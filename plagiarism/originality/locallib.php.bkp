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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Get the client IP address
 * @return string - the ip address
 */
function get_client_ip() {
    $ipaddress = '';

    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 0;
    }
    return $ipaddress;
}


/**
 * Logging function
 * @param string $str - string to write to the log
 */
function log_it($str='') {

    global $CFG, $plugin;

    require($CFG->dirroot . '/plagiarism/originality/version.php');

    $logsdir = $CFG->dataroot . '/originality_logs';

    if (!file_exists($logsdir)) {
        if (!mkdir($logsdir, 0755)) {
            notify_customer_service_did_not_create_logs_dir();
        }
    } else {
        if (0755 !== (fileperms($logsdir) & 0777)) {
            chmod($logsdir, 0755);
        }
    }

    /*
     * For plugin version 4.0.0 don't display the client key in the log file
     */

    $plagiarismsettings = (array)get_config('plagiarism');

    if (!empty($plagiarismsettings['originality_key'])) {
        $clientkey = $plagiarismsettings['originality_key'];
        $str = str_replace($clientkey, str_repeat('X', strlen($clientkey)), $str);
    }

    $logfile = 'originality_' . date('Y-m-d')  . '.csv';

    if (!file_exists($logsdir."/$logfile")) {
        $strNew = '"Date","Release","File","Output","FileName","SenderIP","FacultyCode","FacultyName","DeptCode","DeptName","CourseCategory","CourseCode","CourseName","AssignmentCode","MoodleAssignPageNo","StudentCode","LecturerCode","GroupMembers","DocSequence","file" ' . "\n"; 
        file_put_contents($logsdir."/$logfile", $strNew, FILE_APPEND);
    }


    $str = '"'.date('Y-m-d H:i:s', time() ).'","'.$plugin->release.'","'.basename($_SERVER['PHP_SELF']).'","'.$str.'" ' . "\n"; 
    
           
    if(!file_put_contents($logsdir."/$logfile", $str, FILE_APPEND)){
        notify_customer_log_not_created($str);
    }

}


/**
 * Notify customer service if there was an error creating the logs directory
 */
function notify_customer_service_did_not_create_logs_dir() {

    $to = get_string("originality_customerservice_email", "plagiarism_originality");
    $from = 'notify@'.ltrim($_SERVER['HTTP_HOST'], 'www.');
    $subject = 'Originality: Failed to create logs directory';
    $message = 'Failed to create logs directory for client domain ' . $_SERVER['HTTP_HOST'];
    $headers = "From: $from" . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
}


/**
 * Notify customer service if there was an error creating the logs directory
 */
function notify_customer_log_not_created($str) {

    $to = get_string("originality_customerservice_email", "plagiarism_originality");
    $from = 'notify@'.ltrim($_SERVER['HTTP_HOST'], 'www.');
    $subject = 'Originality: Failed to write log';
    $message = 'Server '.$_SERVER['HTTP_HOST'].' failed to write log : ' . $str;
    $headers = "From: $from" . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
}

/**
 * Notify customer service if there was an error creating the upgrades directory
 */
function notify_customer_service_did_not_create_upgrades_dir() {

    $to = get_string("originality_customerservice_email", "plagiarism_originality");
    $from = 'notify@'.ltrim($_SERVER['HTTP_HOST'], 'www.');
    $subject = 'Originality: Failed to create upgrades directory';
    $message = 'Failed to create upgrades directory for client domain ' . $_SERVER['HTTP_HOST'];
    $headers = "From: $from" . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    mail($to, $subject, $message, $headers);
}

/**
 * Get the latest version number available on the server - the zip for upgrade
 * @param list - strings
 * @return string or null
 */
function get_latest_version_number($origserver, $origkey){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $origserver->value."plugins/versions",
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

    $output = curl_exec($curl);

    $outputarray = json_decode($output, true);

    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        log_it("cURL Error:" . $err." On calling function get_latest_version_number");
        return null;
    } else {
        return $outputarray['version'];
    }
}

/**
 * Checks if the plugin is on a moodle installation using an mssql database
  * @return boolean
 */
function is_mssql_db(){
    global $DB;

    if ($DB->get_dbfamily() == 'mssql'){
        return true;
    }else {
        return false;
    }
}

/**
 * Delimits the field name so that can add brackets when using an mssql database where certain field names are reserved words, like 'file'
 * @return boolean
 */
function delimit_fieldname($field){
    if (is_mssql_db()){
        return "[$field]";
    }else{
        return $field;
    }
}


function resubmit_req($reqid, $lib) {
    global $DB, $CFG, $USER;

    $requests = $DB->get_recordset_sql("select * from ".$CFG->prefix . "plagiarism_originality_req where id=?", array('id' => $reqid));

    if ($requests->current()) {
        $userid = $requests->current()->userid;
        $assignmentid = $requests->current()->assignment;

        log_it("Resubmitting request record id=$reqid for assignment=$assignmentid and user=$userid");

        $courseid = get_course_id($assignmentid);

        $submissionid = get_submission_id($assignmentid, $userid);

        list($origserver, $origkey) = get_server_and_key();

        // ...https://docs.moodle.org/dev/Course_module.
        $course = $DB->get_record('course', array('id' => $courseid));
        $info = get_fast_modinfo($course);

        $list = get_array_of_activities($courseid);

        foreach ($list as $k => $v) {
            if ($v->mod == 'assign' && $v->id == $assignmentid) {
                $cm = $v->cm;
            }
        }

        $eventdata = array();
        // The only thing this is used for is assignment number and I am passing that in directly.
        $eventdata['contextinstanceid'] = $cm;
        $eventdata['objectid'] = $submissionid[0];
        $eventdata['courseid'] = $courseid;
        $eventdata['userid'] = $userid;
        $eventdata['assignNum'] = $assignmentid;

        $USER->id = $userid;

        list($USER->firstname, $USER->lastname) = get_user_first_and_last_name($userid);

        if (strpos($requests->current()->file, 'onlinetext') !== FALSE) {
            $type = 'onlinetext';
        } else {
            $type = 'file';
        }

        if ($type == 'onlinetext'){
            $onlinetextrec = $DB->get_recordset_sql("select onlinetext from ".$CFG->prefix . "assignsubmission_onlinetext where assignment=? and submission=?", array('assignment' => $assignmentid, 'submission' => $submissionid[0]));

            $eventdata['other']['content'] = $onlinetextrec->current()->onlinetext;
            $lib->originality_event_onlinetext_submitted($eventdata);
        } else {
            $lib->originality_event_file_uploaded($eventdata, $requests->current()->moodle_file_id);
        }
    }
}
