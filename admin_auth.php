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
 * Manage aspects of the snapp plugin
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
require_once(dirname(__FILE__).'/admin_form.php');

admin_externalpage_setup('snapp_manage');

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('local/snapp:administer', $context);

require_once(dirname(__FILE__).'/snapp_lib.php');

$PAGE->requires->css('/local/snapp/styles.css');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('socialnetworkapps', 'local_snapp'));
echo $OUTPUT->heading(get_string('managepage', 'local_snapp'), 2);

echo $OUTPUT->box_start('generalbox');

//CONFIRMATION SCREENS NEED TO GO HERE, BEFORE ANY PAGE CONTENT
//CHECK FOR CERTIFICATE DELETION
$deletecert = optional_param('deletecert', 0, PARAM_INT);

if ($deletecert > 0 && confirm_sesskey()) {
    $confirm = optional_param('confirm', false, PARAM_BOOL);
    $keyname = optional_param('keyname', '', PARAM_TEXT);

    if (!$confirm) {

        echo $OUTPUT->confirm(get_string('confirmdelcert', 'local_snapp', $keyname),
        new moodle_url($PAGE->url, array('deletecert' => $deletecert, 'keyname' => $keyname, 'confirm' => 1)),
        $PAGE->url);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;

    } else {
        if ( snapp_security::delete_cert($deletecert)) {
            echo $OUTPUT->notification(get_string('deletedcert', 'local_snapp', $keyname), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('nodeletedcert', 'local_snapp', $keyname), 'notifyproblem');
        }
    }
}
//END CHECK FOR CERTIFICATE DELETION
//CHECK FOR CONSUMER KEY DELETION
$deletekey = optional_param('deletekey', 0, PARAM_INT);

if ($deletekey > 0 && confirm_sesskey()) {
    $confirm = optional_param('confirm', false, PARAM_BOOL);
    $keyname = optional_param('keyname', '', PARAM_TEXT);

    if (!$confirm) {

        echo $OUTPUT->confirm(get_string('confirmdelkey', 'local_snapp', $keyname),
        new moodle_url($PAGE->url, array('deletekey' => $deletekey, 'keyname' => $keyname, 'confirm' => 1)),
        $PAGE->url);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;

    } else {
        if ( snapp_security::delete_consumer_key($deletekey)) {
            echo $OUTPUT->notification(get_string('deletedkey', 'local_snapp', $keyname), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('nodeletedkey', 'local_snapp', $keyname), 'notifyproblem');
        }
    }
}
//END CHECK FOR CONSUMER KEY DELETION

//CONTAINER MANAGEMENT
//check for enable/disable of container
$contenable = optional_param('contenable', -1, PARAM_INT);

if ($contenable > -1 && confirm_sesskey()) {
    $confirm = optional_param('confirm', false, PARAM_BOOL);
    $name = optional_param('name', '', PARAM_TEXT);
    $id = required_param('cont', PARAM_INT);

    if (!$confirm) {

        echo $OUTPUT->confirm(get_string('confirmcontenable'.$contenable, 'local_snapp', $name),
        new moodle_url($PAGE->url, array('contenable' => $contenable, 'name' => $name, 'cont' => $id, 'confirm' => 1)),
        $PAGE->url);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;

    } else {
        if (snapp_lib::set_container_enabled($id, $contenable)) {
            echo $OUTPUT->notification(get_string('enabledcont', 'local_snapp', $name), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('noenabledcont', 'local_snapp', $name), 'notifyproblem');
        }
    }
}
//check for container delete
$deletecont = optional_param('deletecont', 0, PARAM_INT);

if ($deletecont > 0 && confirm_sesskey()) {
    $confirm = optional_param('confirm', false, PARAM_BOOL);
    $name = optional_param('name', '', PARAM_TEXT);

    if (!$confirm) {

        echo $OUTPUT->confirm(get_string('confirmdelcont', 'local_snapp', $name),
        new moodle_url($PAGE->url, array('deletecont' => $deletecont, 'name' => $name, 'confirm' => 1)),
        $PAGE->url);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;

    } else {
        try {
            snapp_lib::delete_container($deletecont);
            echo $OUTPUT->notification(get_string('deletedcont', 'local_snapp', $name), 'notifysuccess');
        } catch (Exception $e) {
            echo $OUTPUT->notification(get_string('nodeletedcont', 'local_snapp',
                array('name' => $name, 'error' => $e->getMessage())), 'notifyproblem');
        }
    }
}
//Process if new container added (do this before table)
$contform = new snapp_addcontainer_form();
if ($fromform = $contform->get_data()) {
    if (snapp_lib::insert_container($fromform->container)) {
        $addnewcontok = true;
    } else {
        $addnewcontok = false;
    }
}
//Print a table of all containers - these can be disabled or deleted (which also deletes all users + keys/certs for container id)
echo $OUTPUT->heading(get_string('contman', 'local_snapp'), 3);
$table = new flexible_table('local_snapp_admin_conts_table');
$table->define_columns(array('container', 'enabled', 'delete'));
$table->define_headers(array(get_string('th_container', 'local_snapp'),
    get_string('th_enabled', 'local_snapp'), get_string('th_deletecont', 'local_snapp')));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'local_snapp_admin_conts_table');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthnormal');
$table->setup();
$table->start_output();//Cheat here and call this method so table always shows

foreach (snapp_lib::get_containers() as $cont) {
    $delete = new moodle_url($PAGE->url, array('deletecont' => $cont->id, 'name' => $cont->name, 'sesskey' => sesskey()));
    $delete = html_writer::link($delete, get_string('delete'));
    //enable/disable link
    if ($cont->enabled == 1) {
        $enable = new moodle_url($PAGE->url, array('contenable' => '0', 'cont' => $cont->id,
            'name' => $cont->name, 'sesskey' => sesskey()));
        $enable = html_writer::link($enable, get_string('disable'));
    } else {
        $enable = new moodle_url($PAGE->url, array('contenable' => '1', 'cont' => $cont->id,
            'name' => $cont->name, 'sesskey' => sesskey()));
        $enable = html_writer::link($enable, get_string('enable'));
    }
    $table->add_data(array($cont->name, $enable, $delete));
}

$table->finish_output();
//allow manual entry of container
echo html_writer::start_tag('div', array('id' => 'snapp-newcont', 'class' => 'box generalbox'));
echo $OUTPUT->heading(get_string('newcont', 'local_snapp'), 4);
//notify about success above form
if ($fromform = $contform->get_data()) {
    if ($addnewcontok) {
        echo $OUTPUT->notification(get_string('addedcont', 'local_snapp'), 'notifysuccess');
        $contform->clear();//clear form
    } else {
        echo $OUTPUT->notification(get_string('noaddedcont', 'local_snapp'), 'notifyproblem');
    }
}
$contform->display();
echo html_writer::end_tag('div');

//PROCESS IF NEW CERTIFICATE ADDED (do this before table is shown so new entry is added)
$cform = new snapp_addcertificate_form();
if ($fromform = $cform->get_data()) {
    if (snapp_security::insert_cert($fromform->container, $fromform->keyname, $fromform->cert)) {
        $addnewcertok = true;
    } else {
        $addnewcertok = false;
    }
}
/// Print a table of all certificates in the db
echo $OUTPUT->heading(get_string('certman', 'local_snapp'), 3);
$table = new flexible_table('local_snapp_admin_certs_table');
$table->define_columns(array('container', 'key', 'delete'));
$table->define_headers(array(get_string('th_container', 'local_snapp'),
    get_string('th_keyname', 'local_snapp'), get_string('th_delete', 'local_snapp')));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'local_snapp_admin_certs_table');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthnormal');
$table->setup();
$table->start_output();//Cheat here and call this method so table always shows

foreach (snapp_security::get_certs() as $cert) {
    $delete = new moodle_url($PAGE->url, array('deletecert' => $cert->id, 'keyname' => $cert->keyname, 'sesskey' => sesskey()));
    $delete = html_writer::link($delete, get_string('delete'));
    $table->add_data(array($cert->container, $cert->keyname, $delete));
}

$table->finish_output();

//Allow manual entry of new certs

echo html_writer::start_tag('div', array('id' => 'snapp-newcert', 'class' => 'box generalbox'));
echo $OUTPUT->heading(get_string('newcert', 'local_snapp'), 4);
//notify about success above form
if ($fromform = $cform->get_data()) {
    if ($addnewcertok) {
        echo $OUTPUT->notification(get_string('addedcert', 'local_snapp'), 'notifysuccess');
        $cform->clear();//clear form
    } else {
        echo $OUTPUT->notification(get_string('noaddedcert', 'local_snapp'), 'notifyproblem');
    }
}
$cform->display();
echo html_writer::end_tag('div');

//Manage consumer keys
//PROCESS IF NEW CONSUMER KEY ADDED (do this before table is shown so new entry is added)
$csform = new snapp_addconsumerkey_form();
if ($fromform = $csform->get_data()) {
    if (snapp_security::insert_consumer_key($fromform->consumerkey, $fromform->secret, $fromform->containerid, $fromform->info)) {
        $addnewkeyok = true;
    } else {
        $addnewkeyok = false;
    }
}
//table of consumer keys
echo $OUTPUT->heading(get_string('keyman', 'local_snapp'), 3);
$table = new flexible_table('local_snapp_admin_keys_table');
$table->define_columns(array('key', 'secret', 'container', 'info', 'delete'));
$table->define_headers(array(get_string('th_ckey', 'local_snapp'), get_string('th_csecret', 'local_snapp'),
    get_string('th_container', 'local_snapp'), get_string('th_cinfo', 'local_snapp'), get_string('th_delete', 'local_snapp')));
$table->define_baseurl($PAGE->url);
$table->set_attribute('id', 'local_snapp_admin_keys_table');
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthnormal');
$table->setup();
$table->start_output();//Cheat here and call this method so table always shows

foreach (snapp_security::get_consumer_keys() as $cert) {
    $delete = new moodle_url($PAGE->url, array('deletekey' => $cert->id, 'keyname' => $cert->consumerkey, 'sesskey' => sesskey()));
    $delete = html_writer::link($delete, get_string('delete'));
    $table->add_data(array($cert->consumerkey, $cert->secret, $cert->container, $cert->info, $delete));
}

$table->finish_output();
//Allow manual entry of new consumer keys

echo html_writer::start_tag('div', array('id' => 'snapp-newkey', 'class' => 'box generalbox'));
echo $OUTPUT->heading(get_string('newkey', 'local_snapp'), 4);
//notify about success above form
if ($fromform = $csform->get_data()) {
    if ($addnewkeyok) {
        echo $OUTPUT->notification(get_string('addedkey', 'local_snapp'), 'notifysuccess');
        $csform->clear();//clear form
    } else {
        echo $OUTPUT->notification(get_string('noaddedkey', 'local_snapp'), 'notifyproblem');
    }
}
$csform->display();
echo html_writer::end_tag('div');

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
