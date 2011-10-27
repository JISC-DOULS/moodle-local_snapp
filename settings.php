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
 * Config settings
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Config settings used in snapp,
 * Should be saved in config_plugins table under local_snapp.
 *
 * Links to admin external pages used to manage plugin (local/snapp:administer capability)
 */

require_once(dirname(__FILE__).'/snapp_lib.php');

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {//Stops errors when tree not available, but still allows external pages to work

    //SNAPP tree
    $ADMIN->add('localplugins', new admin_category('local_snapp', get_string('socialnetworkapps', 'local_snapp')));

    //Config page
    $configpage = new admin_settingpage('local_snapp_config', get_string('configsettings', 'local_snapp'));

    //DEFINE PLUGIN CONFIG SETTINGS HERE

    //enabled: determines if feature is turned 'on' or not.
    $enabled = new admin_setting_configcheckbox('enabled', get_string('snapp_admin_enabled', 'local_snapp'),
                get_string('snapp_admin_enabled_desc', 'local_snapp'), 0);
    $enabled->plugin = 'local_snapp';

    $configpage->add($enabled);

    //accesskey: Security salt used in token generation etc
    $accesskey = new admin_setting_configtext('accesskey', get_string('snapp_admin_accesskey', 'local_snapp'),
                get_string('snapp_admin_accesskey_desc', 'local_snapp'), '');
    $accesskey->plugin = 'local_snapp';

    $configpage->add($accesskey);

    //secretkey: Secret key used for hash token generation
    $secretkey = new admin_setting_configtext('secretkey', get_string('snapp_admin_secretkey', 'local_snapp'),
                get_string('snapp_admin_secretkey_desc', 'local_snapp'), '');
    $secretkey->plugin = 'local_snapp';

    $configpage->add($secretkey);

    //get all the external_services_users records (nicely formatted with details)
    $wsusers = snapp_lib::return_external_services_users();

    $wsuser = new admin_setting_configselect('wsuser', get_string('snapp_admin_wsuser', 'local_snapp'),
                get_string('snapp_admin_wsuser_desc', 'local_snapp'), 0, $wsusers);
    $wsuser->plugin = 'local_snapp';
    $configpage->add($wsuser);

    $anycert = new admin_setting_configcheckbox('anycert', get_string('snapp_admin_anycert', 'local_snapp'),
                get_string('snapp_admin_anycert_desc', 'local_snapp'), 0);
    $anycert->plugin = 'local_snapp';

    $configpage->add($anycert);

    $ADMIN->add('local_snapp', $configpage);

    //External pages (non config settings e.g. data management) should use local/snapp:administer capability
    $ADMIN->add('local_snapp', new admin_externalpage('snapp_manage', get_string('managepage', 'local_snapp'),
                $CFG->wwwroot.'/local/snapp/admin_auth.php', 'local/snapp:administer'));
    $ADMIN->add('local_snapp', new admin_externalpage('snapp_manageusers', get_string('manageuserspage', 'local_snapp'),
                $CFG->wwwroot.'/local/snapp/admin_users.php', 'local/snapp:vieweditusermapping'));
}
