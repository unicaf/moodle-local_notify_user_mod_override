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



namespace local_course_reminder\task;
//require_once('../../../../config.php');
//require_once($CFG->dirroot."/local/course_reminder/classes/emails.php");
include_once($CFG->dirroot."/local/course_reminder/classes/emails.php");
class send_notification_task extends \core\task\scheduled_task
{
    public function get_name()
    {
        // TODO: Implement get_name() method.
        return get_string('pluginfullname', 'local_course_reminder');
    }

    public function execute()
    {
        send_email_by_cron();


    }

}