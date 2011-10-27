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
 * Gets gadget xml files from local and return with some additions
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * 'Serve' gadget xml files
 * Files must reside in local directory
 * Parameters can be added to the original xml and replaced when 'served'
 *
 */

//setup lib etc
require_once(dirname(__FILE__).'/../../../config.php');
global $CFG;
require_once($CFG->libdir.'/filelib.php');
require_once(dirname(__FILE__).'/../snapp_lib.php');

//check for url + verify is a safe local file (should stop directory traversal etc)
$gurl = optional_param('gurl', '', PARAM_LOCALURL);

if ($gurl == '') {
    gadgetserv_error();
}

$snapp = new snapp_lib();
//If connector is turn off we return nothing
if ($snapp->isenabled() === false) {
    gadgetserv_error();
}

//if it's an xml file then get the contents by reading disk
if (strripos($gurl, '.xml') == strlen($gurl)-4) {

    $file = dirname(__FILE__).'/../../'.$gurl;

    $content = (is_readable($file) && ($content = file_get_contents($file))) ? $content : false;

} else {
    //if it's not an xml file then get contents via http as this is safer (e.g. won't return php code)
    $file = $CFG->wwwroot.'/local/'.$gurl;

    $content = snapp_lib::get_from_web($file);

}

if (!$content) {
    gadgetserv_error();
}

$output = snapp_gadgetxml::replace_placeholders($content);

snapp_gadgetxml::output_xml($output, $file);

//if error send 404 error response
function gadgetserv_error() {
    send_file_not_found();
    exit;
}
