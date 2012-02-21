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
 * Database upgrades.
 *
 * @package local
 * @subpackage snapp
 * @copyright 2012 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_snapp_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes

    if ($oldversion < 2011011401) {

        //Remove the oujson capabilitiy from system as cannot uninstall web service protocols
        capabilities_cleanup('webservice_oujson');

        upgrade_plugin_savepoint(true, 2011011401, 'local', 'snapp');
    }

    return true;
}
