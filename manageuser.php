<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Manage a users snapp mapping
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Allows user (or nominated other with appropriate permission) to:
 * View current container/id's they are mapped to
 * Remove any of their mappings
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/snapp_lib.php');

global $CFG, $USER, $PAGE, $OUTPUT;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

//login
require_login(null, false);//In theory this should stop auto-login as guest?

if (isguestuser()) {
    //Guests can't use this feature
    print_error('guestnotallowed', 'local_snapp');
}

$params = array();

$userid = optional_param('userid', 0, PARAM_INT);

if ($userid != 0) {
    //we want to view/ammend someone else's mapping - need permission to do this
    require_capability('local/snapp:vieweditusermapping', context_system::instance());

    //check the requested user exists
    if (!$user = $DB->get_record('user', array('id'=>$userid))) {
        print_error('invaliduserid');
    }

    $params['userid'] = $userid;
} else {
    $userid = $USER->id;
}

$title = get_string('manageusertitle', 'local_snapp');

$PAGE->requires->css('/local/snapp/styles.css');

//Different context depending on how accessed
if (!isset($params['userid'])) {
    //editing your own - show user profile stuff
    $context = context_user::instance($USER->id);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('mydashboard');
} else {
    //editing someone elses - so an admin type person
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('admin');
    admin_externalpage_setup('snapp_manageusers');
}

$PAGE->set_url('/local/snapp/manageuser.php', $params);//make sure after admin_external page

$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'manageuser'));

echo $OUTPUT->box_start();

//delete record
$deleterec = optional_param('deleterec', -1, PARAM_INT);
if ($deleterec != -1) {
    require_sesskey();
    //Delete record, must belong to userid (checked in func)
    if (snapp_mapuser::delete_user_map($deleterec, $userid)) {
        echo $OUTPUT->notification(get_string('manageuser_delete_success', 'local_snapp'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('manageuser_delete_fail', 'local_snapp'), 'notifysuccess');
    }
}

//Show users entries from local_snapp_users amd allow user to delete

$records = snapp_mapuser::get_users_map($userid);

if ($records) {

    $username = $USER->username;

    if ($userid != $USER->id) {
        //show extra info if managing someone else
        $userrec = $DB->get_record('user', array('id' => $userid), 'username', MUST_EXIST);
        $username = $userrec->username;
    }

    $table = new flexible_table('local_snapp_manageuser_table');
    $columns = array('user', 'containername', 'added', 'delete');
    $table->define_columns($columns);
    $table->define_headers(array(get_string('manageuser_th_username', 'local_snapp'),
        get_string('manageuser_th_container', 'local_snapp'),
        get_string('manageuser_th_added', 'local_snapp'),
        get_string('manageuser_th_delete', 'local_snapp')));
    //hide username when actual user looking at table
    if ($userid == $USER->id) {
        $table->column_class('user', 'snapp-hidden');
    }
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('id', 'local_snapp_manageuser_table');
    $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthnormal');
    $table->setup();

    foreach ($records as $cont) {
        $delete = new moodle_url($PAGE->url, array('deleterec' => $cont->id, 'sesskey' => sesskey()));
        $delete = html_writer::link($delete, get_string('manageuser_delete', 'local_snapp'));

        $table->add_data(array($username, $cont->container, userdate($cont->added), $delete));
    }

    $table->finish_output();

} else {
    echo html_writer::tag('p', get_string('manageuser_norecords', 'local_snapp'), array('id' => 'manageuser_norecords'));
}

echo $OUTPUT->box_end();

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
