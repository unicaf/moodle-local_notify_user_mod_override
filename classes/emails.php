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
    //MAIN FUNCTIONALITY TO RUN BY CRON TO GET WHICH EMAILS TO SEND
    global $DB;
    $table = 'local_course_reminder_email';
    $get_record_for_cron = $DB->get_records($table, [ "emailsent" => "0"], '', "*");
    $keys = array_keys($get_record_for_cron);


    $object = new stdClass();
    for ($i = 0; $i < count($get_record_for_cron); $i++) {
        foreach ($get_record_for_cron[$keys[$i]] as $key => $value) {
            $object->$key = $value;

        }
        //Emails the student
        email_Student($object, "student");
        //Emails the Teacher
        email_Student($object, "teacher");


    }

}


function email_sent($table, $id)
{
    global $DB;


    $object = new stdClass();
    $object->id = $id;
    $object->emailsent = "1";
    $object->emailtime = sent_email_time();


    $DB->update_record($table, $object);


}

function sent_email_time()
{
    //Returns time
    return time();
}

function email_Student($studentObj, $typeOfUser)
{
    global $USER, $DB;


    $course_module_from_id = get_coursemodule_from_id("",$studentObj->coursemodulesid);


    $assignmentID = $course_module_from_id->{'instance'};


    $emailFrom = core_user::get_noreply_user();
    // Email of the student
    $student = $studentObj->userid;
    //User object
    $emailofStudent = \core_user::get_user($student);
    //STUDENT FIRST NAME
    $studentFirstName = $emailofStudent->firstname;
    //STUDENT LAST NAME
    $studentLastName = $emailofStudent->lastname;

    $component = $course_module_from_id->{'modname'};


    $assignmentName = $course_module_from_id->{'name'};

    $courseid = $course_module_from_id->{'course'};
    $student_id_number = $emailofStudent->idnumber;
    $courseFullName = getCourseName($courseid)->fullname;
    //Shortname is also know as offer
    $courseShortName = getCourseName($courseid)->shortname;

    $assignmentDate = $studentObj->assignmentdate;
    //Transform the date (original date of assignment)
    $assignmentDate = date('d-M-Y H:i', $assignmentDate);
    // Date of the extension set
    $assignmentOverrideDate = $studentObj->assignmentoverridedate;
    $assignmentOverrideDate = date('d-M-Y H:i', $assignmentOverrideDate);

    //GETS STUDENT GROUP - IF STUDENT DOESNT HAVE A GROUP IT GIVE AN EMPTY STRING
    $student_group = get_student_group($courseid, $student);
    if ($student_group == NULL) {
        $student_group = " ";
    } else {
        $student_group = $student_group->name;
    }
    //Email of Unicaf extenuating Circumstances
    $extenuatingCircumstances = html_writer::link("mailto:extenuating.circumstances@unicaf.org", "extenuating.circumstances@unicaf.org");
    //Email of Unicaf Quality Assurance
    $quality_assurance_email = html_writer::link("mailto:qualityassurance@unicaf.org", "qualityassurance@unicaf.org");
    //EMAILS TO STUDENT
    if ($typeOfUser === 'student') {




        $coursemodulesid = $studentObj->coursemodulesid;

        //Assignment link
        $assignment_url = get_assignment_url($coursemodulesid, $component);
        //Makes it as a link
        $assignment_url = html_writer::link($assignment_url, $assignmentName);

        echo nl2br("Email is being sent to student with ID ".$emailofStudent->id."\n");

        //Subject of email
        $subject = "Your course ".$courseFullName." has some changes in ".$component." has changed dates";
        //Message of email

$message = <<<ANYTHING
Dear $studentFirstName,

Following the review of your extenuating circumstances request, we would like to inform you that your application for an extension for $courseShortName $courseFullName  $student_group has been approved.

The new assessment deadline for $assignment_url is $assignmentOverrideDate .

Please note that late submission regulations do not apply to extended deadlines. Work submitted later than the above-approved dates/times will not be accepted and will be recorded as 0% . 

In case you fail to meet the passing grade or fail to submit within the extended deadline, you will be required to wait for your results to be reviewed and confirmed by the Awarding body. You will then receive further information on how to proceed with the outstanding module.

Should you require any further clarification, please do not hesitate to contact the Unicaf Extenuating Circumstances team directly on $extenuatingCircumstances 
ANYTHING;

        // Function to send email
        email_to_user($emailofStudent, $emailFrom, $subject, $message, nl2br($message), "", "", "");
        email_sent("local_course_reminder_email", $studentObj->id);

        // EMAILS THE TEACHER
    } elseif ($typeOfUser === "teacher") {
        //Gets ID for 'teacher'
        $role = $DB->get_record('role', array('shortname' => 'teacher'),"id");
        $context = context_course::instance($courseid);


        //Gets Group ID of the student
        $group_id = groups_get_group_by_name($courseid, $student_group);
        //Gets Teachers of the Group.
        $teachers = get_role_users($role->id, $context, "", "", "", "", $group_id);



        $subject = "Student Extension for course ".$courseShortName." for student ".$studentFirstName." has been granted";

        //Emails each Teacher
        foreach ($teachers as $teacher) {
            echo nl2br("Email is being sent to teacher with ID ".$teacher->id."\n");

$message = <<<ANYTHING
Dear $teacher->firstname,

Please be informed that assessment deadlines which relate to $courseShortName have been changed as follows and require your attention.
Below you can find the details for your associated actions:

<ul>
    <li>Student's name: $studentFirstName  $studentLastName</li>
    <li>Student number: $student_id_number</li>
    <li>Assessment name : $assignmentName</li>
    <li>Previous assessment deadline: $assignmentDate</li>
    <li>New assessment deadline: $assignmentOverrideDate</li>
 </ul>
In case the student submits or already submitted their work within the new assessment deadline,
please bear in mind that this work should proceed through the marking process.

Should you require any further clarification, please do no hesitate to contact the Unicaf Extenuating Circumstances team directly on $extenuatingCircumstances and/or the Quality Assurance Department at $quality_assurance_email .
ANYTHING;


            //SEND EMAIL
            email_to_user($teacher, $emailFrom, $subject, $message, nl2br($message), "", "", "");

        }


    }


}

function get_assignment_url($coursemodulesid, $component)
    //GETS ASSIGNMENT/QUIZ URL
{
    if ($component == "assign") {
        return new \moodle_url('/mod/assign/view.php', array('id' => $coursemodulesid));
    } elseif ($component == "quiz") {
        return new \moodle_url('/mod/quiz/view.php', array('id' => $coursemodulesid));
    }
}

function getAssignmentName($id, $component)
{
    /* In this function we require two parameters , $id and $table. The ID is the ID of the assignment or quiz
    and the table is for the database table (mdl_assign is for assignments and mdl_quiz for quizes)
    We then create a database connection and add our $table parameter passed from getData and return back
    an object with the name of the assignment/quiz
    */
    global $DB;
    if ($component == "assign") {
        return $assignmentName = $DB->get_record("assign", array('id' => $id), 'name');
    } elseif ($component == "quiz") {
        return $assignmentName = $DB->get_record('quiz', array('id' => $id), 'name');
    }

}

function getCourseName($courseid)
{
    // GETS FULL NAME AND SHORTNAME WITH COURSEID
    global $DB;
    $name = $DB->get_record('course', array('id' => $courseid), 'fullname,shortname');
    return $name;
}


function get_student_group($courseid, $userid)
    //GETS GROUPS OS STUDENT
{
    $table = "groups";
    global $DB;
    $group = groups_get_user_groups($courseid, $userid);
    $groups = [];
    $group_keys = array_keys($group);

    for ($i = 0; $i < count($group); $i++) {
        foreach ($group[$group_keys[$i]] as $key => $value) {
            array_push($groups, $value);
        }
    }
    foreach ($groups as $group) {
        return $DB->get_record($table, array("id" => $group), "name");

    }

}

