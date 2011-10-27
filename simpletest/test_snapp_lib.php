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
 * unit tests for snapp
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once(dirname(__FILE__).'/../snapp_lib.php');


class local_snapp_test_snapp_lib extends UnitTestCase {

    public static $includecoverage = array('local/snapp/snapp_lib.php');
    //public  static $excludecoverage = array();

    private $snapp;

    public function setUp() {
        $this->snapp = new snapp_lib(true);
    }

    public function test_construct() {
        //test that settingslist is array
        $this->assertIsA($this->snapp->settings, 'stdClass');
    }


    public function test_verify_opensocial_request() {
        $result = snapp_security::verify_opensocial_request();
        $this->assertFalse($result->success);
        $this->assertEqual($result->error, 'notsigned');

        $result = snapp_security::verify_opensocial_request(array('oauth_signature_method' => 'RSA-SHA1'));
        $this->assertFalse($result->success);
        $this->assertEqual($result->error, 'notsigned');

        $result = snapp_security::verify_opensocial_request(array('oauth_signature_method' => 'HMAC-SHA1'));
        $this->assertFalse($result->success);
        $this->assertEqual($result->error, 'notregistered');
    }

    public function test_get_os_container() {
        $result = snapp_security::get_os_container(array('oauth_consumer_key' => '1234test'));
        $this->assertEqual($result, '1234test');

        $result = snapp_security::get_os_container(array('empty'));
        $this->assertEqual($result, '');
    }

    public function test_get_os_pubkey() {
        $result = snapp_security::get_os_pubkey(array('xoauth_signature_publickey' => 'http://1234test'));
        $this->assertEqual($result, 'http://1234test');
    }

    public function test_replace_placeholder() {
        global $CFG;

        $stringtotest = 'wibble%%moodleurl%%wobble';
        $result = snapp_gadgetxml::replace_placeholders($stringtotest);

        $this->assertPattern("($CFG->wwwroot)", $result);
    }
}