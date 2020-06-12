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
 *
 * @package   plagiarism_originality
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Ανιχνευτής Λογοκλοπής Originality';
$string['studentdisclosuredefault'] = 'Όλα τα αρχεία που ανεβάζετε θα υποβληθούν στην υπηρεσία ανίχνευσης λογοκλοπής Originality';
$string['originalitystudentdisclosure'] = 'Αυτή η εργασία είναι πραγματικά δική μου, είναι γραμμένη από εμένα και αναλαμβάνω την πλήρη ευθύνη για την αυθεντικότητά της.<br>Αυτή η εργασία είναι δική μου, με εξαίρεση εκεί όπου παραπέμπω σε έργα άλλων. <br>';
$string['studentdisclosure'] = 'Γνωστοποίηση Originality προς τους Φοιτητές';
$string['studentdisclosure_help'] = 'Αυτό το κείμενο θα εμφανίζεται στη σελίδα φόρτωσης αρχείων εργασιών των φοιτητών.';
$string['originalityexplain'] = 'Ρυθμίσεις για το plugin του ανιχνευτή λογοκλοπής Originality';
$string['originality'] = 'plugin του ανιχνευτή λογοκλοπής Originality';
$string['useoriginality'] = 'Ενεργοποίησε το Originality';
$string['savedconfigsuccess'] = 'Οι Ρυθμίσεις Λογοκλοπής Καταχωρήθηκαν ';
$string['useoriginality'] = 'Ενεργοποίησε το Originality';
$string['originality_help'] = 'Το Originality είναι εργαλείο ανίχνευσης λογοκλοπής.';
$string['originality_key'] = 'Κλειδί Originality';
$string['originalitykey'] = 'Κλειδί Originality';
$string['originalitykey_help'] = 'Πρέπει να προμηθευτείτε ένα Κλειδί του Originality για να χρησιμοποιήσετε αυτό το plugin';
$string['originality_server'] = 'Εξυπηρετητής Originality';

$string['originalityserver_help'] = 'Καταχωρήστε την διεύθυνση IP του Originality με http ή https';
// ... $string['originality_api'] = ""$orig_server->value/Api/$orig_key->value/SubmitDocument/" .
$string['originality_view_report'] = 'Επιτρέψτε στο φοιτητή να δει το αποτέλεσμα του ελέγχου';
$string['originalityviewreport_help'] = 'Επιτρέψτε στο φοιτητή να δει το αποτέλεσμα του ελέγχου';

$string['savedconfigsuccess'] = 'Οι ρυθμίσεις καταχωρήθηκαν επιτυχώς';
$string['savedconfigfailed'] = 'Το κλειδί API που δώσατε είναι λανθασμένο παρακαλώ ξαναδοκιμάστε (το plugin είναι ανενεργό)';

$string['agree_checked'] = "Γνωρίζω και αποδέχομαι πλήρως την εξέταση αυτής της εργασιας από το 'Originality Group' προκειμένου να ελεγχθεί για λογοκλοπή, όπως επίσης και τους <a rel='external' href='https://www.originality.co.il/termsOfUse.html' target='_blank' style='text-decoration:underline'>όρους αυτού του ελέγχου</a>.";

$string['agree_checked_bgu'] = "Γνωρίζω ότι το Πανεπιστήμιο έχει το δικαίωμα να ελέγξει μέσω του Originality - μιας εφαρμογή για την ανίχνευση λογοκλοπής - την εργασία που υποβάλλω για τις εξετάσεις μου";

$string['originality_fileextmsg'] =  "Επιτρέπονται μόνο αρχεία με τις ακόλουθες επεκτάσεις:";

$string['originality_inprocessmsg'] = "Υπό επεξεργασία";

$string['originality_waitingprocessmsg'] = "Σε αναμονή";

$string['originality_info'] = "Πληροφορίες Originality";

$string['originality_settings'] = "Ρυθμίσεις Originality";

$string['originality_upgrade'] = "Αναβάθμιση του Originality";

$string['originality_no_upgrade'] = "Το plugin είναι ενήμερο";

$string['originality_new_version_available'] = "Μια νέα έκδοση του Originality είναι διαθέσιμη. Θέλετε ξεκινήσετε την αναβάθμιση;";

$string['originality_customerservice'] = "Originality Group. Επικοινωνία με το CustomerService@originality.co.il";

$string['settings_key_error'] = "Το μυστικό κλειδί που δώσατε δεν είναι έγκυρο. Παρακαλώ εισάγετε ένα έγκυρο μυστικό κλειδί.";

$string['originality_one_type_submission'] = "Η ανίχνευση της λογοκλοπής γίνεται είτε με την υποβολή ενός μόνο αρχείου, ή με την υποβολή κειμένου γραμμή προς γραμμή. Επιλέξτε ένα από τα δύο";

$string['originality_unprocessable'] = 'Μη επεξεργάσιμο';

$string['originality_click_checkbox_msg'] = "Για να ενεργοποιήσετε το πλήκτρο υποβολής, πρέπει να σημειώσετε το τετραγωνίδιο ελέγχου 'Γνωρίζω και αποδέχομαι πλήρως ...'";

$string['originality_click_checkbox_button_text'] = "OK";

$string['originality_previous_submissions'] = "Φοιτητές που υπέβαλλαν τις εργασίες τους πριν από αυτήν την αλλαγή πρέπει να τις επανυποβάλλουν για ανίχνευση λογοκλοπής";

$string['originality_shortname'] = "Originality";

$string['originality_live_server'] = "Ενεργός";

$string['originality_test_server'] = "Δοκιμή";

$string['originality_customerservice_email'] = "customerservice@originality.co.il";

$string['originality_upgrade_error']= "Υπήρξε λάθος κατά την ανάβαθιση.";

$string['originality_server_production'] = "<b>Production Server</b>&nbsp;&nbsp;<span style='font-size:14px;'>Υποβολή εργασίας στον εξυπηρετητή ΠΑΡΑΓΩΓΗΣ του Originality.</span>";

$string['originality_server_test'] = "<b>Test Server</b>&nbsp;&nbsp;<span style='font-size:14px;'>Υποβολή εργασίας στο δοκιμαστικό περιβάλλον του Originality. Για να κάνετε αυτή την επιλογή πρέπει πρώτα να συνεννοηθείτε με την εταιρεία Originality.</span>";

