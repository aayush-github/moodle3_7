<?php

// require_once('config.php');
require_once(__DIR__ . '/../../../../config.php');

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('standard');
$PAGE->set_title("Originality Quiz Report");
$PAGE->set_heading("Originality Quiz Report");
//$PAGE->set_url($CFG->wwwroot . '/about.php');


echo $OUTPUT->header();

// Actual content goes here
echo "Report comming soon";

echo $OUTPUT->footer();

