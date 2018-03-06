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
 * Extended Guest Access enrolment plugin.
 *
 * @package    enrol_extendedguest
 * @copyright  2018 Baptiste Desprez
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Extended Guest Access plugin implementation.
 * @author Baptiste Desprez
 * @copyright  2018 Baptiste Desprez
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_extendedguest_plugin extends enrol_plugin {

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon or empty if none
     */
    public function get_info_icons(array $instances) {
        $config = get_config('enrol_extendedguest');

        foreach ($instances as $instance) {
            if ($instance->customint1 == '1') {
                if (self::ip_in_range($_SERVER['REMOTE_ADDR'], $config->extendedguest_list_ip) === true) {
                    return array(new pix_icon('withoutpassword',
                        get_string('guestaccess_withoutpassword', 'enrol_extendedguest'), 'enrol_extendedguest'));
                }
            }

            if ($instance->customint2 == '1') {
                if (isloggedin() && !isguestuser()) {
                    return array(new pix_icon('withoutpassword',
                        get_string('guestaccess_withoutpassword', 'enrol_extendedguest'), 'enrol_extendedguest'));
                }
            }

            return array();
        }
    }

    /**
     * Enrol a user using a given enrolment instance.
     *
     * @param stdClass $instance
     * @param int $userid
     * @param null $roleid
     * @param int $timestart
     * @param int $timeend
     * @param null $status
     * @param null $recovergrades
     */
    public function enrol_user(stdClass $instance, $userid, $roleid = null,
        $timestart = 0, $timeend = 0, $status = null, $recovergrades = null) {
        // Nothing to do, we never enrol here!
        return;
    }

    /**
     * Enrol a user from a given enrolment instance.
     *
     * @param stdClass $instance
     * @param int $userid
     */
    public function unenrol_user(stdClass $instance, $userid) {
        // Nothing to do, we never enrol here!
        return;
    }

    /**
     * Attempt to automatically gain temporary guest access to course,
     * calling code has to make sure the plugin and instance are active.
     *
     * @param stdClass $instance course enrol instance
     * @return bool|int false means no guest access, integer means end of cached time
     */
    public function try_guestaccess(stdClass $instance) {
        global $CFG;

        $config = get_config('enrol_extendedguest');

        if ($instance->customint1 == '1') {
            if (self::ip_in_range($_SERVER['REMOTE_ADDR'], $config->extendedguest_list_ip) === true) {
                $context = context_course::instance($instance->courseid);
                load_temp_course_role($context, $CFG->guestroleid);
                return ENROL_MAX_TIMESTAMP;
            }
        }

        if ($instance->customint2 == '1') {
            if (isloggedin() && !isguestuser()) {
                $context = context_course::instance($instance->courseid);
                load_temp_course_role($context, $CFG->guestroleid);
                return ENROL_MAX_TIMESTAMP;
            }
        }

        return false;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/extendedguest:config', $context);
    }

    /**
     * Returns localised name of enrol instance.
     *
     * @param stdClass $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance)) {
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol);
        } else if (empty($instance->name)) {
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol);
        } else {
            return format_string($instance->name, true, array('context' => context_course::instance($instance->courseid)));
        }
    }

    /**
     * Given a courseid this function returns true if the user is able to enrol or configure ip filtering.
     *
     * @param int $courseid
     * @return bool
     */
    public function can_add_instance($courseid) {
        global $DB;
        $coursecontext = context_course::instance($courseid);
        if (!has_capability('moodle/course:enrolconfig', $coursecontext)
            or ! has_capability('enrol/extendedguest:config', $coursecontext)) {
            return false;
        }

        if ($DB->record_exists('enrol', array('courseid' => $courseid, 'enrol' => 'extendedguest'))) {
            return false;
        }

        return true;
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param stdClass $course
     * @param stdClass $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        global $DB;

        if ($inserted) {
            if ($this->get_config('defaultenrol')) {
                $this->add_default_instance($course);
            }
        }
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = array(
            'status' => $this->get_config('status'),
            'customint1' => $this->get_config('localnet'),
            'customint2' => $this->get_config('authenticated_users'),
        );

        return $this->add_instance($course, $fields);
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;

        if (!$DB->record_exists('enrol', array('courseid' => $data->courseid, 'enrol' => $this->get_name()))) {
            $this->add_instance($course, (array) $data);
        }

        // No need to set mapping, we do not restore users or roles here.
        $step->set_mapping('enrol', $oldid, 0);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/extendedguest:config', $context);
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED => get_string('yes'), ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }

    /**
     * Return an array of valid options for the localnet and authenticated_users.
     *
     * @return array
     */
    protected function get_options() {
        $options = array(
            0 => get_string('no'),
            1 => get_string('yes'),
        );
        return $options;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $DB;

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_extendedguest'), $options);

        $options = $this->get_options();
        $mform->addElement('select', 'customint1', get_string('localnet', 'enrol_extendedguest'), $options);
        $mform->setDefault('customint1', $this->get_config('localnet'));
        $mform->addElement('select', 'customint2', get_string('authenticated_users', 'enrol_extendedguest'), $options);
        $mform->setDefault('customint2', $this->get_config('authenticated_users'));
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname" => value) of submitted data
     * @param array $files array of uploaded files "element_name" => tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name" => "error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = array();

        $validstatus  = array_keys($this->get_status_options());
        $validoptions = array_keys($this->get_options());
        $tovalidate   = array(
            'status' => $validstatus,
            'customint1' => $validoptions,
            'customint2' => $validoptions,
        );

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors     = array_merge($errors, $typeerrors);

        return $errors;
    }

    /**
     * Check if a given ip is in a network
     * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
     * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
     * @return boolean true if the ip is in this range / false if not.
     */
    public static function ip_in_range($ip, $range) {
        if (strpos($range, ',') !== false) {
            $listrange = explode(',', $range);
            foreach ($listrange as $singlerange) {
                $singlerange = trim($singlerange);
                if (self::ip_in_range($ip, $singlerange)) {
                    return true;
                }
            }

            return false;
        }

        if (strpos($range, '/') === false) {
            $range .= '/32';
        }

        if (preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/[0-9]{1,2})?$/", $range) === 0) {
            return false;
        }

        list( $range, $netmask ) = explode('/', $range, 2);
        $rangedecimal    = ip2long($range);
        $ipdecimal       = ip2long($ip);
        $wildcarddecimal = pow(2, ( 32 - $netmask)) - 1;
        $netmaskdecimal  = ~ $wildcarddecimal;
        return ( ( $ipdecimal & $netmaskdecimal ) == ( $rangedecimal & $netmaskdecimal ) );
    }
}

/**
 * Get icon mapping for font-awesome.
 */
function enrol_extendedguest_get_fontawesome_icon_map() {
    return [
        'enrol_extendedguest:withoutpassword' => 'fa-id-badge',
    ];
}
