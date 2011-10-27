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
 * Maps open social user to Moodle user
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * User authorisation that they accept mapping of gadget container to moodle
 * * 2: authorise - Moodle page
 * 2.1 - show form to activate mapping (needs valid token)
 * 2.2 do mapping, show close text (needs session key + valid token)
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/snapp_lib.php');

global $CFG, $USER;

//login
require_login(null, false);//In theory this should stop auto-login as guest?

//check capability (should be prevented for guests)
$context = get_context_instance(CONTEXT_SYSTEM);

if (isguestuser()) {
    //Guests can't use this feature
    print_error('guestnotallowed', 'local_snapp');
}

$snapp = new snapp_lib(true);

//If connector is turn off we return nothing
if ($snapp->isenabled() === false) {
    print_error('disabled', 'local_snapp');
}

//check for a token
$token = required_param('token', PARAM_ALPHANUMEXT);
$owner = required_param('owner', PARAM_TEXT);
$container = required_param('container', PARAM_TEXT);
$site = required_param('site', PARAM_TEXT);
$save = optional_param('save', '', PARAM_TEXT);
$cancel = optional_param('cancel', '', PARAM_TEXT);

//check token validity, if no good error
if ($token != snapp_mapuser::check_map_token($token, $owner, $container, $snapp)) {
    print_error('invalidmaptoken', 'local_snapp');
}

$containerid = snapp_lib::get_containerid_fromname($container);
//Check container exists, check container is enabled
if (!$containerid || snapp_lib::iscontainer_name_disabled($container)) {
    //problem getting container record from DB
    print_error('disabledcont', 'local_snapp');
}

//Double check user is not mapped already
$existsalready = snapp_mapuser::user_map_exists($owner, $containerid);

global $PAGE, $OUTPUT;
$PAGE->requires->css('/local/snapp/styles.css');
$PAGE->set_url('/local/snapp/authorise.php', array('token' => $token, 'owner' => $owner, 'container' => $container, 'site' => $site));
$PAGE->set_context($context);

//which state to display?
if ($save != '' || $cancel != '' || $existsalready) {
    //2.2 save user + give feedback (window close or error)
    if ($save != '' && !$existsalready) {
        //save user, check session key first
        require_sesskey();
        if (!snapp_mapuser::create_user_map($owner, $containerid, $USER->id, false)) {
            print_error('mapusererror', 'local_snapp');
        }
    }

    $PAGE->set_title(get_string('closewindow', 'local_snapp'));
    $PAGE->set_pagelayout('popup');
    $PAGE->requires->js_init_call('window.close');
    echo $OUTPUT->header();

    echo html_writer::tag('a', get_string('closewindow', 'local_snapp'), array('href' => 'javascript:window.close()'));

} else {
    //2.1 Setup moodle page

    $PAGE->set_title(get_string('authorisepagetitle', 'local_snapp'));
    $PAGE->set_heading(get_string('authorisepagetitle', 'local_snapp'));
    //$PAGE->navbar->add($PAGE->title);

    echo $OUTPUT->header();

    echo html_writer::start_tag('div', array('id' => 'authorisepage'));

    //Info text (create object to put into text)
    $extrainfo = new stdClass();
    $extrainfo->site = $site;

    echo html_writer::tag('p', get_string('authorisepagedesc', 'local_snapp', $extrainfo), array('class' => 'authorisepagedesc'));
    //Form
    echo html_writer::start_tag('form', array('method' => 'post'));
    echo html_writer::input_hidden_params($PAGE->url);
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'save',
        'value' => get_string('authorisepagesubmit', 'local_snapp')));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'cancel',
        'value' => get_string('authorisepagecancel', 'local_snapp')));
    echo html_writer::end_tag('form');

    echo html_writer::end_tag('div');

}
echo $OUTPUT->footer();
