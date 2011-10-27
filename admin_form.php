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
 * Forms used in snap admin screens
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
require_once("$CFG->libdir/formslib.php");
require_once(dirname(__FILE__).'/snapp_lib.php');

class snapp_addcontainer_form extends moodleform {


    public function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('text', 'container', get_string('newcont_container', 'local_snapp'));
        $mform->addRule('container', get_string('required'), 'required', '', 'client', false, false);
        $mform->setType('container', PARAM_TEXT);
        $mform->setDefault('container', '');

        $mform->addElement('html', '<p>'.get_string('newcont_desc', 'local_snapp').'</p>');

        $this->add_action_buttons(false, get_string('newcont_submit', 'local_snapp'));

    }

    //Clear form values so they can be removed on successful submit
    public function clear() {
        $mform =& $this->_form;
        $mform->setConstant('container', '');
    }
}

class snapp_addcertificate_form extends moodleform {


    public function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('text', 'container', get_string('newcert_container', 'local_snapp'));
        $mform->addRule('container', get_string('required'), 'required', '', 'client', false, false);
        $mform->setType('container', PARAM_TEXT);
        $mform->setDefault('container', '');

        $mform->addElement('text', 'keyname', get_string('newcert_keyname', 'local_snapp'));
        $mform->addRule('keyname', get_string('required'), 'required', '', 'client', false, false);
        $mform->setType('keyname', PARAM_TEXT);

        $mform->addElement('textarea', 'cert', get_string('newcert_cert', 'local_snapp'));
        $mform->addRule('cert', get_string('required'), 'required', '', 'client', false, false);
        $mform->setType('cert', PARAM_RAW);

        $this->add_action_buttons(false, get_string('newcert_submit', 'local_snapp'));

    }

    //Clear form values so they can be removed on successful submit
    public function clear() {
        $mform =& $this->_form;
        $mform->setConstant('container', '');
        $mform->setConstant('keyname', '');
        $mform->setConstant('cert', '');
    }
}

class snapp_addconsumerkey_form extends moodleform {


    public function definition() {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('text', 'consumerkey', get_string('newkey_consumerkey', 'local_snapp'));
        $mform->addRule('consumerkey', get_string('required'), 'required', '', 'client', false, false);
        $mform->setType('consumerkey', PARAM_TEXT);
        $mform->setDefault('consumerkey', '');

        $mform->addElement('text', 'secret', get_string('newkey_secret', 'local_snapp'));
        $mform->addRule('secret', get_string('required'), 'required', '', 'client', false, false);
        $mform->setType('secret', PARAM_TEXT);

        $containers = snapp_lib::get_containers();
        $contarray = array();
        foreach ($containers as $key => $value) {
            $contarray[$value->id] = $value->name;
        }

        $mform->addElement('select', 'containerid', get_string('newkey_container', 'local_snapp'), $contarray);

        $mform->addElement('textarea', 'info', get_string('newkey_info', 'local_snapp'));
        $mform->addRule('info', get_string('maximumchars', '', 255), 'maxlength', '255', 'client', false, false);
        $mform->setType('info', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('newkey_submit', 'local_snapp'));

    }

    //Clear form values so they can be removed on successful submit
    public function clear() {
        $mform =& $this->_form;
        $mform->setConstant('consumerkey', '');
        $mform->setConstant('secret', '');
        $mform->setConstant('info', '');
    }
}
