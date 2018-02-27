<?php
/**
 * Url module admin settings and defaults
 *
 * @package    enrol_extendedguest
 * @copyright  2018 Baptiste Desprez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) { die('Direct access to this script is forbidden.'); }

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $settings->add(new admin_setting_configcheckbox('enrol_extendedguest/defaultenrol', get_string('defaultenrol', 'enrol'), get_string('defaultenrol_desc', 'enrol'), 1));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_extendedguest/status', get_string('status', 'enrol_extendedguest'), get_string('status_desc', 'enrol_extendedguest'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_extendedguest/extendedguest_list_ip', get_string('settings_list_ip', 'enrol_extendedguest'), get_string('settings_list_ip_helptext', 'enrol_extendedguest'), ''));

    $settings->add(new admin_setting_configcheckbox('enrol_extendedguest/localnet', get_string('localnet', 'enrol_extendedguest'), null, 1));

    $settings->add(new admin_setting_configcheckbox('enrol_extendedguest/authenticated_users', get_string('authenticated_users', 'enrol_extendedguest'), null, 1));
}