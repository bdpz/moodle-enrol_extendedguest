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
 * Url module admin settings and defaults
 *
 * @package    enrol_extendedguest
 * @copyright  2018 Baptiste Desprez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $settings->add(new admin_setting_configcheckbox('enrol_extendedguest/defaultenrol', get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_extendedguest/status', get_string('status', 'enrol_extendedguest'), get_string('status_desc', 'enrol_extendedguest'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_extendedguest/extendedguest_list_ip', get_string('settings_list_ip', 'enrol_extendedguest'), get_string('settings_list_ip_helptext', 'enrol_extendedguest'), ''));

    $settings->add(new admin_setting_configcheckbox('enrol_extendedguest/localnet', get_string('localnet', 'enrol_extendedguest'), null, 1));

    $settings->add(new admin_setting_configcheckbox('enrol_extendedguest/authenticated_users', get_string('authenticated_users', 'enrol_extendedguest'), null, 1));
}