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
 * plagiarism.php - allows the admin to configure plagiarism stuff
 *
 * @package   plagiarism_originality
 * @author    Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * Last update date: 2017-09-18
 */

/*
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}
*/
require_once(dirname(dirname(__FILE__)) . '/../config.php');

echo $CFG->libdir.'/plagiarismlib.php';
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/originality/lib.php');
require_once($CFG->dirroot.'/plagiarism/originality/plagiarism_form.php');
require_once($CFG->dirroot.'/plagiarism/originality/version.php');

global $plugin;

// Include version and $plugin here because its used in the tab.php file.

require_login();
admin_externalpage_setup('manageplagiarismplugins');

$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

require_once('plagiarism_form.php');

echo $OUTPUT->header();


$mform = new plagiarism_setup_form();
$plagiarismplugin = new plagiarism_plugin_originality();

if ($mform->is_cancelled()) {
    redirect('');
}

$keyok = false;

$plagiarismsettings = (array)get_config('plagiarism');

if (!isset($plagiarismsettings['originality_server'])) {
    $origserver = 'https://www.originality.co.il/rest/v2/api/';
    $tiiconfigfield = new stdClass();
    $tiiconfigfield->value = $origserver;
    $tiiconfigfield->plugin = 'plagiarism';
    $tiiconfigfield->name = 'originality_server';
    if (!$DB->insert_record('config_plugins', $tiiconfigfield)) {
        print_error("errorinserting");
    }
}

if (!isset($plagiarismsettings['server'])) {
    $tiiconfigfield = new stdClass();
    $tiiconfigfield->value = 'live';
    $tiiconfigfield->plugin = 'plagiarism';
    $tiiconfigfield->name = 'server';
    if (! $DB->insert_record('config_plugins', $tiiconfigfield)) {
        print_error("errorinserting");
    }
}


if (($data = $mform->get_data()) && confirm_sesskey()) {

    if (!isset($data->originality_use)) {
        $data->originality_use = 0;
    }
    if (!isset($data->originality_view_report)) {
        $data->originality_view_report = 0;
    }

    foreach ($data as $field => $value) {
        if ($field == 'originality_server') {
            $origserver = $value;
        }
    }

    /*
     * Version 3.1.7 Check permissions of moodledata files folder.
     * From now on storing the originality files there
     */
    $filesdir = $CFG->dataroot . '/originality';
    if (!file_exists($filesdir)) {
        if (!mkdir($filesdir, 0755)) {
            log_it("Error creating the originality directory in moodle data folder");
        }
    } else {
        if (0755 !== (fileperms($filesdir) & 0777)) {
            chmod($filesdir, 0755);
        }
    }

    foreach ($data as $field => $value) {
        if ($field == 'originality_key') {  // Check if key is valid.

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $origserver . "customers/ping",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "authorization: $value",
                    "cache-control: no-cache",
                ),
            ));

            $output = curl_exec($curl);

            $outputarray = json_decode($output, true);

            $err = curl_error($curl);

            curl_close($curl);

            if ($outputarray['Pong'] == 'true') {
                $keyok = true;
                echo $OUTPUT->notification(get_string('savedconfigsuccess', 'plagiarism_originality'), 'notifysuccess');
            } else {
                echo $OUTPUT->notification(get_string('settings_key_error', 'plagiarism_originality'));
                log_it("Settings check key error. Curl Error: $err. Curl Output: " . $outputarray['Pong']);
            }
        }

        if ($field == 'originality_server') {
            continue;
        }


        if (($field == 'originality_key' && $keyok) || ($field != 'originality_key')) {

                if ($tiiconfigfield = $DB->get_record('config_plugins', array('name' => $field, 'plugin' => 'plagiarism'))) {
                        $tiiconfigfield->value = $value;

                    if (! $DB->update_record('config_plugins', $tiiconfigfield)) {
                            print_error("errorupdating");
                    }
                } else {
                        $tiiconfigfield = new stdClass();
                        $tiiconfigfield->value = $value;
                        $tiiconfigfield->plugin = 'plagiarism';
                        $tiiconfigfield->name = $field;

                    if (! $DB->insert_record('config_plugins', $tiiconfigfield)) {
                            print_error("errorinserting");
                    }
                }
        }
    }

    // If the key is not valid, do not enable the plugin

    if (!$keyok){

        if ($tiiconfigfield = $DB->get_record('config_plugins', array('name' => 'originality_use', 'plugin' => 'plagiarism'))) {
            $tiiconfigfield->value = 0;
            // For showing the correct value on the form as the page reloads
            $data->originality_use=0;

            if (!$DB->update_record('config_plugins', $tiiconfigfield)) {
                print_error("errorupdating");
            }
        }
    }

    $plagiarismsettings = (array)get_config('plagiarism');

    $_POST['originality_use'] = $data->originality_use ? 1 : null;

}


/*
 * Clear Database Cache after insert/update plagiarism plugin data
 */
cache_helper::purge_stores_used_by_definition('core', 'databasemeta');

$currenttab = 'originalitysettings';
require_once('tabs.php');

// Get the config again and define a new form that will be for display and preset the POST vars for input fields that we may have reset and won't be the original POST value.
// When the form loads after being submitted the values displayed in the fields are based on the posted values. set_data is just called for the initial load of the form.

$plagiarismsettings = (array)get_config('plagiarism');

$mform2 = new plagiarism_setup_form();

$mform2->set_data($plagiarismsettings);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform2->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();