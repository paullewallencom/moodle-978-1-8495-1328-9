<?php  // $Id: report.php,v 1.3.2.2 2010/07/24 03:30:23 arborrow Exp $

// This script uses installed report plugins to print game reports

    require_once("../../config.php");
    require_once("locallib.php");

    $id = optional_param('id',0,PARAM_INT);    // Course Module ID, or
    $q = optional_param('q',0,PARAM_INT);     // game ID

    $mode = optional_param('mode', 'overview', PARAM_ALPHA);        // Report mode

    if ($id) {
        if (! $cm = get_coursemodule_from_id('game', $id)) {
            error("There is no coursemodule with id $id");
        }

        if (! $course = get_record("course", "id", $cm->course)) {
            error("Course is misconfigured");
        }

        if (! $game = get_record("game", "id", $cm->instance)) {
            error("The game with id $cm->instance corresponding to this coursemodule $id is missing");
        }

    } else {
        if (! $game = get_record("game", "id", $q)) {
            error("There is no game with id $q");
        }
        if (! $course = get_record("course", "id", $game->course)) {
            error("The course with id $game->course that the game with id $a belongs to is missing");
        }
        if (! $cm = get_coursemodule_from_instance("game", $game->id, $course->id)) {
            error("The course module for the game with id $q is missing");
        }
    }

    require_login($course->id, false);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    //require_capability('mod/game:viewreports', $context);

    // if no questions have been set up yet redirect to edit.php
    //if (!$game->questions and has_capability('mod/game:manage', $context)) {
    //    redirect('edit.php?gameid='.$game->id);
    //}

    // Upgrade any attempts that have not yet been upgraded to the 
    // Moodle 1.5 model (they will not yet have the timestamp set)
    //if ($attempts = get_records_sql("SELECT a.*".
    //       "  FROM {$CFG->prefix}game_attempts a, {$CFG->prefix}question_states s".
    //       " WHERE a.game = '$game->id' AND s.attempt = a.uniqueid AND s.timestamp = 0")) {
    //    foreach ($attempts as $attempt) {
    //        game_upgrade_states($attempt);
    //    }
    //}

    add_to_log($course->id, "game", "report", "report.php?id=$cm->id", "$game->id", "$cm->id");

/// Open the selected game report and display it

    $mode = clean_param( $mode, PARAM_SAFEDIR);

    if (! is_readable("report/$mode/report.php")) {
        error("Report not known ($mode)");
    }

    include("report/default.php");  // Parent class
    include("report/$mode/report.php");

    $report = new game_report();

    if (! $report->display( $game, $cm, $course)) {             // Run the report!
        error("Error occurred during pre-processing!");
    }

/// Print footer

    print_footer($course);

?>
