﻿<?php
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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since      2.0
 * @package    plagiarism_originality
 * @subpackage plagiarism
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @LastUpdateDate: 2017-09-18
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // Script must be included from a moodle page.
}

// Get global class.
global $CFG, $USER;
require_once($CFG->dirroot . '/plagiarism/lib.php');
require_once($CFG->dirroot . '/plagiarism/originality/locallib.php');

class plagiarism_plugin_originality extends plagiarism_plugin {

    /**
     * hook to allow plagiarism specific information to be displayed beside a submission
     * @param $linkarray - array contains all relevant information for the plugin to generate a link
     * @return string
     */
    public function get_links($linkarray) {
        global $DB, $USER, $COURSE, $OUTPUT, $CFG, $PAGE;

        $output = ''; // Added by openapp, solves "undefined variable".
        $displaynone = '';

        $plagiarismsettings = (array)get_config('plagiarism');
        $adminallowsstudentviewreport = $plagiarismsettings['originality_view_report'];
        $select = 'cm = ?';
        $selectclause = '';

        $originalityname = get_string("originality_shortname", "plagiarism_originality");

        if ($originalityuse = $DB->get_record_select('plagiarism_originality_conf', $select, array($linkarray['cmid']))) {
            $teacherallowsstudentviewreport = $originalityuse->student_view_report;
        } else {
            return;
        }
        $userroles = current(get_user_roles($PAGE->context, $USER->id));

        if ($userroles) {
            // Note: can also use the function user_has_role_assignment with parameters:
            // $USER->id, 5, $linkarray['cmid']. 5 refers to a student.
            $isstudent = $userroles->shortname == 'student' ? true : false;
            if ($isstudent) {
                if (!($adminallowsstudentviewreport && $teacherallowsstudentviewreport)) {
                    return;
                }
            }
        }

        if (isset($linkarray['file'])){
            $file = $linkarray['file'];
            $fileid =  $file->get_id();
	    
  	        $filename = $file->get_filename();
	        $info = pathinfo($filename);

            if (!in_array($info['extension'], $this->allowed_file_extensions())){
                $output = '<div class="plagiarismreport" dir="rtl" ' . $displaynone . '>'. $originalityname . ':';
                $output .= get_string("originality_unprocessable", "plagiarism_originality");
                $output .= '</div>'; 
		        return $output;
            }    
        }

        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $select = 'id = ' . $cmid;
        $ins = $DB->get_record_select('course_modules', $select, null, '*', IGNORE_MULTIPLE);

        $originalityname = get_string('originality_shortname', 'plagiarism_originality');

        // Changed by openapp, userid condition should be done only when isset.
        $select = 'assignment = ' . $ins->instance;
        if (isset($userid)) {
            $select .= ' AND userid =' . $userid;
        }
	
	    if (isset($fileid)) { // A file submission.
            $selectclause = ' AND moodle_file_id = ' . $fileid;
            // Check if there is a response record.
            $resp = $DB->get_record_select('plagiarism_originality_resp', $select . $selectclause, null, '*', IGNORE_MULTIPLE);
            // If there is no response for the file id, then check if there is a response with a value of 0 for fileid
            if (!$resp){  // For backwards compatility - assignments with responses from before an upgrade to 4.0.5 which don't have a file id. The assumption is that there is just one submission and the file id is 0 in the responses table.
                // From version 5.0.1 also for when Eli resends reports from the server the moodle file id would be set to 0 in report.php when there are no request records.
                $selectclause2 = ' AND moodle_file_id = 0';
                $resp = $DB->get_record_select('plagiarism_originality_resp', $select . $selectclause2, null, '*', IGNORE_MULTIPLE);
            }
  	    } else {  // Online text.
	        $selectclause = ' AND moodle_file_id = -1';
            $resp = $DB->get_record_select('plagiarism_originality_resp', $select . $selectclause, null, '*', IGNORE_MULTIPLE);
        }

        if ($resp) {
            $grade = $resp->grade;

            if ($grade > 950) {
                $grade = get_string("originality_unprocessable", "plagiarism_originality");
            } else {
                $grade  = $grade . '%';
            }

            /*
             * Version 3.1.7 Check permissions of moodledata files folder.
             * From now on storing the originality files there
             * updated on 2017-09-18
             */

            $filesdir = $CFG->dataroot . '/originality/';

            $path = $CFG->wwwroot .'/plagiarism/originality/show_report.php?file=' . $ins->instance . '/' . $resp->file;

            $respfile = $resp->file;
            $respfilearray = explode('.', $respfile);
            $extension = end($respfilearray);
            $icon = $OUTPUT->pix_url(file_extension_icon(".$extension"))->out();
            $image = html_writer::empty_tag('img', array('src' => $icon, 'style' => 'width:16px;'));
            $output = '<div class="plagiarismreport" dir="rtl" ' . $displaynone . '>' . $originalityname . ':';
            $output .= $grade.'&nbsp;&nbsp;';
            if (file_exists($filesdir.$ins->instance."/". $resp->file) && $resp->grade <= 950) {
                $output .= '<a href="' . $path . '" target="_blank" style="text-decoration:underline">'.$image.'</a>' .
                    '</div>';
            }
        } else {
            $req = $DB->get_record_select('plagiarism_originality_req', $select . $selectclause, null, '*', IGNORE_MULTIPLE);

            // If there is an upload error show in transit messsage.
            // If no upload error show in process message.
            // If no request record at all show unprocessable record


            if ($req) {
                if ($req->upload_error == 1) {
                    $output = '<div class="plagiarismreport" dir="rtl" ' . $displaynone . '>'. $originalityname . ':';
                    $output .= get_string("originality_waitingprocessmsg", "plagiarism_originality");
                    $output .= '</div>';
                } else{
                    $output = '<div class="plagiarismreport" dir="rtl" ' . $displaynone . '>'.$originalityname. ':';
                    $output .= get_string("originality_inprocessmsg", "plagiarism_originality");
                    $output .= '</div>';
                }
            }else{
                    $output = '<div class="plagiarismreport" dir="rtl" ' . $displaynone . '>'. $originalityname . ':';
                    $output .= get_string("originality_unprocessable", "plagiarism_originality");
                    $output .= '</div>';
            }
        }
        $output .= '<hr />';
        $plagiarismsettings = (array)get_config('plagiarism');
        $select = 'cm = ?';

        if (!empty($plagiarismsettings['originality_use'])) {
            if (!$originalityuse = $DB->get_records_select('plagiarism_originality_conf', $select, array($cmid))) {
                return;
            }
        } else {
                return;
        }
        return $output;
    }


    // Creates a compressed zip file DISABLED, not required by customers.
    public static function create_zip($files = array(), $destination = '', $overwrite = false) {

    }

    /*
     *Hook to save plagiarism specific settings on a module settings page.
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB, $USER;
        $plagiarismsettings = (array)get_config('plagiarism');

        if (!empty($plagiarismsettings['originality_use'])) { // This is the admin setting.
            $setting = array('Yes', 'No');

            $select = 'cm = ?';

            // This is the lecturer setting.
            if ($originalityuse = $DB->get_record_select('plagiarism_originality_conf', $select, array($data->coursemodule))) {
                $DB->delete_records_select('plagiarism_originality_conf', $select, array($data->coursemodule));
            }
            if (isset($data->originality_use)) {
                if ($data->originality_use != 0) {
                    $newelement = new stdClass();
                    $newelement->cm = $data->coursemodule;
                    $newelement->student_view_report  = $data->student_view_report;
                    $DB->insert_record('plagiarism_originality_conf', $newelement);
                    
                    log_it("Update : Originality setting to '".$setting[$_POST['originality_use']]."' by user ".$USER->id." for Course Id ".$_POST['course'].", Assignment Id ".$_POST['coursemodule']." " );
                } /* else {
                    $select = 'cm = ?';

                    if ($originalityuse = $DB->get_record_select('plagiarism_originality_conf', $select, array($data->coursemodule))) {
                        $DB->delete_records_select('plagiarism_originality_conf', $select, array($data->coursemodule));
                    }
                }*/
            }
        }
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */

    // Added by openapp - param $modulename.
    public function get_form_elements_module($mform, $context, $modulename='plagiarism') {
        global $DB;
        global $PAGE;
        global $CFG;
        global $USER;



        // Only show settings for assignments.
        if ($PAGE->pagetype != 'mod-assign-mod') {
            return;
        }

        //check whether on add assignment to set use originality to yes as the default, depends on the particular college setting on the server.
        $defaultuseoriginality = $this->default_assignment_settings_use_originality();

        $add = optional_param('add', '', PARAM_TEXT);
        if ($add) {
            if ($defaultuseoriginality) { // If there is no record its the first time and set depending on the status of the college.
                $mform->setDefault('originality_use', 1);
            }
        }

        $plagiarismsettings = (array)get_config('plagiarism');
        $adminallowsstudentviewreport = $plagiarismsettings['originality_view_report'];

        $PAGE->requires->js('/plagiarism/originality/javascript/jquery-3.1.1.min.js');
        $PAGE->requires->js('/plagiarism/originality/javascript/inter-assignment.js?v=12');

        // If there are existing submissions then javascript will check if changing setting for originality from no to yes, it will notify the teacher about the items already submitted.
        if (isset($_GET['update'])){
            $has_submissions_notifications = $this->assignment_has_submissions($_GET['update']);

            if ($has_submissions_notifications){
                $has_submissions_notifications = get_string('originality_previous_submissions', 'plagiarism_originality');
                $mform->addElement('html', "<div id='assignment_has_submissions_notifications' style='display:none;'>$has_submissions_notifications</div>");
            }
        }

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


    /**
     * Count how many submissions an assignment has
     * @param int $assignnum - assignment number
     * @return integer or null
     */
    function assignment_has_submissions($assignnum){
        global $DB;
        $submissions = $DB->get_record_sql("select count(*) as num from {assign_submission} where assignment=?", array($assignnum));
        if ($submissions && $submissions->num > 0){
            return 1;
        }else{
            return null;
        }
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     * @param int $cmid - course module id
     * @return string
     */

    public function print_disclosure($cmid) {
        global $OUTPUT, $PAGE, $DB;

        if ($PAGE->pagetype == 'mod-forum-post') {
            return;
        }

        $plagiarismsettings = (array)get_config('plagiarism');
        $select = 'cm = ?';

        if (!empty($plagiarismsettings['originality_use'])) {
            if (!$originalityuse = $DB->get_records_select('plagiarism_originality_conf', $select, array($cmid))) {
                return;
            }
        }

        $str = $OUTPUT->box_start('generalbox boxaligncenter', 'intro-originality'); //  2016-01-01 Changed id of element from 'intro'.

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $path = core_component::get_plugin_directory("mod", "originality");

        $PAGE->requires->js('/plagiarism/originality/javascript/jquery-3.1.1.min.js');
        $PAGE->requires->js('/plagiarism/originality/javascript/inter.js?v=24');
        $str .= format_text(get_string("originalitystudentdisclosure", "plagiarism_originality"), FORMAT_MOODLE, $formatoptions);

        // Ben Gurion University requested an additional statement here.
        $bgu_addition = '';
        list($origserver, $origkey) = $this->_get_server_and_key();

        if (strpos($origkey->value, 'BGU19523') !== FALSE) {   //Note: Actually Idan wants to change this to be something else or an api call b.c. he doesn't want everyone seeing the bgu key.
            $bgu_addition = '<br />'. get_string('agree_checked_bgu', 'plagiarism_originality');
        }

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
        return $str;
    }


    /**
     * Hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */

    public function update_status($course, $cm) {
        // Calls debugging("called");.
        // Called at top of submissions/grading pages - allows printing of admin style links or updating status.
    }


    // Called by admin/cron.
    public function cron() {
        // Do any scheduled task stuff.
    }

    /**
     * Filter method for only allowing uploads of files with specific extensions
     * @return array of strings
     */
    public function allowed_file_extensions(){
        return array('txt', 'rtf', 'doc', 'docx', 'pdf');
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

        $err = curl_error($curl);

        curl_close($curl);

        log_it("Uploading file curl output: " . strip_tags($output));

        if ($err) {
            log_it('Curl Error: '.$err);
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

    /**
     * Get actual assignment number
     * @param $assignnum int - the assignment number
     * @return $realassignnum - the course module id
     */
    private function get_real_assignment_number($assignnum){
        global $DB;

        $realassignnum = $DB->get_field_sql("SELECT cm.id from {course_modules} cm join {modules} m on m.id = cm.module join {assign} a on a.id = cm.instance WHERE m.name = 'assign' and a.id = ?", array($assignnum));

        return $realassignnum;
    }

    /**
     * Add request record to the originality requests table
     * @param list - info to store about the request
     */
    private function _add_request_record($assignnum, $userid, $filename, $fileidentifier, $moodlefileid, $uploadresult) {
        global $DB, $CFG, $USER;

        $file = delimit_fieldname('file');

        $newelement = new stdClass();
        $newelement->assignment = $assignnum;
        $newelement->userid = $userid;
        $newelement->$file = $filename;
        $newelement->file_identifier =$fileidentifier;
        $newelement->moodle_file_id = $moodlefileid;
        $newelement->upload_error = $uploadresult ? 0 : 1;
        $newelement->submit_date = time();

        $res = $DB->insert_record('plagiarism_originality_req', $newelement);

        if (!$res) {
            log_it("Error adding request record");
        }

        if (is_mssql_db()) {
            $DB->set_field_select('plagiarism_originality_req', '[file]', $filename, 'id=?', array($res));
        }
    }

    /**
     * Delete any existing request and response records
     * @param list - userid, assignment number and type
     */
    private function _delete_existing_request_and_response_records($userid, $assignnum, $type){
        global $DB;

        $file = delimit_fieldname('file');

        if ($type == 'file'){
            //$DB->delete_records('plagiarism_originality_req', array("assignment" => $assignnum, 'userid' => $userid));
                $DB->delete_records_select('plagiarism_originality_req', "assignment = ? and userid = ? and " . $DB->sql_like("$file", '?', true, true, true), array("assignment" => $assignnum, 'userid' => $userid, '%onlinetext%'));
        }
        if ($type == 'onlinetext'){
            //$DB->delete_records('plagiarism_originality_req', array("assignment" => $assignnum, 'userid' => $userid));
            $DB->delete_records_select('plagiarism_originality_req', "assignment = ? and userid = ? and " . $DB->sql_like("$file", '?'),  array("assignment" => $assignnum, 'userid' => $userid, '%onlinetext%'));
        }

        $DB->delete_records('plagiarism_originality_resp', array("assignment" => $assignnum, 'userid' => $userid));
    }


    /**
     * Delete any existing request and response records
     * @param list - userid, assignment number and type
     */
    private function _delete_existing_request_and_response_record($userid, $assignnum, $resubmitmoodlefileid){
        global $DB;

        $DB->delete_records('plagiarism_originality_req', array("assignment" => $assignnum, 'userid' => $userid, "moodle_file_id" => $resubmitmoodlefileid));
        $DB->delete_records('plagiarism_originality_resp', array("assignment" => $assignnum, 'userid' => $userid, "moodle_file_id" => $resubmitmoodlefileid));
    }



    /**
     * Catches file submission event and uploads file(s) to the originality server
     * @param list - eventdata, and resubmitmoodlefileid
     * @return boolean
     */
    public function originality_event_file_uploaded($eventdata, $resubmitmoodlefileid=null) {
        $result = true;
        global $DB, $CFG, $USER;

        $plagiarismvalues = $DB->get_records_menu('plagiarism_originality_conf', array('cm' => $eventdata['contextinstanceid']), '', 'id,cm');

        if ($resubmitmoodlefileid) {
            log_it("This is a resubmit from requests: resubmit moodle file id: $resubmitmoodlefileid");
        }

        if (empty($plagiarismvalues)) {
            log_it("No plagiarism values, returning.");
            return $result;
        } else {
            list($origserver, $origkey) = $this->_get_server_and_key();
            $modulecontext = context_module::instance($eventdata['contextinstanceid']);
            $fs = get_file_storage();

            list($coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum) = $this->_get_params_for_file_submission($eventdata);

            if (isset($resubmitmoodlefileid)){
                $this->preprocess_file_upload($eventdata, $resubmitmoodlefileid);
            } else {
                $this->preprocess_submissions($eventdata, 'file');
            }

            $fileidentifier = $this->get_unique_id($assignnum, $userid);

        /*
         * Version 3.1.7, adding a file identifier so more than one file can be submitted, for example an assignment that allows
         * both online text and file uploads.
         */
            if ($files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file', 'submission_files', $eventdata['objectid'])) {
                foreach ($files as $file) {
                    if ($file->get_filename() === '.') {
                        continue;
                    }
                    $fileid = $file->get_id();

                    log_it("The Moodle file id is $fileid");

                    if (isset($resubmitmoodlefileid) && $fileid != $resubmitmoodlefileid) {  // Only resubmit the file being resubmitted from requests1 and not all files of the submission.
                        log_it("Resubmit is set (coming from requests.php 1 or 4 and the file id is not the same as the resubmitfileid. Continue.");
                        continue;
                    }

                    $filename = trim($file->get_filename());

                    $filename = preg_replace("/[^א-תα-ωΑ-Ωa-zA-Z0-9_\.\s]/u", '', $filename);

                    $coursename = preg_replace("/[^א-תα-ωΑ-Ωa-zA-Z0-9_\.\s]/u", '', $coursename);

                    $deptname = preg_replace("/[^א-תα-ωΑ-Ωa-zA-Z0-9_\.\s]/u", '', $deptname);

                    $facultyname = preg_replace("/[^א-תα-ωΑ-Ωa-zA-Z0-9_\.\s]/u", '', $facultyname);

                    $coursecategory = preg_replace("/[^א-תα-ωΑ-Ωa-zA-Z0-9_\.\s]/u", '', $coursecategory);

                    $info = pathinfo($filename);

                    if (!in_array($info['extension'], $this->allowed_file_extensions())){
                        continue;
                    }

                    $filename = basename($filename, '.'.$info['extension']);

                    if (strlen($filename) > 40) {
                        $filename = mb_substr($filename, 0, 40);
                    }
                    $filename = $filename . '.' . $info['extension'];

                    $content = $file->get_content();

                    //================================== PARAMS ADDED BY ORIGINALITY LTD =========================================
                    if (!empty($origserver->value) && !empty($origkey->value)) {
                        log_it("Upload :  Retrieved the server ".$origserver->value." and key successfully");
                        $uploadresult = $this->_do_curl_request($origserver, $origkey, $content, $filename, $coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum, $fileidentifier);

                       // Upload result is a unique file id on the server.

                        log_it("Adding request record: assignment: $assignnum, user: $userid, filename: $filename, fileidentifer: $fileidentifier, fileid: $fileid");
                        $this->_add_request_record($assignnum, $userid, $filename, $fileidentifier, $fileid, $uploadresult[0]);

                        if (!$uploadresult[0]) {
                            log_it("Upload failed. Notifying customer service via email. Error: " . $uploadresult[1]);
                            $this->notify_customer_service_failed_file_upload($userid, $assignnum, $uploadresult[1]);
                        }
                    }
                    $fileidentifier++;
                }
            }
        }
        return $result;
    }

    /**
     * Nofity customer service when a file upload fails
     * @param list - userid, assignment number and reason
     */
    private function notify_customer_service_failed_file_upload($userid, $assignnum, $reason= '') {
        $assignmentname = $this->get_assignment_name($assignnum);
        $username = $this->get_user_name($userid);

        $to = get_string("originality_customerservice_email", "plagiarism_originality");
        $from = 'notify@'.ltrim($_SERVER['HTTP_HOST'], 'www.');
        $subject = 'Originality: Failed File Upload';
        $message = 'File upload failed from client domain: ' . $_SERVER['HTTP_HOST'].
               " for user $userid:$username and assignment $assignnum:$assignmentname. <br /><br />\nReason: $reason.";
        $headers = "From: $from" . "\r\n" .
            "Content-type: text/html; charset=utf-8"." \r\n";
            'X-Mailer: PHP/' . phpversion();
        $rc = mail($to, $subject, $message, $headers);
    }


    /**
     * Get assignment name
     * @param $id - int
     * @return string
     */
    private function get_assignment_name($id) {
        global $DB;
        $assignment = $DB->get_record('assign', array('id' => $id));
        return $assignment->name;
    }

    /**
     * Get user name
     * @param $id - int
     * @return string
     */
    private function get_user_name($id) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $id));
        return $user->firstname . ' ' . $user->lastname;
    }

    /**
     * Catch files done event
     * @param $eventdata - array of event data
     */
    public function originality_event_files_done($eventdata) {
        global $DB;
        $result = true;
    }

    /**
     * Catch online text submitted event
     * @param $eventdata - array of event data
     * @return boolean
     */
    public function originality_event_onlinetext_submitted($eventdata) {
        $result = true;
        global $DB, $CFG, $USER;

        log_it("In originality_event_onlinetext_submitted method");

        $plagiarismvalues = $DB->get_records_menu('plagiarism_originality_conf', array('cm' => $eventdata['contextinstanceid']), '', 'id,cm');

        if (empty($plagiarismvalues)) {
           log_it("No plagiarism values, returning.");
           return $result;
        } else {
            list($origserver, $origkey) = $this->_get_server_and_key();

            list($coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum) = $this->_get_params_for_file_submission($eventdata);

            $filename = 'onlinetext-'.$userid.'.txt';
            $content = strip_tags($eventdata['other']['content']);

            if (!$content){
                log_it("No text content. Returning");
                return;
            }

            $fileidentifier = $this->get_unique_id($assignnum, $userid);

            log_it("File Identifier: $fileidentifier");

            $this->preprocess_submissions($eventdata, 'onlinetext');

                 // PARAMS ADDED BY ORIGINALITY LTD.
            if (!empty($origserver->value) && !empty($origkey->value)) {
                $uploadresult = $this->_do_curl_request($origserver, $origkey, $content, $filename, $coursenum, $cmid, $courseid, $userid, $inst, $lectid, $coursecategory, $coursename, $senderip, $facultycode, $facultyname, $deptcode, $deptname, $checkfile, $reserve2, $groupsize, $groupmembers, $assignnum, $realassignnum, $fileidentifier);
                // Upload result is a unique file id on the server.

                    log_it("Adding request record: assignment: $assignnum, user: $userid, filename: $filename, fileidentifer: $fileidentifier");

                    $this->_add_request_record($assignnum, $userid, $filename, $fileidentifier, -1, $uploadresult[0]);

                if (!$uploadresult[0]) {
                    log_it("Upload failed. Notifying customer service via email. Error: " . $uploadresult[1]);
                    $this->notify_customer_service_failed_file_upload($userid, $assignnum, $uploadresult[1]);
                }
            }//if have orig server value
        }
}


    /**
     * Check the default setting for the school for whether to have the lecturer definitions for the course have
     * originality on as the default
     * @return boolean
     */
    private function default_assignment_settings_use_originality() {
        global $DB, $CFG, $USER;

        list($origserver, $origkey) = $this->_get_server_and_key();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $origserver->value."customers/status",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: ".$origkey->value,
                "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            log_it("Status Check error:  $err");
            return false;
        } else {
            $values = json_decode($response, true);

            if (isset($values['SendByDefault']) && $values['SendByDefault'] == true) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Catch created event
     * @param $eventdata - array of event data
     */
    public function originality_event_mod_created($eventdata) {
        $result = true;
        return $result;
    }

    /**
     * Catch updated event
     * @param $eventdata - array of event data
     */
    public function originality_event_mod_updated($eventdata) {
        $result = true;
        return $result;
    }

    /**
     * Catch deleted event
     * @param $eventdata - array of event data
     */
    public function originality_event_mod_deleted($eventdata) {
        $result = true;
        return $result;
    }

    /**
     * Delete any previous data for a submission like any existing request and response records so as to have a clean slate for a new submission
     * @param list - event data and type (file or online text)
     */
    public function preprocess_submissions($eventdata, $type){
        global $DB;
        $cmid = $eventdata['contextinstanceid']; // In events2 api, I think this is the course module id (Yael).
        $userid = $eventdata['userid'];
        $coursenum = $eventdata['courseid'];

        if (!isset($eventdata['assignNum'])) {
            if ($records = $DB->get_records_menu('course_modules', array('id' => $cmid), '', 'course,instance')) {
                if (isset($records[$coursenum]) && !empty($records[$coursenum])) {
                    $assignnum = $records[$coursenum];
                }
            }
        } else {
            $assignnum = $eventdata['assignNum'];
        }

        if ($type == 'file'){
            $this->_delete_existing_request_and_response_records($userid, $assignnum, 'file');
        }
        if ($type == 'onlinetext'){
            $this->_delete_existing_request_and_response_records($userid, $assignnum, 'onlinetext');
        }

    }


    /**
     * Delete any previous data for a specific file, the request and response records
     * @param list - event data and type (file or online text)
     */
    public function preprocess_file_upload($eventdata, $resubmitmoodlefileid){
        global $DB;
        $cmid = $eventdata['contextinstanceid']; // In events2 api, I think this is the course module id (Yael).
        $userid = $eventdata['userid'];
        $coursenum = $eventdata['courseid'];

        if (!isset($eventdata['assignNum'])) {
            if ($records = $DB->get_records_menu('course_modules', array('id' => $cmid), '', 'course,instance')) {
                if (isset($records[$coursenum]) && !empty($records[$coursenum])) {
                    $assignnum = $records[$coursenum];
                }
            }
        } else {
            $assignnum = $eventdata['assignNum'];
        }
            $this->_delete_existing_request_and_response_record($userid, $assignnum, $resubmitmoodlefileid);  // Delete the specific request record for the resubmission.
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
}

