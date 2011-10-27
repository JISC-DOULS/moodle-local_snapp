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
 * SNAPP user admin screen (external admin page)
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $OUTPUT, $PAGE;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

admin_externalpage_setup('snapp_manageusers');

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('local/snapp:vieweditusermapping', $context);

require_once(dirname(__FILE__).'/snapp_lib.php');

$PAGE->requires->css('/local/snapp/styles.css');

$baseurl = '/local/snapp/admin_users.php';

$page = optional_param('page', 0, PARAM_INT);   // which page to show
$perpage = 25;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('socialnetworkapps', 'local_snapp'));
echo $OUTPUT->heading(get_string('manageuserspage', 'local_snapp'), 2);

echo $OUTPUT->box_start('generalbox');

//Print a table of all users that are recorded in snapp user table

$table = new flexible_table('local_snapp_admin_mapusers_table');
$table->define_columns(array('username', 'link'));
$table->define_headers(array(get_string('manageuser_th_username', 'local_snapp'), get_string('manageuser_th_link', 'local_snapp')));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'local_snapp_admin_conts_table');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthnormal');
$table->setup();
$table->start_output();//Cheat here and call this method so table always shows

$users = snapp_mapuser::get_all_users_map($usercount, $page * $perpage, $perpage);

foreach ($users as $user) {
    $link = new moodle_url('/local/snapp/manageuser.php', array('userid' => $user->id));
    $link = html_writer::link($link, get_string('mapusers_link', 'local_snapp'));

    $table->add_data(array($user->username, $link));
}

$table->finish_output();

echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
