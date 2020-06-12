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

/*
 * Originality Plagiarism Plugin
 * Reprocess and delete requests that were never completely processed.
 * If there are any records left in plagiarism_originality_req then have a page with the list and buttons to delete and resubmit.
 * Last update date: 2017-09-18
 *
 */

/*
Moodle 3.1.7 new file

Simply moved previous functionality from 3.1.6 to this file so now requests.php includes different files based on request.
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}


$inputclientkey = $_GET['clientkey'];

$lib = new plagiarism_plugin_originality();

$hourselapsed = $_GET['hourselapsed'];

$USER = new stdClass();

if (!$hourselapsed || !is_numeric($hourselapsed)){
    echo "Request 4 automatically resubmits upload requests that failed to complete or resulted in an error. It takes a GET parameter called hourselapsed - process unfinished requests whose last submission attempt was over hourselapsed hours ago.";
    exit;
}


$file = delimit_fieldname('file');

$requests = $DB->get_records_sql("select id, userid, assignment, $file, submit_date from ".$CFG->prefix . "plagiarism_originality_req where upload_error = 1");

// In this version 4.0.7 started saving the submit date with the requests. But previous plugin versions don't have it there.
// Also If there is a difference in elapsed days from the original submission and the one stored in the requests table, then it was resubmitted.

$i=0;
if (count($requests) > 0){
  foreach ($requests as $req) {
    $hourselapsedreq = floor((time()-$req->submit_date)/(60*60));

    if ($hourselapsedreq >= $hourselapsed) {
        resubmit_req($req->id, $lib);
        echo "Resubmitting record ".$req->id . "<br />\n";
        $i++;
        // Sleep for 1 second
        sleep(1);
    }
  }
  echo "$i requests with upload errors found<b r/>\n";
}else{
    echo "<h4>No requests with upload error found</h4>";
}


