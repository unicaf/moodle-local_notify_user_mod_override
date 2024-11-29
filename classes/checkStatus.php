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
class checkStatusClass
{
    function __construct($courseid)
    {
//        echo "This is the new Course id from constructor ($courseid)";
        $this->courseid = $courseid;
        $this->tableID = $this->get_id_table();

//        var_dump($this);
    }

    function checkStatus()

    {
        global $DB;

        $this->isEnabled = $this->is_enabled();
        return $recordExisits = $DB->record_exists("local_course_reminder", ["courseid" => "$this->courseid"]);



    }

    function add_to_table()
    {
        // if record is not in database its adds it
        global $DB;
        $record = new stdClass();
        $record->courseid = $this->courseid;
        $DB->insert_record('local_course_reminder', $record, false);


    }

    function set_enable($fromform)
    {

        // Sets field enable to 0 or 1 within the coursesettings.php
        global $DB;

        $record1 = new stdClass();

        $record1->id = $this->tableID;
        $record1->enable = $fromform->enable;
        $record1->courseid = $this->courseid;

//        If record doesnt exisit in table local_course_reminder then add it
        if(!$record1->id ){
            $DB->insert_record('local_course_reminder', $record1, false);
            return;

        }
        // If have ID in table continue from here

        $DB->update_record('local_course_reminder', $record1);

        /*This used to be used if a course had for example off notifiction and then turned on it would not sent email to students which had it off.
        This is good to not send email to users which were not sent once it was off
        */
//        $get_id_local_course_reminder_email = $DB->get_records('local_course_reminder_email', ['courseid' => $this->courseid], "", "id");
//
//        $record2 = new stdClass();
//
//        $record2->id = $get_id_local_course_reminder_email;
//        foreach ($record2->id as $record) {
////           var_dump($record->id);
//            $record2->emailtosent = $fromform->enable;
//            $record2->id = $record->id;
//            $update_local_course_reminder_email = $DB->update_record('local_course_reminder_email', $record2);
//        }


    }

    function is_enabled()
    {
        //Gets enable field from database on table
        global $DB;
        $is_enabled = $DB->get_record('local_course_reminder', ['courseid' => $this->courseid], 'enable');

        if(!$is_enabled){
          return;
        }
        return $is_enabled->enable;

    }

    function get_id_table()
    {
        //Gets id of instance for the coruseid
        global $DB;
        $table_id = $DB->get_record('local_course_reminder', ['courseid' => $this->courseid], 'id');
        if(!$table_id){
            return $table_id;

        }
        return $table_id->id;

    }

    function who_to_send_notification($emailofUser, $courseName, $component, $assignmentName, $assignId, $assignmentDate, $assignmentOverrideDate, $assignment_url, $coursemodulesid)
    {
        $this->studentEmail = $emailofUser->email;
        $this->userid = $emailofUser->id;
        $this->courseName = $courseName;
        $this->component = $component;
        $this->assignmentname = $assignmentName;
        $this->assignmentid = $assignId;
        $this->assignmentDate = $assignmentDate;
        $this->assignmentOverrideDate = $assignmentOverrideDate;
        $this->assignmentURL = $assignment_url;
//        var_dump($this);
        global $DB;
        $table = "local_course_reminder_email";
        $dataObj = new stdClass();

        $dataObj->userid = $emailofUser->id;

        $dataObj->assigmentname = $assignmentName;


        $dataObj->assignmentdate = $assignmentDate;
        $dataObj->assignmentoverridedate = $assignmentOverrideDate;
        $dataObj->coursemodulesid = $coursemodulesid;
        //Stops duplicate entry.

        $record_exisits = $DB->record_exists($table, ["coursemodulesid" => $dataObj->coursemodulesid, "userid" => $dataObj->userid]);


        if (!$record_exisits) {
            //Adds to the database

            $DB->insert_record($table, $dataObj);
        }

    }


}