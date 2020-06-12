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


$mform = new plagiarism_server_form();
$plagiarismplugin = new plagiarism_plugin_originality();

if ($mform->is_cancelled()) {
    redirect('');
}

$keyok = false;

if (($data = $mform->get_data()) && confirm_sesskey()) {

    if ($data->server=='live') {
        $data->originality_server = 'http://40.115.61.181/rest/v2/api/';
    }
    if ($data->server=='test') {
        $data->originality_server = 'http://40.115.61.181/rest/v2/api/';
    }

    foreach ($data as $field => $value) {
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

    $plagiarismsettings = (array)get_config('plagiarism');

    $_POST['originality_server'] = $data->originality_server;

}


/*
 * Clear Database Cache after insert/update plagiarism plugin data
 */
cache_helper::purge_stores_used_by_definition('core', 'databasemeta');

$currenttab = 'originalityserver';
require_once('tabs.php');

// Get the config again and define a new form that will be for display and preset the POST vars for input fields that we may have reset and won't be the original POST value.
// When the form loads after being submitted the values displayed in the fields are based on the posted values. set_data is just called for the initial load of the form.

$plagiarismsettings = (array)get_config('plagiarism');

$mform2 = new plagiarism_server_form();

$mform2->set_data($plagiarismsettings);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform2->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();