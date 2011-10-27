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
 * Checks user mapping (is gadget user in Moodle) against oauth opensocial call
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * 1: check user - No login required, returns JSON.
 *     JSON structure:
 *     .message - returns with error if there was a 'fatal' error
 *     .debuginfo - if debug mode info on the request & processing
 *     .userexists - true/false user mapping found in db
 *     .url - url to login page (2) so user can register and create mapping
 */

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../snapp_lib.php');

global $CFG;

$snapp = new snapp_lib(true);

//If connector is turn off we return nothing
if ($snapp->isenabled() === false) {
    mapuser_error('disabled', true);
}

$json = array();

$debuginfo = '';
if (debugging()) {
    $json['debuginfo'] = &$debuginfo;
    $debuginfo = '<pre>'.print_r($_REQUEST, true).'</pre>';
    $debuginfo .= $_SERVER['QUERY_STRING']."\r";
}

//Verify request is signed using OAuth (
//this will also add public certificates + container if not exist in DB)
$checkrequest = snapp_security::verify_opensocial_request();

if (!$checkrequest->success) {
    mapuser_error($checkrequest->error, true);
}
if (debugging()) {
    $debuginfo .= $checkrequest->debug;
}
$container = snapp_security::get_os_container();
$containerid = snapp_lib::get_containerid_fromname($container);

if (!$containerid) {
    //problem getting container record from DB
    mapuser_error('disabledcont', true);
}

//Get owner and viewer info
$ownerid = snapp_security::get_os_ownerid();
$viewerid = snapp_security::get_os_viewerid();

//we shouldn't ever get a problem here as verified requests should have info....
if (!$container && !$ownerid) {
    mapuser_error('notsigned', true);
}

if (debugging()) {
    $debuginfo .= "\rOS container:$container\rOS container id:$containerid\r
    OS ownerid:$ownerid\rOS viewer id:$viewerid";
}

//Check if owner in db table
if (snapp_mapuser::user_map_exists($ownerid, $containerid)) {
    $json['userexists'] = true;
} else {
    $json['userexists'] = false;
    //if owner is viewer then create auth token and send url so they can login and create mapping
    if ($ownerid !== $viewerid) {
        //In theory could slip past here is owner and viewer are false, but that shouldn't happen as reuqest would then not validate
        mapuser_error('notmapped', true);
    }
    //If user is not signed in (id -1) then send message
    if ($ownerid == -1 || $viewerid == -1 || (!$ownerid && !$viewerid)) {
        $json['instructions'] = get_string('map_need_sign_in', 'local_snapp', $container);
    } else {
        $tokenstr = snapp_mapuser::create_map_token($ownerid, $container, $snapp);
        $params = array();
        $params['token'] = $tokenstr;
        $params['owner'] = $ownerid;
        $params['container'] = $container;
        $params['site'] = snapp_security::get_os_container_name();
        $url = new moodle_url($CFG->wwwroot.'/local/snapp/authorise.php', $params);
        $url = $url->out();
        $json['url'] = $url;
        $json['instructions'] = get_string('map_instructions', 'local_snapp');
        $json['linktext'] = get_string('map_linktext', 'local_snapp');
    }
}

//TODO Enable sending of Moodle user id so that mapping can be made here and authorisation is not required (so instant mapping)

//return user exists
if (debugging()) {
    $debuginfo .= "\rUser exists in DB:{$json['userexists']}";
    if (isset($json['url'])) {
        $debuginfo .= "\rUser map url:{$json['url']}";
    }
    $debuginfo = str_replace(array("\n", "\r"), '<br/>', $debuginfo);
}
print json_encode($json);


function mapuser_error($error, $json=false) {
    if (!$json) {
        print_error($error, 'local_snapp');
    } else {
        global $CFG;
        print json_encode(array('message'=>get_string($error, 'local_snapp')));
        exit;//only return error if json
    }
}
