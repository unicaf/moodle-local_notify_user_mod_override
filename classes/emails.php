<?php
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version info
 *
 * @package    local_course_reminder
 * @copyright  2023 UNICAF LTD <info@unicaf.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/group/lib.php');





function send_email_by_cron()
{
    global $DB;
    $table = 'local_course_reminder_email';
    $get_record_for_cron = $DB->get_records($table, ["emailtosent" => "1", "emailsent" => "0"], '', "*");
    $keys = array_keys($get_record_for_cron);


    $object = new stdClass();
        for($i=0; $i<count($get_record_for_cron); $i++){
        foreach($get_record_for_cron[$keys[$i]] as $key => $value){
            $object->$key = $value;

            }
        //Emails the student
        email_Student($object,"student");
        //Emails the Teacher
        email_Student($object,"teacher");


        }

}




function email_sent($table, $id){
    global $DB;

//    var_dump($id);
    $object = new stdClass();
    $object->id = $id;
    $object->emailsent = "1";
    $object->emailtosent = "0";
    $object->emailtime = sent_email_time();
//    var_dump($object);

    $DB->update_record($table,$object);




}
function sent_email_time(){
    //Returns time
    return time();
}

function email_Student($studentObj,$typeOfUser){
    global $USER,$DB;
    $assignmentID = $studentObj->assignmentid;
    $emailFrom = core_user::get_noreply_user();
    // Email of the student
    $student = $studentObj->studentid;
    //User object
    $emailofStudent = \core_user::get_user($student);

    $studentFirstName = $emailofStudent->firstname;
    $studentLastName = $emailofStudent->lastname;
    $component = $studentObj->component;
    if ($component == 'quiz') {
        $assignmentID = $studentObj->quizid;
    }
    //Assignment Name
    $assignmentName = getAssignmentName($assignmentID, $component);
    $assignmentName = $assignmentName->name;
    //Course ID
    $courseid = $studentObj->courseid;
    $student_id_number= $emailofStudent->idnumber;
    $courseFullName = getCourseName($courseid)->fullname;
    //Shortname is also know as offer
    $courseShortName = getCourseName($courseid)->shortname;

    $assignmentDate = $studentObj->assignmentdate;
    //Transform the date (original date of assignment)
    $assignmentDate = date('d-M-Y H:i', $assignmentDate);
    // Date of the extension set
    $assignmentOverrideDate = $studentObj->assignmentoverridedate;
    $assignmentOverrideDate = date('d-M-Y H:i', $assignmentOverrideDate);




    $student_group = get_student_group($courseid,$student);
    if($student_group == NULL){
       $student_group= " ";
    }else{
        $student_group = $student_group->name;
    }



    if($typeOfUser === 'student') {
        //Email of Unicaf extenuating Circumstances
        $extenuatingCircumstances = html_writer::link("mailto:extenuating.circumstances@unicaf.org", "extenuating.circumstances@unicaf.org");


        $contextinstanceid = $studentObj->contextinstanceid;

        //Assignment link
        $assignment_url = get_assignment_url($contextinstanceid, $component);
        //Makes it as a link
        $assignment_url = html_writer::link($assignment_url, $assignmentName);

        echo nl2br("Email is being sent to student with ID " . $emailofStudent->id . "\n");

        //Subject of email
        $subject = "Your course " . $courseFullName . " has some changes in " . $component . " has changed dates";
        //Message of email
        $message = "Dear " . $studentFirstName . "\n\n Following the review of your extenuating circumstances claim, we would like to inform you that your application for an extenstion for  " . $courseShortName . " " . $courseFullName . " " .$student_group ."
        has been approved .\n\n The assessment deadline for " . $assignment_url . " has been changed from " . $assignmentDate . " to  <strong> " . $assignmentOverrideDate . " </strong>. \n\n"
            . "In case you have already submitted " . $component . " " . $assignment_url . " prior or on " . $assignmentOverrideDate . ", then rest assured that your assignment will be sent for marking .\n\n
        In case you are yet to submit " . $component . " " . "$assignment_url" . ", please do so no later than by the new extended deadline " . $assignmentOverrideDate .
            "\n\n Should you require any further clarification, please do not hesitate to contact the Unicaf Extenuating Circumstances team directly on " . $extenuatingCircumstances;
        // Function to send email
        email_to_user($emailofStudent, $emailFrom, $subject, $message, nl2br($message), "", "", "");
        email_sent("local_course_reminder_email", $studentObj->id);
    }elseif($typeOfUser==="teacher"){
        //Gets ID for 'editing tutor'
        $role=$DB->get_record('role',array('shortname'=>'editingteacher'));
        $context = context_course::instance($courseid);

        //Gets Group ID of the student
        $group_id = groups_get_group_by_name($courseid,$student_group);
        //Gets Teachers of the Group.
        $teachers = get_role_users($role->id,$context,"","","","",$group_id);
//    print_r($teachers);
        $subject = "Student Extension for course " . $courseShortName . " for student " . $studentFirstName .  " has been granted";

        //Emails each Teacher
        foreach ($teachers as $teacher){
            echo nl2br("Email is being sent to teacher with ID " . $teacher->id . "\n");
            $message = "Dear " .$teacher->firstname . " ".$teacher->lastname .", \n\n" . "Please be informed that assessment deadlines which relate to " .$courseFullName . " " .$courseShortName .  " ". $student_group . " have been changed as follows and require your attention \n 
         Below you can find the details for your associated actions \n\n" . "Student's name: " .$studentFirstName ." "  .$studentLastName ." \n" ."UniSIS ID number: "  .$student_id_number ."\n " . " Assessment name :".$assignmentName . "\n Previous assessment deadline: ".$assignmentDate ." \n  New assessment deadline: ".$assignmentOverrideDate . "\n\n In case the student submits or already submitted their work within the new assessment deadline, please bear in mind that this work should proceed through the marking process.";


            //SEND EMAIL
            email_to_user($teacher, $emailFrom, $subject, $message, nl2br($message), "", "", "");

        }



    }



}
function get_assignment_url($contextinstanceid, $component){
    if($component == "assignment") {
        return new \moodle_url('/mod/assign/view.php', array('id'=> $contextinstanceid));
    }elseif ($component == "quiz"){
        return new \moodle_url('/mod/quiz/view.php',array('id'=> $contextinstanceid));
    }
}

function getAssignmentName($id,$component){
    /* In this function we require two parameters , $id and $table. The ID is the ID of the assignment or quiz
    and the table is for the database table (mdl_assign is for assignments and mdl_quiz for quizes)
    We then create a database connection and add our $table parameter passed from getData and return back
    an object with the name of the assignment/quiz
    */
    global $DB;
    if($component == "assignment") {
        return $assignmentName = $DB->get_record("assign", array('id' => $id), 'name');
    }elseif ($component == "quiz"){
         return $assignmentName = $DB->get_record('quiz', array('id' => $id), 'name');
    }

}

function getCourseName($courseid){
    global $DB;
    $name = $DB->get_record('course', array('id'=>$courseid),'fullname,shortname');
    return $name;
}


function get_student_group($courseid,$userid){
    $table="groups";
    global $DB;
      $group = groups_get_user_groups($courseid,$userid);
    $groups = [];
    $group_keys = array_keys($group);

    for($i=0; $i<count($group); $i++){
        foreach($group[$group_keys[$i]] as $key =>$value){
            array_push($groups,$value);
        }
    }
  foreach ($groups as $group){
      return $DB->get_record($table,array("id"=>$group),"name");

  }

}

function get_teacher_of_student_group($courseid,$userid){
    $get_group_student = groups_is_member('3');
    var_dump($get_group_student);
}