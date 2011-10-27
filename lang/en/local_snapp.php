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
 * Language strings for local/snapp
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SNAPP (Social Network Apps)';

$string['snapp:administer'] = 'Alter SNAPP configuration';
$string['snapp:vieweditusermapping'] = 'View and edit other users mapping with social network containers'.

$string['socialnetworkapps'] = 'Social Network Application Plugin';

//Admin Settings
$string['managepage'] = 'Manage authorisation';
$string['manageuserspage'] = 'Manage users';
$string['configsettings'] = 'Configuration settings';

$string['snapp_admin_enabled'] = 'Enabled';
$string['snapp_admin_enabled_desc'] = 'Turn on/off the Social Network connector';

$string['snapp_admin_accesskey'] = 'Security salt';
$string['snapp_admin_accesskey_desc'] = 'Salt value added to security token generation (if this setting is left blank a non-secure default will be used)';

$string['snapp_admin_secretkey'] = 'Secret key';
$string['snapp_admin_secretkey_desc'] = 'Secret password used in token generation (if this setting is left blank a non-secure default will be used)';

$string['snapp_admin_wsuser'] = 'External service+user';
$string['snapp_admin_wsuser_desc'] = 'When using Web Services with the Social Network plugin you need to specify the combination of Moodle External Service and user that will be used to make the web service calls (a valid token must be available for this selection).
 In order to use this feature you must follow the process detailed in the web services admin settings:Overview - "One system controlling Moodle with a token"';

$string['snapp_admin_anycert'] = 'Allow external certificates';
$string['snapp_admin_anycert_desc'] = 'When enabled the plugin will attempt to download public certificates that are not stored in the database. This means unknown gadget containers can request web services.';

//Admin page
$string['contman'] = 'Container management';
$string['certman'] = 'Certificate management';
$string['th_enabled'] = 'Enabled';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['confirmcontenable0'] = 'Are you sure you wish to disable the container [{$a}]?<br/>All SNAPP services will be denied to requests from this container.';
$string['confirmcontenable1'] = 'Are you sure you wish to enable the container [{$a}]?<br/>All SNAPP services will be enabled to requests from this container.';
$string['enabledcont'] = 'Changed container [{$a}] enabled status.';
$string['noenabledcont'] = 'Error changing container [{$a}] enabled status';
$string['th_deletecont'] = 'Delete';
$string['confirmdelcont'] = 'Are you sure you wish to delete the container [{$a}]?<br/>By deleteing the container you also remove records for all associated keys, certificates and users.';
$string['deletedcont'] = 'Container [{$a}] deleted.';
$string['nodeletedcont'] = 'Error when deleting container [{$a->name}].<br/>{$a->error}';
$string['newcont'] = 'Add container';
$string['newcont_container'] = 'Container name';
$string['newcont_desc'] = 'Name of the container. If using public certificate signing (RSA-SHA1) then container name needs to match oauth_consumer_key.<br/>See https://opensocialresources.appspot.com/certificates.';
$string['newcont_submit'] = 'Add container';
$string['addedcont'] = 'Added new container';
$string['noaddedcont'] = 'Error adding container';
$string['th_container'] = 'Container';
$string['th_keyname'] = 'Public key';
$string['th_delete'] = 'Delete entry';
$string['confirmdelcert'] = 'Are you sure you wish to delete the certificate [{$a}]?';
$string['deletedcert'] = 'Certificate [{$a}] deleted.';
$string['nodeletedcert'] = 'Error when deleting certificate [{$a}].';
$string['newcert'] = 'Add certificate';
$string['newcert_container'] = 'OpenSocial container (oauth_consumer_key)';
$string['newcert_keyname'] = 'Certificate name (xoauth_signature_publickey)';
$string['newcert_cert'] = 'Certificate contents (string)';
$string['newcert_submit'] = 'Add certificate details';
$string['addedcert'] = 'Added new certificate';
$string['noaddedcert'] = 'Error adding certificate';
$string['keyman'] = 'Consumer key/secret management';
$string['th_ckey'] = 'Consumer key';
$string['th_csecret'] = 'Consumer secret';
$string['th_cinfo'] = 'Information';
$string['confirmdelkey'] = 'Are you sure you wish to delete the consumer key [{$a}]?';
$string['deletedkey'] = 'Consumer key [{$a}] deleted.';
$string['nodeletedkey'] = 'Error when deleting consumer key [{$a}].';
$string['newkey'] = 'Add consumer key';
$string['newkey_consumerkey'] = 'Consumer key (oauth_consumer_key)';
$string['newkey_secret'] = 'Consumer secret';
$string['newkey_container'] = 'Associated container';
$string['newkey_info'] = 'Useful information (optional)';
$string['newkey_submit'] = 'Add new key';
$string['addedkey'] = 'Added new consumer key';
$string['noaddedkey'] = 'Error adding consumer key';

//Authorise page
$string['authorisepagetitle'] = 'Authorisation';
$string['authorisepagedesc'] = 'By authorising this gadget to access your OU account, you will be
 able to use it and any other OU gadget on <em>{$a->site}</em>.
<br/><br/>
The gadget will have access to information such as which modules you are studying.
 This information will not be stored on <em>{$a->site}</em> and other gadgets will not have access to it.
<br/<br/>
Gadgets do not have access to your OUCU, your password or other sensitive information in your account.
';
$string['authorisepagesubmit'] = 'Authorise access';
$string['authorisepagecancel'] = 'Deny access';
$string['closewindow'] = 'Close window';
$string['guestnotallowed'] = 'Sorry, you must log in as a full user to use this feature.';

//Manage user page
$string['manageusertitle'] = 'Manage gadget access';
$string['manageuser_th_username'] = 'Username';
$string['manageuser_th_container'] = 'Site name';
$string['manageuser_th_added'] = 'Access granted date';
$string['manageuser_th_delete'] = 'Remove access';
$string['manageuser_th_link'] = 'Manage users access';
$string['mapusers_link'] = 'Manage user access';
$string['manageuser_delete'] = 'Delete';
$string['manageuser_delete_success'] = 'Successfully deleted access';
$string['manageuser_delete_fail'] = 'Error encountered when removing access.';
$string['manageuser_norecords'] = 'No records found to manage.';

//User mapping
$string['map_instructions'] = 'To use this OU gadget, you need to authorise access to your OU account. To do this go to the ';
$string['map_linktext'] = 'authorisation page';
$string['map_need_sign_in'] = 'You must be signed-in to {$a} to use this gadget.';
//Errors
$string['disabled'] = 'This feature has been disabled by the system administrator.';
$string['disabledcont'] = 'Use of this network has been disabled by the system administrator.';
$string['validation_failed'] = 'Request not verified.';
$string['cert_error'] = 'Not a valid certificate';
$string['notsigned'] = 'Not a valid OpenSocial signed request';
$string['notregistered'] = 'No consumer key registered';
$string['invalidmaptoken'] = 'Invalid credentials supplied to undertake this operation.';
$string['mapusererror'] = 'Database error, try refreshing this page [F5].';
$string['notmapped'] = 'Access has not yet been granted for this gadget.';
$string['wssetuperror'] = 'Web service access for gadgets is not enabled by your institution.';
$string['invalidwscall'] = 'Invalid call made.';
$string['wserror'] = 'Error accessing web service.';
