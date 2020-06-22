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
 * Implementaton of the quizaccess_originalityquiz plugin.
 *
 * @package   quizaccess_originalityquiz
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');


/**
 * A rule requiring the student to promise not to cheat.
 *
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_originalityquiz extends quiz_access_rule_base {

    /** @var array the allowed keys. */
    protected $allowedkeys;

    public function __construct($quizobj, $timenow) {
        parent::__construct($quizobj, $timenow);
        $this->allowedkeys = self::split_keys($this->quiz->originalityquiz_allowedkeys);
    }

    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {


        return new self($quizobj, $timenow);
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        global $DB;
        global $PAGE;
        global $CFG;
        global $USER;







        
        $plagiarismsettings = (array)get_config('plagiarism');
        $adminallowsstudentviewreport = $plagiarismsettings['originality_view_report'];

        if (!empty($plagiarismsettings['originality_use'])) {
            // There doesn't seem to be a way to obtain the current cm a better way - $this->_cm is not available here.
            $cmid = optional_param('update', 0, PARAM_INT);
            $ynoptions = array(0 => get_string('no'), 1 => get_string('yes'));
            $mform->addElement('header', 'originalitydesc', get_string('originality', 'plagiarism_originality'));
            $mform->addHelpButton('originalitydesc', 'originality', 'plagiarism_originality');
            $mform->addElement('select', 'originality_use', get_string("useoriginality", "plagiarism_originality"), $ynoptions);
            if ($adminallowsstudentviewreport) {
                $mform->addElement('select', 'student_view_report',
                                    get_string("originality_view_report", "plagiarism_originality"), $ynoptions);
            } else {
                $mform->addElement('hidden', 'student_view_report', '0');
            }
            $mform->setType('student_view_report', PARAM_NOTAGS);
            $select = 'cm = ?';

            if ($originalityuse = $DB->get_record_select('plagiarism_originality_conf', $select, array($cmid))) {
                // If there is a record at all, it means originality was enabled.
                $mform->setDefault('originality_use', 1);
                if ($adminallowsstudentviewreport) {
                    $mform->setDefault('student_view_report', $originalityuse->student_view_report);
                }
            }
        }

    }

    public static function validate_settings_form_fields(array $errors,
            array $data, $files, mod_quiz_mod_form $quizform) {

        if (!empty($data['originalityquiz_allowedkeys'])) {
            $keyerrors = self::validate_keys($data['originalityquiz_allowedkeys']);
            if ($keyerrors) {
                $errors['originalityquiz_allowedkeys'] = implode(' ', $keyerrors);
            }
        }

        return $errors;
    }


    public static function save_settings($quiz) {
        global $DB;
        /* if (empty($quiz->originalityquiz_allowedkeys)) {
            $DB->delete_records('quizaccess_originalityquiz', array('quizid' => $quiz->id));
        } else {
            $record = $DB->get_record('quizaccess_originalityquiz', array('quizid' => $quiz->id));
            if (!$record) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->allowedkeys = self::clean_keys($quiz->originalityquiz_allowedkeys);
                $DB->insert_record('quizaccess_originalityquiz', $record);
            } else {
                $record->allowedkeys = self::clean_keys($quiz->originalityquiz_allowedkeys);
                $DB->update_record('quizaccess_originalityquiz', $record);
            }
        } */
    }

    public static function delete_settings($quiz) {
        global $DB;
       // $DB->delete_records('quizaccess_originalityquiz', array('quizid' => $quiz->id));
    }

    public static function get_settings_sql($quizid) {
       /* return array(
            'originalityquiz.allowedkeys AS originalityquiz_allowedkeys',
            'LEFT JOIN {quizaccess_originalityquiz} originalityquiz ON originalityquiz.quizid = quiz.id',
            array()); */
    }

    public function prevent_access() {
        
        return '';
    }

    public function description() {
        return self::get_blocked_user_message();
    }

    /**
     * Get the list of allowed browser keys for the quiz we are protecting.
     *
     * @return array of string, the allowed keys.
     */
    public function get_allowed_keys() {
        return $this->allowedkeys;
    }

    public function setup_attempt_page($page) {
        $page->set_title($this->quizobj->get_course()->shortname . ': ***** ' . $page->title);
        $page->set_cacheable(false);
        $page->set_popup_notification_allowed(false); // Prevent message notifications.
        $page->set_heading($page->title);
        $page->set_pagelayout('secure');
    } 

    /**
     * Generate the message that tells users they must use the secure browser.
     */
    public static function get_blocked_user_message() {
        
        global $OUTPUT, $PAGE, $DB;


        $plagiarismsettings = (array)get_config('plagiarism');
        $select = 'cm = ?';


        $str = $OUTPUT->box_start('generalbox boxaligncenter', 'intro-originality'); //  2016-01-01 Changed id of element from 'intro'.

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $path = core_component::get_plugin_directory("mod", "originality");

        // $PAGE->requires->js('/plagiarism/originality/javascript/jquery-3.1.1.min.js');
        // $PAGE->requires->js('/plagiarism/originality/javascript/inter.js?v=24');
        $str .= format_text(get_string("originalitystudentdisclosure", "plagiarism_originality"), FORMAT_MOODLE, $formatoptions);

        // Ben Gurion University requested an additional statement here.
        $bgu_addition = '';
        
        //  "I agree supports English and Hebrew
        $str.= "<div style='margin-top:10px'> <input  style='vertical-align: middle; margin-bottom: 4px; margin-right: 5px;'
        id='iagree' name='iagree' type='checkbox'/>". "<label for='iagree' >".get_string('agree_checked', 'plagiarism_originality').$bgu_addition ."</label>" ."</div>";

        $click_checkbox_msg = get_string("originality_click_checkbox_msg", 'plagiarism_originality');

        $click_checkbox_button_text = get_string("originality_click_checkbox_button_text", 'plagiarism_originality');

        $str .= <<<HHH
        <span id='click_checkbox_msg' style='display:none;'>$click_checkbox_msg</span>
        <span id='click_checkbox_button_text' style='display:none;'>$click_checkbox_button_text</span>
HHH;

        $str .= $OUTPUT->box_end();

        // $result = $PAGE->requires->js_call_amd('quizaccess_originalityquiz/timer', 'init', array($str));

        return $str;
    }

    /**
     * This helper method takes list of keys in a string and splits it into an
     * array of separate keys.
     * @param string $keys the allowed keys.
     * @return array of string, the separate keys.
     */
    public static function split_keys($keys) {
        $keys = preg_split('~[ \t\n\r,;]+~', $keys, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($keys as $i => $key) {
            $keys[$i] = strtolower($key);
        }
        return $keys;
    }

    /**
     * This helper method validates a string containing a list of keys.
     * @param string $keys a list of keys separated by newlines.
     * @return array list of any problems found.
     */
    public static function validate_keys($keys) {
        $errors = array();
        $keys = self::split_keys($keys);
        $uniquekeys = array();
         foreach ($keys as $i => $key) {
            if (!preg_match('~^[a-f0-9]{64}$~', $key)) {
                $errors[] = get_string('allowedbrowserkeyssyntax', 'quizaccess_originalityquiz');
                break;
            }
        }
        if (count($keys) != count(array_unique($keys))) {
            $errors[] = get_string('allowedbrowserkeysdistinct', 'quizaccess_originalityquiz');
        } 
        return $errors;
    }

    /**
     * This helper method takes a set of keys that would pass the slighly relaxed
     * validation peformed by {@link validate_keys()}, and cleans it up so that
     * the allowed keys are lower case and separated by a single newline character.
     * @param string $keys the allowed keys.
     * @return string a cleaned up version of the $keys string.
     */
    public static function clean_keys($keys) {
        return implode("\n", self::split_keys($keys));
    }

    /**
     * Check the whether the current request is permitted.
     * @param array $keys allowed keys
     * @param context $context the context in which we are checking access. (Normally the quiz context.)
     * @return bool true if the user is using a browser with a permitted key, false if not,
     * or of the user has the 'quizaccess/originalityquiz:exemptfromcheck' capability.
     */
    public static function check_access(array $keys, context $context) {
        if (has_capability('quizaccess/originalityquiz:exemptfromcheck', $context)) {
            return true;
        }
        if (!array_key_exists('HTTP_X_originalityquiz_REQUESTHASH', $_SERVER)) {
            return false;
        }
        return self::check_keys($keys, self::get_this_page_url(),
                trim($_SERVER['HTTP_X_originalityquiz_REQUESTHASH']));
    }

    /**
     * Return the full URL that was used to request the current page, which is
     * what we need for verifying the X-originalityquiz-RequestHash header.
     */
    public static function get_this_page_url() {
        global $FULLME;
        return $FULLME;
    }

    /**
     * Check the hash from the request header against the permitted keys.
     * @param array $keys allowed keys.
     * @param string $url the request URL.
     * @param string $header the value of the X-originalityquiz-RequestHash to check.
     * @return bool true if the hash matches.
     */
    public static function check_keys(array $keys, $url, $header) {
        foreach ($keys as $key) {
            if (self::check_key($key, $url, $header)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check the hash from the request header against a single permitted key.
     * @param string $key an allowed key.
     * @param string $url the request URL.
     * @param string $header the value of the X-originalityquiz-RequestHash to check.
     * @return bool true if the hash matches.
     */
    public static function check_key($key, $url, $header) {
        return hash('sha256', $url . $key) === $header;
    }



   /**
     * It is possible for one rule to override other rules.
     *
     * The aim is that third-party rules should be able to replace sandard rules
     * if they want. See, for example MDL-13592.
     *
     * @return array plugin names of other rules that this one replaces.
     *      For example array('ipaddress', 'password').
     */
    public function get_superceded_rules() {
        echo "8888"; 
        return array();
    }
}
