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


require_once('../../config.php');
require __DIR__.'/classes/checkStatus.php';
require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot.'/local/course_reminder/classes/form/coursesettingsform.php');
global $DB, $COURSE, $PAGE;

//This is only accesible to users who are able to edit the course settings
if (!has_capability('moodle/course:update', context_course::instance($PAGE->course->id))) {
    return;
}

//Displays the form
$courseid = required_param("id", PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/local/course_reminder/coursesettings.php', array('id' => $courseid)));


$PAGE->set_context(context_course::instance($courseid));
$PAGE->set_title("Customize Reminder Settings");
$PAGE->set_heading(get_string('pluginname', 'local_course_reminder'));

$isEnabled = new checkStatusClass($courseid);
//Ads it to database local_course_reminder if not there
$checkifDatabase = $isEnabled->checkStatus();
$customdata = array('id' => $courseid);
//mform is the from we created
$mform = new coursesettingsform(null, $customdata);

//When the form is canceled
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));

} else if ($fromform = $mform->get_data()) {

    $isEnabled->set_enable($fromform);
    redirect(new moodle_url('/course/view.php', array('id' => $courseid)));


}
//DISPLAYS HEADER
echo $OUTPUT->header();
//DISPLAYS THE FORM
$mform->display();
echo $OUTPUT->footer();
