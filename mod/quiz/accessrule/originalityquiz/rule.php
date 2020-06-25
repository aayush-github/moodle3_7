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
require_once($CFG->dirroot . '/plagiarism/originality/locallib.php');

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
        $page->set_title($this->quizobj->get_course()->shortname . ': ' . $page->title);
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

        if ($PAGE->pagetype != 'mod-quiz-view') {
            return;
        }

        $plagiarismsettings = (array)get_config('plagiarism');
        $select = 'cm = ?';


        $str = $OUTPUT->box_start('generalbox boxaligncenter', 'intro-originality'); //  2016-01-01 Changed id of element from 'intro'.

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $path = core_component::get_plugin_directory("mod", "originality");
         $PAGE->requires->js('/plagiarism/originality/javascript/jquery-3.1.1.min.js');
         $PAGE->requires->js('/mod/quiz/accessrule/originalityquiz/javascript/originalityquiz.js?v=24');
        $str .= "<span style='text-align: left'>";
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
        $str .= "</span>";
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
        return array();
    }







/**
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     * @return bool whether a check is required before the user starts/continues
     *      their attempt.
     */
    public function is_preflight_check_required($attemptid) {
        global $DB, $CFG, $USER;

        $currentURL = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        if (strpos($currentURL,'summary.php') !== false) {

            $cmid = optional_param('cmid', null, PARAM_INT);
            $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);

           // echo "<pre>";
            // print_r($attemptobj->get_question());
            // print_r($attemptobj->get_attempt());
            //echo "<br><br>";
            //print_r($attemptobj->get_active_slots());
            //echo "<br><br>";
           // echo $attemptobj->get_question_attempt(2)->get_id();
           // echo $attemptobj->get_question_attempt(2)->get_id() .' ================ '. $attemptobj->get_question_attempt(2)->get_usageid(); ->get_question_attempt(1) quizobj
           // print_r( $attemptobj->get_uniqueid() );

            $questionsattempts = $DB->get_records('question_attempts', array('questionusageid' => $attemptobj->get_uniqueid()));

            // print_r($questionsattempts);

            foreach ($questionsattempts as $key => $value) {
                $questionsAttemptSteps = $DB->get_records('question_attempt_steps', array('questionattemptid' => $value->id));
                foreach ($questionsAttemptSteps as $key1 => $value1) {
                    $questionsAttemptsData = $DB->get_records('question_attempt_step_data', array('attemptstepid' => $value1->id, 'name' => 'answer'));
                    foreach ($questionsAttemptsData as $key2 => $value2) {
                        //echo $value2->value;
                        $userid = 2;
                        list($origserver, $origkey) = $this->_get_server_and_key();
                        $filename = 'onlinetext-'.$userid.'.txt';
                        $eventdata = array('courseid'=>5,'contextinstanceid'=>$cmid,'userid'=>2,'assignNum'=>712);
                        list($coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum) = $this->_get_params_for_file_submission($eventdata);

                        $uploadresult = $this->_do_curl_request($origserver, $origkey, $value2->value, $filename, $coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum, $fileidentifier);

                       // print_r($uploadresult);

                    }
                }
            }

            // echo '<br><br><br><br>'.$attemptid."ddddd".$cmid; exit();


        }
        //  echo $attemptid."ddddd"; exit();
        // if(!empty($_POST)){ print_r( $_POST ); exit(); }
        return false;
    }

    /**
     * Add any field you want to pre-flight check form. You should only do
     * something here if {@link is_preflight_check_required()} returned true.
     *
     * @param mod_quiz_preflight_check_form $quizform the form being built.
     * @param MoodleQuickForm $mform The wrapped MoodleQuickForm.
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     */
    public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform,
            MoodleQuickForm $mform, $attemptid) {

    }
/**
     * Validate the pre-flight check form submission. You should only do
     * something here if {@link is_preflight_check_required()} returned true.
     *
     * If the form validates, the user will be allowed to continue.
     *
     * @param array $data the submitted form data.
     * @param array $files any files in the submission.
     * @param array $errors the list of validation errors that is being built up.
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     * @return array the update $errors array;
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        // print_r($data); exit();
        return $errors;
    }

    /**
     * The pre-flight check has passed. This is a chance to record that fact in
     * some way.
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     */
    public function notify_preflight_check_passed($attemptid) {
        //echo "dddddddddddd"; exit();
        // Do nothing by default.
    }

    /**
     * This is called when the current attempt at the quiz is finished. This is
     * used, for example by the password rule, to clear the flag in the session.
     */
    public function current_attempt_finished() {
        echo "xxxxxxxxxxxxxxxx"; exit();
        // Do nothing by default.
    }









    /**
     * Get config settings for originality server and key.
     * @return array() - strings
     */
    private function _get_server_and_key() {
        global $DB, $CFG, $USER;
        $origkey = $DB->get_record('config_plugins', array('name' => 'originality_key', 'plugin' => 'plagiarism'));
        $origserver = $DB->get_record('config_plugins', array('name' => 'originality_server', 'plugin' => 'plagiarism'));
        return array($origserver, $origkey);
    }


    /**
     * Set up parameters for assignment submission
     * @param array  $eventdata - data about the event
     * @return array - parameters for upload
     */
    private function _get_params_for_file_submission($eventdata) {
        global $DB, $CFG, $USER;
        $coursenum = $eventdata['courseid'];
        $cmid = $eventdata['contextinstanceid']; // In events2 api, I think this is the course module id (Yael).
        $courseid = $eventdata['courseid'];
        $userid = $eventdata['userid'];
        $inst = "0";
        $coursecategory = null;
        $coursename = null;
        $senderip = get_client_ip();
        // To get course category $coursecategory = $DB->get_field_sql("SELECT name FROM {course_categories} WHERE id = (SELECT category FROM {course} WHERE id = $courseid)");.
        // To get the course name $coursename = $DB->get_field('course','fullname',array('id'=>$courseid));.
        $facultycode = 100;
        $facultyname = 'FacultyName';
        $deptcode = 200;
        $deptname = 'DepartmentName';
        $coursecategory = 'CourseCategory';
        $coursename = 'CourseName';


        // Get lecturer ID.
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        require_once($CFG->libdir. '/coursecatlib.php');
        $tmpCourse = new course_in_list($course);
        if ($tmpCourse->has_course_contacts()) {
            foreach ($tmpCourse->get_course_contacts() as $useridnum => $coursecontact) {
                $lectid = $coursecontact['user']->id;
            }
        }

        $coursecategory = $DB->get_field_sql("SELECT name FROM {course_categories} WHERE id = (SELECT category FROM {course} WHERE id = $courseid)");
        $coursename = $DB->get_field('course','fullname',array('id'=>$courseid));

        $checkfile = '1'; // Indicator whether to check file for plagiarism, for now default is 1.
        $reserve2 = 'Reserve1';
        $groupsize = 1; // In the future set to # of group members submitting the work together.

        // Due to a problem with using the hebrew letter aleph in urls sent to the server, we are using constants for the names and for various other fields.

        $firstname = str_replace(' ', '-', $USER->firstname);
        $lastname = str_replace(' ', '-', $USER->lastname);

        $groupmembers = str_replace(' ', '-', $firstname) . '~' . str_replace(' ', '-', $lastname);



        if (!isset($eventdata['assignNum'])) {
            if ($records = $DB->get_records_menu('course_modules', array('id' => $cmid), '', 'course,instance')) {
                if (isset($records[$coursenum]) && !empty($records[$coursenum])) {
                    $assignnum = $records[$coursenum];
                }
            }
        } else {
            $assignnum = $eventdata['assignNum'];
        }

        $realassignnum = $this->get_real_assignment_number($assignnum);


        return array($coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode,
                     $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum);
    }

    /**
     * Sned curl request to originality server
     * @param list - parameters for upload
     * @return array - boolean and file upload id (from originality server response), or error string
     */
    private function _do_curl_request($origserver, $origkey, $content, $filename, $coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum, $fileidentifier) {
        $content = base64_encode($content);

        $data = array("FileName" => $filename ? $filename : '',
                      "SenderIP" => $senderip ? $senderip : '',
                      "FacultyCode" => $facultycode ? $facultycode : '',
                      "FacultyName" => $facultyname ? $facultyname : '',
                      "DeptCode" => $deptcode ? $deptcode : '',
                      "DeptName" => $deptname ? $deptname : '',
                      "CourseCategory" => $coursecategory ? $coursecategory : '',
                      "CourseCode" => $coursenum ? $coursenum : '',
                      "CourseName" => $coursename ? $coursename : '',
                      "AssignmentCode" => $assignnum ? $assignnum : '',
                      "MoodleAssignPageNo" => $realassignnum ? $realassignnum : '',
                      "StudentCode" => $userid ? $userid : '',
                      "LecturerCode" => $lectid ? $lectid : '',
                      "GroupMembers" => $groupmembers ? $groupmembers : '',
                      "DocSequence" => $fileidentifier ? $fileidentifier : '',
                      "file" => $content ? $content : '',
                    );

        $datawithoutfilecontents = $data;

        $datawithoutfilecontents['file'] = '';

        $data_string = json_encode($data);

        $datawithoutfilecontentsstring = json_encode($datawithoutfilecontents, JSON_UNESCAPED_UNICODE);

        //log everything sending other than the encoded file
        $data['file'] = '';

        $dataLog = 'Uploading file","'.$data['FileName'].'","'.$data['SenderIP'].'","'.$data['FacultyCode'].'","'.$data['FacultyName'].'","'.$data['DeptCode'].'","'.$data['DeptName'].'","'.$data['CourseCategory'].'","'.$data['CourseCode'].'","'.$data['CourseName'].'","'.$data['AssignmentCode'].'","'.$data['MoodleAssignPageNo'].'","'.$data['StudentCode'].'","'.$data['LecturerCode'].'","'.$data['GroupMembers'].'","'.$data['DocSequence'].'","'.$data['file'].'';

        log_it($dataLog);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $origserver->value .'documents',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data_string,
            CURLOPT_HTTPHEADER => array(
                "authorization: " . $origkey->value,
                "cache-control: no-cache",
                "content-type: application/json",
            ),
        ));

        $output = curl_exec($curl);

        $outputarray = json_decode($output, true);

        // print_r($outputarray);

        $err = curl_error($curl);

        curl_close($curl);

        log_it("Uploading originality file ".$outputarray['Id']." curl output: " . strip_tags($output));

        if ($err) {
            $assignmentname = $this->get_assignment_name($assignnum);
            $username = $this->get_user_name($userid);
            log_it('Curl Error: '.$err. '. Client domain : ' . $_SERVER['HTTP_HOST'].
               " for course : $coursename, user $userid : $username and assignment $assignnum : $assignmentname" );
            return array(false, $err);
        } else {
            if (isset($outputarray['Id'])) {
                log_it('Curl output: '.$outputarray['Id']);
                return array(true, $outputarray['Id']);
            }
            else {
                return array(false, 'No File Id returned from curl upload.');
            }
        }
    }
    // Keep unique file identifiers in the requests table per user and assignment so if there are multiple requests not yet answered, when get response in report.php we will know which request it belongs to.

    /**
     * Keep unique file identifiers in the requests table per user and assignment so if there are multiple requests not yet answered, when get response in report.php we will know which request it belongs to.
     * @param list - assignnum and userid
     * @return int
     */
    private function get_unique_id($assignnum, $userid){
      global $DB;
      $maxreqid = $DB->get_record_sql("select max(file_identifier) as maxid from {plagiarism_originality_req} where assignment=? and userid=?", array($assignnum, $userid));
      if ($maxreqid){
          return $maxreqid->maxid+1;
      }else{
          return 1;
      }
    }

    private function get_real_assignment_number($assignnum){
        global $DB;

        $realassignnum = $DB->get_field_sql("SELECT cm.id from {course_modules} cm join {modules} m on m.id = cm.module join {assign} a on a.id = cm.instance WHERE m.name = 'quiz' and a.id = ?", array($assignnum));

        return $realassignnum;
    }
}
