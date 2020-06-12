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
 * @original author    Yael Rubel
 * updates by the Originality Group
 * Last update date: 2018-07-15
 */

/*
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}
*/

global $DB, $CFG;


require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/originality/lib.php');
require_once($CFG->dirroot.'/plagiarism/originality/plagiarism_form.php');
require_once($CFG->dirroot . '/plagiarism/originality/locallib.php');

require_once($CFG->dirroot.'/plagiarism/originality/version.php');

global $plugin;

$release = $plugin->release;
$cfgdirroot = $CFG->dirroot;
$cfgdataroot = $CFG->dataroot;

require_login();
admin_externalpage_setup('manageplagiarismplugins');

define('UPGRADES_DIR', $CFG->dataroot . '/originality_upgrades/');
define('CURRENT_PLUGINTYPE_FOLDER', $CFG->dirroot . '/plagiarism/');
define('CURRENT_PLUGIN_FOLDER', $CFG->dirroot . '/plagiarism/originality');
define('BACKUP_CURRENT_PLUGIN_ZIP', UPGRADES_DIR . 'originality_' . $plugin->release . '_' . date('Y-m-d') . '_backup.zip');

// Create the moodle data upgrades folder if it doesn't exist yet.

if (!file_exists(UPGRADES_DIR)) {
    if (!mkdir(UPGRADES_DIR, 0755)) {
        notify_customer_service_did_not_create_upgrades_dir();
        $error = "Error with upgrade. See log for details.";

    }
} else {
    if (0755 !== (fileperms(UPGRADES_DIR) & 0777)) {
        $rc = chmod(UPGRADES_DIR, 0755);
        if (!$rc) {
            log_it_upgrade_ugprade("Did not update permissions on upgrades directory.");
            $error = "Error with upgrade. See log for details.";
        }
    }
}

if (!is_writeable(CURRENT_PLUGIN_FOLDER)) {
    log_it_upgrade("Plugin folder is not writeable");
    $error = "Error with upgrade. See log for details.";
}


//Check whether the online version is a higher version number than the currently installed version

$origkey = $DB->get_record('config_plugins', array('name' => 'originality_key', 'plugin' => 'plagiarism'));
$origserver = $DB->get_record('config_plugins', array('name' => 'originality_server', 'plugin' => 'plagiarism'));

$versionavailable = get_latest_version_number($origserver, $origkey);

$doupgrade = false;

if (version_compare($plugin->release, $versionavailable) === -1) {
    $doupgrade = true;
}


if (!$doupgrade) {
    log_it_upgrade("Number of version on server is not higher than current version number.");
    $error = "Error with upgrade. See log for details.";
}


$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

require_once('plagiarism_form.php');


$mform = new plagiarism_upgrade_form();

$plagiarismplugin = new plagiarism_plugin_originality();

if ($mform->is_cancelled()) {
    redirect('');
}

if (!isset($error)) {
    if (($data = $mform->get_data()) && confirm_sesskey() && $doupgrade) {

        if (!is_writable(CURRENT_PLUGINTYPE_FOLDER)) {
            log_it_upgrade("Plugin folder not writeable");
            $error = "Plagiarism Folder is not writeable";
        } else {
            log_it_upgrade("Downloading new version zip file");

            $newzipfile = download_new_version($origserver, $origkey, $versionavailable);

            if (!$newzipfile) {
                $error = "Error with upgrade. See log for details.";
                log_it_upgrade("Error downloading new version zip file.");
            } else {
                log_it_upgrade("Unzipping new version zip file");
                // Unzipping the new version.
                $rc = unzip_new_version($newzipfile, UPGRADES_DIR);

                if (!$rc) {
                    log_it_upgrade("Error unzipping the new version file.");
                    $error = "Error with upgrade. See log for details.";
                } else {
                    log_it_upgrade("Creating zip backup of plugin folder.");
                    // Zip and back up the original plugin file.
                    $rc = create_backup(BACKUP_CURRENT_PLUGIN_ZIP, CURRENT_PLUGIN_FOLDER);

                    if (filesize(BACKUP_CURRENT_PLUGIN_ZIP) > 0) {

                        log_it_upgrade("Moving plugin folder to upgrades directory.");
                        $rc = rename(CURRENT_PLUGIN_FOLDER, UPGRADES_DIR . 'originality_backup' . date('Y-m-d_H:i:s'));

                        if (!$rc) {
                            log_it_upgrade("Error moving the current plugin to the upgrades directory.");
                            $error = "Error with upgrade. See log for details.";
                        } else {
                            log_it_upgrade("Moving the new version of the plugin to the plagiarism directory.");
                            // Copy the new plugin to the original location.
                            $rc = rename(UPGRADES_DIR . 'originality', CURRENT_PLUGIN_FOLDER);

                            if (!$rc) {
                                log_it_upgrade("Error moving the new plugin to the plagiarism directory.");
                                $error = "Error with upgrade. See log for details.";
                            } else {
                                chmod_r(CURRENT_PLUGINTYPE_FOLDER, 0755, 0644);
                                redirect(new moodle_url('/admin'));
                            }
                        }
                    } else {
                        log_it_upgrade("Error creating backup file." . implode("\n", $output));
                        $error = "Error with upgrade. See log for details.";
                    }
                }
            }
        }
    }
}

echo $OUTPUT->header();

$currenttab = 'originalityupgrade';
require_once('tabs.php');


echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');


if (isset($error)){
    echo $OUTPUT->notification($error);
}else {
    if ($doupgrade){
      $mform->display();
    }
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

function download_new_version($origserver, $origkey, $versionavailable){
    $curl = curl_init();

    $zipfile = UPGRADES_DIR . 'originality_' .$versionavailable . '.zip';

    curl_setopt_array($curl, array(
        CURLOPT_URL => $origserver->value . "plugins",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: " . $origkey->value,
            "cache-control: no-cache",
        ),
    ));

    $output = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    file_put_contents($zipfile, $output);

    if ($err) {
        log_it_upgrade("cURL Error:" . $err);
        return null;
    } else {
        if (filesize($zipfile)) return UPGRADES_DIR . 'originality_' .$versionavailable . '.zip';
        else return null;
    }
}


function create_backup($zipfile, $pathname){
    $zip = new ZipArchive();

    if ($zip->open($zipfile, ZIPARCHIVE::CREATE) !== TRUE) {
        return false;
    }

    $all= new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathname));

    foreach ($all as $f=>$value) {
        if (file_exists($f) && is_file($f)) {
            $rc = $zip->addFile(realpath($f), $f);
            if (!$rc) {
                log_it_upgrade("Error adding moodle file $f to backup zip file.");
                return false;
            }
        }
    }
    $zip->close();
    return true;
}

function unzip_new_version($newzipfilename, $pathname){
    $zip = new ZipArchive;
    if ($zip->open($newzipfilename) === TRUE) {
        $zip->extractTo($pathname);
        $zip->close();
        return true;
    } else {
        return false;
    }
}

function chmod_r($dir, $dirPermissions, $filePermissions) {
    $dp = opendir($dir);
    while($file = readdir($dp)) {
        if (($file == ".") || ($file == ".."))
            continue;

        $fullPath = $dir."/".$file;

        if(is_dir($fullPath)) {
            //echo('DIR:' . $fullPath . "\n");
            chmod($fullPath, $dirPermissions);
            chmod_r($fullPath, $dirPermissions, $filePermissions);
        } else {
            //echo('FILE:' . $fullPath . "\n");
            chmod($fullPath, $filePermissions);
        }

    }
    closedir($dp);
}


// Since this script folder gets moved during execution there seems to be an issue using the main log_it function so creating a new one here for this script.
function log_it_upgrade($str='') {

    global $release, $cfgdirroot, $cfgdataroot;

    $logsdir = $cfgdataroot . '/originality_logs';

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

    $logfile = 'originality_' . date('Y-m-d')  . '.log';

    $str = date('Y-m-d H:i:s', time() )  . " release: " . $release .
        "  " .basename($_SERVER['PHP_SELF']) .": " . $str. "\n";
    file_put_contents($logsdir."/$logfile", $str, FILE_APPEND);

}

