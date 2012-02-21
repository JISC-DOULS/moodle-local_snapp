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
 * Provides secure 'layer' to Moodle webservice when using oauth
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Moodle web services 'layer'
 * Calls json web service specified with user token as defined in plugin config settings
 * Will check validity of call and will pass through user id of verified user
 * Send wsfunction (same as needed for web service call) + wsparams (any params you need to send to service - encoded)
 */

//Constant definitions to match those in web service calls
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'/../snapp_lib.php');

global $CFG, $DB;

require_once($CFG->dirroot.'/webservice/lib.php');

$debuginfo = '';
if (debugging()) {
    $debuginfo = '<pre>'.print_r($_REQUEST, true).'</pre>';
    $debuginfo .= $_SERVER['QUERY_STRING']."\r";
}

//expecting same params as a web service call: need function name
$wsfunction = optional_param('wsfunction', '', PARAM_ALPHAEXT);
if ($wsfunction == '') {
    callws_error('invalidwscall', $debuginfo);
}
//Send all parameters that the ws needs encoded within this param
$wsparams = optional_param('wsparams', '', PARAM_TEXT);
$wsparamsarray = array();//holds our ws params as array
if ($wsparams != '') {
    //turn our encoded parameters into an array
    parse_str(rawurldecode($wsparams), $wsparamsarray);
}

//Service requires web service to be enabled, might as well check now...
if ($CFG->enablewebservices == 0) {
    callws_error('wssetuperror', $debuginfo);
}

$snapp = new snapp_lib(true);

//If connector is turn off we return nothing
if ($snapp->isenabled() === false) {
    callws_error('disabled', $debuginfo);
}

//Verify request is signed using OAuth (this will also add public certificates + container if not exist in DB)
$checkrequest = snapp_security::verify_opensocial_request();
if (debugging()) {
    $debuginfo .= $checkrequest->debug;
}

if (!$checkrequest->success) {
    //callws_error($checkrequest->error, $debuginfo);
}

$container = snapp_security::get_os_container();
$containerid = snapp_lib::get_containerid_fromname($container);

if (!$containerid) {
    //problem getting container record from DB
    if (debugging()) {
        $debuginfo .= "\rCan't find container record.";
    }
    callws_error('disabledcont', $debuginfo);
}

//Get owner and viewer info
$ownerid = snapp_security::get_os_ownerid();
$viewerid = snapp_security::get_os_viewerid();

//We need to make sure no one has hacked the call and added in a moodle user id
//We can also then populate any appropriate params with the userid of the gadget owner
//It's difficult to do this as it depends on the function called as to what the param might be called
//Could either be a userids array
//Or in a [userid] element sub array
//Or something else that we can't really determine!
//First try and get these the sub elements by lopping thru whole wsparams obj
$useridelements = array();

foreach ($wsparamsarray as $param => $value) {
    if (is_array($value) && isset($value['userid'])) {
        //record element so can be overridden later
        $useridelements[$param] = 'userid';
    }
}
$userid = 0;
//If one of the parameters is userid or userids then we need to add in the mapped user id
//In this instance:
//Owner must be verified as a Moodle user
//If owner and viewer are not the same then there must be a confirmation token sent as well
//(this is against the called ws function, so the viewer can't change the call to do something else)
if (count($useridelements) > 0 || isset($wsparamsarray['userids']) || isset($wsparamsarray['userid'])) {
    //we shouldn't ever get a problem here as verified requests should have info....
    if (!$container && !$ownerid) {
        callws_error('notsigned', $debuginfo);
    }
    //If owner not in the system - error
    if (!snapp_mapuser::user_map_exists($ownerid, $containerid)) {
        callws_error('notmapped', $debuginfo);
    }
    //If viewer is not the owner then need to confirm they have a token
    if ($ownerid !== $viewerid) {
        $ftoken = optional_param('ftoken', '', PARAM_ALPHANUMEXT);
        if ($ftoken == '') {
            callws_error('invalidwscall', $debuginfo);
        }
        //Check the token
        if ($ftoken != snapp_security::hash($wsfunction, $snapp)) {
            callws_error('invalidwscall', $debuginfo);
        }
    }
    //Get the user id of the owner
    $userid = snapp_mapuser::get_user_map_userid($ownerid, $containerid);

    //Replace user id and user ids with value of owner to insure other users data is not exposed
    if (count($useridelements) > 0) {
        foreach ($useridelements as $param => $value) {
            $wsparamsarray[$param][$value] = $userid;
        }
    }
    if (isset($wsparamsarray['userids'])) {
        $wsparamsarray['userids'] = array($userid);//make sure it's just the one userid
    }
    if (isset($wsparamsarray['userid'])) {
        $wsparamsarray['userid'] = $userid;//make userid to our verified userid
    }
}

//Call web service by:
//Get WS function + user from snapp config and then find tokens that match
//If more than 1 token then go through and make sure there is one that has not expired
//If still more than 1 check if any have no ip restriction set - if there are none without then what?
//Call rest web service via moodle curl call or call via php(would need to set request[])??
$wsuserid = $snapp->get_wsuserid();
if ($wsuserid == 0) {
    //not setup
    if (debugging()) {
        $debuginfo .= "\rWeb service user not defined.";
    }
    callws_error('wssetuperror', $debuginfo);
}
//First get the required record so we know ws user and service ids
if (!$wsuser = $DB->get_record('external_services_users', array('id' => $wsuserid), 'externalserviceid,userid')) {
    callws_error('wssetuperror', $debuginfo);
}
//Get matching token(s)
if (!$wstokens = $DB->get_records('external_tokens', array('userid' => $wsuser->userid,
    'externalserviceid' => $wsuser->externalserviceid, 'tokentype' => EXTERNAL_TOKEN_PERMANENT))) {
    if (debugging()) {
        $debuginfo .= "\rNo token defined for web service user.";
    }
    callws_error('wssetuperror', $debuginfo);
}
//If more than one token returned we need to make sure that we use a valid one (in date and no ip restrict)
$wstoken = '';
if (count($wstokens) > 1) {
    foreach ($wstokens as $tokenrec => $tokenrecval) {
        if (($tokenrecval->validuntil == 0 || $tokenrecval->validuntil >= time()) && $tokenrecval->iprestriction == '') {
            $wstoken = $tokenrecval->token;
        }
    }
} else {
    $key = array_keys($wstokens);
    $wstoken = $wstokens[$key[0]]->token;
}

if ($wstoken == '') {
    if (debugging()) {
        $debuginfo .= "\rCan't find a valid token for web service user.";
    }
    callws_error('wssetuperror', $debuginfo);
}

//Create a new global request array ready to use in webservice
$_REQUEST = array();
$_REQUEST['wstoken'] = $wstoken;
$_REQUEST['wsfunction'] = $wsfunction;
$_REQUEST = array_merge($_REQUEST, $wsparamsarray);//add in the params specified

//Use rest webservice - Only installed on 2.2+
$webservice = 'rest';
//Call JSON web service directly (using the fudged $_REQUEST)
require_once("$CFG->dirroot/webservice/$webservice/locallib.php");

if (!webservice_protocol_is_enabled($webservice)) {
    if (debugging()) {
        $debuginfo .= "\rWeb service protocol not enabled.";
    }
    callws_error('wssetuperror', $debuginfo);
}
add_to_log(SITEID, 'local', 'snapp', '', $wsfunction, 0, $userid);
$classtocall = 'webservice_' . $webservice . '_server';
$server = new $classtocall(WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN, 'json');
$server->run();
exit;

function callws_error($langstring, $debuginfo = '') {
    callws_sendheaders();
    //return json
    $debuginfo = str_replace(array("\n", "\r"), '<br/>', $debuginfo);
    print json_encode(array('message' => get_string($langstring, 'local_snapp'), 'debuginfo' => $debuginfo));
    exit;
}

function callws_sendheaders() {
    header('Content-Type: application/json');
    header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
    header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
    header('Pragma: no-cache');
    header('Accept-Ranges: none');
}
