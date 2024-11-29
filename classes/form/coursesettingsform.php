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


require_once("$CFG->libdir/formslib.php");
require_once 'classes/checkStatus.php';


class coursesettingsform extends moodleform
{
    //CREATE A NEW FORM OFF MOODLEFORM
    //Form Setup
    public function definition()
    {
    //THESE OPTIONS ARE FOR DATABASE
        $OPTIONS = [
            '0' => 'No',
            '1' => 'Yes'

        ];
        $courseid = $this->_customdata['id'];


        $value_from_database_enable = new checkStatusClass($courseid);
        $value_from_database_enable = $value_from_database_enable->is_enabled();
        $mform = $this->_form;
        $mform->addElement('select', 'enable', "Enable", $OPTIONS);
        $mform->setType('enable', PARAM_INT);
        //By Default in Database is set to Yes (1) when a user override is created
        $mform->setDefault('enable', $value_from_database_enable);
        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons();


    }


}

