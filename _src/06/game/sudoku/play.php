<?php  // $Id: play.php,v 1.8.2.6 2010/08/10 18:11:04 bdaloukas Exp $

// This files plays the game Sudoku

require_once( "../../lib/questionlib.php");

function game_sudoku_continue( $id, $game, $attempt, $sudoku, $endofgame=''){
	global $CFG, $USER;
	
	if( $endofgame){
		game_updateattempts( $game, $attempt, -1, true);
		$endofgame = false;
	}	
	
	if( $attempt != false and $sudoku != false){
		return game_sudoku_play( $id, $game, $attempt, $sudoku);
	}
	
	if( $attempt == false){
		$attempt = game_addattempt( $game);
	}
	
	//new game
    srand( (double)microtime()*1000000);
    
	$recsudoku = getrandomsudoku();
	if( $recsudoku == false){
        require( $CFG->dirroot.'/mod/game/db/importsudoku.php');
	    $recsudoku = getrandomsudoku();
	    if( $recsudoku == false)
            error( 'Empty sudoku database');
	}

	$newrec->id = $attempt->id;
	$newrec->guess = '';
	$newrec->data = $recsudoku->data;
	$newrec->opened  = $recsudoku->opened;

	$need = 81 - $recsudoku->opened;
	$closed = game_sudoku_getclosed( $newrec->data);
	$n = min( count($closed), $need);
	//if the teacher set the maximum number of questions
	if( $game->param2 > 0){
		if( $game->param2 < $n){
			$n = $game->param2;
		}
	}
	$recs = game_questions_selectrandom( $game, CONST_GAME_TRIES_REPETITION*$n);
	
	if( $recs === false){
		error( get_string( 'no_questions', 'game'));
	}
	
	$closed = array_rand($closed, $n);

    $selected_recs = game_select_from_repetitions( $game, $recs, $n);

	if(!game_insert_record('game_sudoku', $newrec)){
		error('error inserting in game_sudoku');
	}
	    
	$i = 0;
    $field = ($game->sourcemodule == 'glossary' ? 'glossaryentryid' : 'questionid');
	foreach($recs as $rec)
	{
        if( !array_key_exists( $rec->$field, $selected_recs))
            continue;

		unset($query);
		$query->attemptid = $newrec->id;
		$query->gameid = $game->id;
		$query->userid = $USER->id;
		$query->col = $closed[ $i++];
		$query->sourcemodule = $game->sourcemodule;
		$query->questionid = $rec->questionid;
		$query->glossaryentryid = $rec->glossaryentryid;
        $query->score = 0;

		if(($query->id = insert_record('game_queries', $query)) == 0){
			error('error inserting in game_queries');
		}

        game_update_repetitions($game->id, $USER->id, $query->questionid, $query->glossaryentryid);
	}
	
	game_updateattempts($game, $attempt, 0, 0);

	game_sudoku_play($id, $game, $attempt, $newrec);
}

function game_sudoku_play( $id, $game, $attempt, $sudoku, $onlyshow=false, $showsolution=false){
    $offsetquestions = game_sudoku_compute_offsetquestions( $game->sourcemodule, $attempt, $numbers, $correctquestions);

	if( $game->toptext != ''){
		echo $game->toptext.'<br>';
	}

	game_sudoku_showsudoku( $sudoku->data, $sudoku->guess, true, $showsolution, $offsetquestions, $correctquestions, $id, $attempt, $game);
	switch( $game->sourcemodule){
	case 'quiz':
	case 'question':
		game_sudoku_showquestions_quiz( $id, $game, $attempt, $sudoku, $offsetquestions, $numbers, $correctquestions, $onlyshow, $showsolution);
		break;
	case 'glossary':
		game_sudoku_showquestions_glossary( $id, $game, $attempt, $sudoku, $offsetquestions, $numbers, $correctquestions, $onlyshow, $showsolution);
		break;
	}
	
	if( $game->bottomtext != ''){
		echo '<br>'.$game->bottomtext;
	}	
}

//returns a map with an offset and id of each question
function game_sudoku_compute_offsetquestions( $sourcemodule, $attempt, &$numbers, &$correctquestions){
    $select = "attemptid = $attempt->id";

    $fields = 'id, col, score';				//,glossaryentryid, questionid
	switch( $sourcemodule){
	case 'quiz':
	case 'question':
		$fields .= ',questionid as id2';
		break;
	case 'glossary':
		$fields .= ',glossaryentryid as id2';
		break;
	}
    if( ($recs = get_records_select( 'game_queries', $select, '', $fields)) == false){
        error( 'There are no questions');
    }
    $offsetquestions = array();
    $numbers = array();
	$correctquestions = array();
    foreach($recs as $rec){
        $offsetquestions[$rec->col] = $rec->id2;
        $numbers[$rec->id2] = $rec->col;
        if($rec->score == 1)
            $correctquestions[$rec->col] = 1;
    }

    ksort( $offsetquestions);

    return $offsetquestions;
}

function getrandomsudoku($level1=0, $level2=0){
	global $CFG;

	$where = "";
	if($level1){
		$where = " AND level >= $level1";
	}
	if($level2){
		$where .= " AND level <= $level2";
	}
	if($where != ""){
		$where = " WHERE $where";
	}
	$sql = "SELECT COUNT(*) as c FROM {$CFG->prefix}game_sudoku_database $where";

	$rec = get_record_sql($sql);
	$count = $rec->c;

	$i = mt_rand(0, $count - 1);

	$sql = "SELECT * FROM {$CFG->prefix}game_sudoku_database $where";
	if( ($recs = get_records_sql( $sql, $i, 1)) != false)
	{
		foreach( $recs as $rec){
			return $rec;
		}
	}

	return false;
}


function game_sudoku_getclosed( $data){
	$textlib = textlib_get_instance();
	
	$a = array();
  
	$n = $textlib->strlen( $data);
	for( $i=1; $i <= $n; $i++)
	{
		$c = $textlib->substr( $data, $i-1, 1);
		if( $c >= "1" and $c <= "9")
			$a[ $i] = $i;
	}

	return $a;
}

function game_sudoku_showsudoku( $data, $guess, $bShowLegend, $bShowSolution, $offsetquestions, $correctquestions, $id, $attempt, $game){
	global $CFG;
	
	$correct = $count = 0;
	
	echo "<br>\r\n";
	echo '<table border="1" style="border-collapse: separate; border-spacing: 0px;">';
    $pos=0;
    for($i=0; $i <= 2; $i++){
		echo "<tr>";
		for($j=0; $j <= 2; $j++){
			echo '<td><table border="1" width="100%">';
			for($k1=0; $k1 <= 2; $k1++){
				echo "<tr>";
				for( $k2=0; $k2 <= 2; $k2++){
					$s = substr($data, $pos, 1);
					$g = substr($guess, $pos, 1);
					$pos++;
					if($g != 0){
						$s = $g;
					}
					if($s >= "1" and $s <= "9"){
						//closed number
						if($bShowLegend)
						{
							//show legend
							if($bShowSolution == false){
								if(!array_key_exists($pos, $correctquestions)){
									if(array_key_exists($pos, $offsetquestions))
									{
										if($s != $g){
											$s = '<input type="submit" value="A'.$pos.'" onclick="OnCheck('.$pos.');" />';
										}
									}else if($g == 0){
										$s = '<input type="submit" value="" onclick="OnCheck('.$pos.');" />';
									}
                                }
                                else{
									//correct question
									$count++;
								}
							}
							echo '<td width=33% style="text-align: center; padding: .6em; color: red; font-weight: lighter; font-size: 1em;">'.$s.'</td>';
                        }
                        else{
							//not show legend
							echo '<td width=33% style="text-align: center; padding: .6em; color: red; font-weight: lighter; font-size: 1em;">&nbsp;</td>';
						}
                    }
                    else{
						$s = strpos("-ABCDEFGHI", $s);
						$count++;
						echo '<td width=33% style="text-align: center; padding: .6em; color: black; font-weight: lighter; font-size: 1em;">'.$s.'</td>';
					}
				}
				echo "</tr>";
			}
			echo "</table></td>\r\n";
		}
		echo "</tr>";
    }
	echo "</table>\r\n";
	
	?>
	<script language="javascript">
			function OnCheck( pos)
			{
				s = window.prompt( "<?php echo get_string ( 'sudoku_guessnumber', 'game') ?>", "");
				
				if( s < "1")
					return;
				if( s > "9")
					return;

				window.location.href = "<?php echo $CFG->wwwroot.'/mod/game/attempt.php?action=sudokucheckn&id='.$id ?>&pos=" + pos + "&num=" + s;
			}
		</script>
	<?php	

	//Here are the congratulations
    if($attempt->timefinish){
		return $count;
	}
	
    if(count($offsetquestions) != count($correctquestions)){
		return $count;
	}
	
	if (!$cm = get_record("course_modules", "id", $id)) {
		error("Course Module ID was incorrect id=$id");
	}
	
	echo '<b><br>'.get_string('sudoku_win', 'game').'</b><br>';	
	echo '<br>';	
	echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id\">".get_string('cross_new', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
	echo "<a href=\"$CFG->wwwroot/course/view.php?id=$cm->course\">".get_string('finish', 'game').'</a> ';
	
	game_updateattempts($game, $attempt, 1, 1);

    return $count;
}


function game_sudoku_getquestionlist($offsetquestions){
    $questionlist = '';
    foreach($offsetquestions as $q){
        if($q != 0){
            $questionlist .= ','.$q;
        }
    }
    $questionlist = substr( $questionlist, 1);
	
    if ($questionlist == '') {
        error(get_string('no_questions', 'game'));
    }

	return $questionlist;
}

function game_sudoku_getglossaryentries($game, $offsetentries, &$entrylist, $numbers){
	global $CFG;

    $entrylist = implode(',', $offsetentries);
	
    if ($entrylist == '') {
        error(get_string('sudoku_noentriesfound', 'game'));
    }

    // Load the questions
    if (!$entries = get_records_select( 'glossary_entries', "id IN ($entrylist)")) {
        error(get_string('sudoku_noentriesfound', 'game'));
    }
	
    return $entries;
}

function game_sudoku_showquestions_quiz($id, $game, $attempt, $sudoku, $offsetquestions, $numbers, $correctquestions, $onlyshow, $showsolution){
	$questionlist = game_sudoku_getquestionlist($offsetquestions);
    $questions = game_sudoku_getquestions($questionlist);
	
	//I will sort with the number of each question
	$questions2 = array();
	foreach($questions as $q){
		$ofs = $numbers[$q->id];
		$questions2[$ofs] = $q;
	}
	ksort($questions2);
	
	if(count($questions2) == 0){
		game_sudoku_showquestion_onfinish($id, $game, $attempt, $sudoku);
		return;
	}

	global $CFG;
	
	/// Start the form
    echo "<form id=\"responseform\" method=\"post\" action=\"{$CFG->wwwroot}/mod/game/attempt.php\" onclick=\"this.autocomplete='off'\">\n";
	if( ($onlyshow === false) and ($showsolution  === false)){
		echo "<br><center><input type=\"submit\" name=\"submit\" value=\"".get_string('sudoku_submit', 'game')."\">";
		
		echo " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"finishattempt\" value=\"".
		        get_string('sudoku_finishattemptbutton', 'game')."\">";
	}

    // Add a hidden field with the quiz id
    echo '<div id="_game-q">';  #@NDF.
    echo '<input type="hidden" name="id" value="' . s($id) . "\" />\n";
    echo '<input type="hidden" name="action" value="sudokucheck" />';

	/// Print all the questions

    // Add a hidden field with questionids
    echo '<input type="hidden" name="questionids" value="'.$questionlist."\" />\n";

	$number=0;
    foreach ($questions2 as $question) {
        $ofs = $numbers[ $question->id];
        if(array_key_exists($ofs, $correctquestions)){
            continue;   //I don't show the correct answers
        }
		$number = "<a name=\"a$ofs\">A$ofs</a>";
				
		global $QTYPES;
		unset( $cmoptions);
        $cmoptions->course = $game->course;
        $cmoptions->optionflags->optionflags = 0;
		$cmoptions->id = 0;
		$cmoptions->shuffleanswers = 1;
		$attempt = 0;
		if (!$QTYPES[$question->qtype]->create_session_and_responses($question, $state, $cmoptions, $attempt)) {
			error('game_sudoku_showquestions_quiz: problem');
		}
		
		$state->last_graded = new StdClass;
		$state->last_graded->event = QUESTION_EVENTOPEN;
		$state->event = QUESTION_EVENTOPEN;
		$options->scores->score = 0;
		$question->maxgrade = 100;
		$state->manualcomment = '';
		$cmoptions->optionflags = 0;
		$options->correct_responses = 0;
		$options->feedback = 0;
		$options->readonly = 0;

		if( $showsolution){
			$state->responses = $QTYPES[$question->qtype]->get_correct_responses($question, $state);
		}
		
		print_question($question, $state, $number, $cmoptions, $options);
    }

    echo "</div>";

    // Finish the form
    echo '</div>';
	if( ($onlyshow === false) and ($showsolution === false)){
		echo "<center><input type=\"submit\" name=\"submit\" value=\"".get_string('sudoku_submit', 'game')."\"></center>\n";
	}

    echo "</form>\n";
}

//show the sudoku and glossaryentries
function game_sudoku_showquestions_glossary($id, $game, $attempt, $sudoku, $offsetentries, $numbers, $correctentries, $onlyshow, $showsolution){
	global $CFG;
	
    $entries = game_sudoku_getglossaryentries($game, $offsetentries, $questionlist, $numbers);

	//I will sort with the number of each question
	$entries2 = array();
	foreach($entries as $q){
		$ofs = $numbers[$q->id];
		$entries2[$ofs] = $q;
	}
	ksort($entries2);
	
	if(count($entries2) == 0){
		game_sudoku_showquestion_onfinish($id, $game, $attempt, $sudoku);
		return;
	}
	
	/// Start the form
    echo "<br><form id=\"responseform\" method=\"post\" action=\"{$CFG->wwwroot}/mod/game/attempt.php\" onclick=\"this.autocomplete='off'\">\n";
	
	if( $onlyshow === false){	
		echo "<center><input type=\"submit\" name=\"submit\" value=\"".get_string('sudoku_submit', 'game')."\"></center>\n";
	}

    // Add a hidden field with the quiz id
    echo '<div>';
    echo '<input type="hidden" name="id" value="' . s($id) . "\" />\n";
    echo '<input type="hidden" name="action" value="sudokucheckg" />';

	/// Print all the questions

    // Add a hidden field with questionids
    echo '<input type="hidden" name="questionids" value="'.$questionlist."\" />\n";

	$number=0;
    foreach ($entries2 as $entry) {
        $ofs = $numbers[$entry->id];
        if( array_key_exists($ofs,$correctentries)){
            continue;   //I don't show the correct answers
        }
				
		$s = 'A'.$ofs.'. '.game_filtertext($entry->definition, 0).'<br>';
		if($showsolution){
			$s .= get_string('answer').': ';
			$s .= "<input type=\"text\" name=\"resp{$entry->id}\" value=\"$entry->concept\"size=30 /><br>";
        }
        else if( $onlyshow === false){	
			$s .= get_string('answer').': ';
			$s .= "<input type=\"text\" name=\"resp{$entry->id}\" size=30 /><br>";
		}
		echo $s."<br/>\r\n";
    }

    echo "</div>";

    // Finish the form
    echo '</div>';
	if($onlyshow === false){	
		echo "<center><input type=\"submit\" name=\"submit\" value=\"".get_string('sudoku_submit', 'game')."\"></center>\n";
	}

    echo "</form>\n";
}

function game_sudoku_showquestion_onfinish( $id, $game, $attempt, $sudoku){
	if(!set_field('game_attempts', 'finish', 1, 'id', $attempt->id)){
		error( "game_sudoku_showquestion_onfinish: Can't update game_attempts id=$attempt->id");
	}
		
	echo '<b>'.get_string( 'sudoku_win', 'game').'</b><br>';	
	echo '<br>';	
	echo "<a href=\"$CFG->wwwroot/mod/game/attempt.php?id=$id\">".get_string('nextgame', 'game').'</a> &nbsp; &nbsp; &nbsp; &nbsp; ';
	echo "<a href=\"$CFG->wwwroot?id=$id\">".get_string('finish', 'game').'</a> ';	
}

function game_sudoku_checkanswers(){
    $responses = data_submitted();

    $actions = question_extract_responses($questions, $responses, $event);
}

function game_sudoku_check_questions($id, $game, $attempt, $sudoku, $finishattempt){
    global $QTYPES, $CFG;

    $responses = data_submitted();

    $offsetquestions = game_sudoku_compute_offsetquestions($game->sourcemodule, $attempt, $numbers, $correctquestions);

	$questionlist = game_sudoku_getquestionlist($offsetquestions);
	
    $questions = game_sudoku_getquestions($questionlist);

    $actions = question_extract_responses($questions, $responses, QUESTION_EVENTSUBMIT);

    foreach($questions as $question) {
        if( !array_key_exists( $question->id, $actions)){
            //no answered
            continue;
        }
        unset( $state);
        unset( $cmoptions);
        $question->maxgrade = 100;
        $state->responses = $actions[ $question->id]->responses;
		$state->event = QUESTION_EVENTGRADE;

		$cmoptions = array();
        $QTYPES[$question->qtype]->grade_responses( $question, $state, $cmoptions);            

		unset($query);

        $select = "attemptid=$attempt->id and score < 0.5";
        $select .= " AND questionid=$question->id";
        if(($query->id = get_field_select( 'game_queries', 'id', $select)) == 0){
			die("problem game_sudoku_check_questions (select=$select)");
            continue;
        }

		$answertext = $state->responses[ ''];
        $grade = $state->raw_grade;
        if($grade < 50){
			//wrong answer
			game_update_queries($game, $attempt, $query, $grade/100, $answertext);
            continue;
        }

        //correct answer
		game_update_queries($game, $attempt, $query, 1, $answertext);
    }

    game_sudoku_check_last($id, $game, $attempt, $sudoku, $finishattempt);
}

function game_sudoku_check_glossaryentries($id, $game, $attempt, $sudoku, $finishattempt){
    global $QTYPES, $CFG;

    $responses = data_submitted();

	//this function returns offsetentries, numbers, correctquestions
    $offsetentries = game_sudoku_compute_offsetquestions( $game->sourcemodule, $attempt, $numbers, $correctquestions);

	$entrieslist = game_sudoku_getquestionlist($offsetentries);

    // Load the glossary entries
    if (!($entries = get_records_select('glossary_entries', "id IN ($entrieslist)"))) {
        error(get_string('noglossaryentriesfound', 'game'));
    }
    foreach($entries as $entry) {
        if(!$answer = optional_param('resp'.$entry->id, '', PARAM_TEXT))
            continue;

		if(game_upper($entry->concept) != game_upper($answer)){
			continue;
		}
        //correct answer
        $select = "attemptid=$attempt->id and score < 0.5";
        $select .= " AND glossaryentryid=$entry->id";
            
		unset($query);
        if(($query->id = get_field_select('game_queries', 'id', $select)) == 0){
			echo "not found $select<br>";
            continue;
        }

        game_update_queries($game, $attempt, $query, 1, $answer);
    }

	game_sudoku_check_last($id, $game, $attempt, $sudoku, $finishattempt);
}


//this is the last function after submiting the answers.
function game_sudoku_check_last($id, $game, $attempt, $sudoku, $finishattempt){
	global $CFG;
	
	$correct = get_field_select('game_queries', 'COUNT(*) AS c', "attemptid=$attempt->id AND score > 0.9");
	$all = get_field_select('game_queries', 'COUNT(*) AS c', "attemptid=$attempt->id");
	
	if($all){
		$grade = $correct / $all;
    }
    else{
		$grade = 0;
	}
	game_updateattempts($game, $attempt, $grade, $finishattempt);
	
    redirect("$CFG->wwwroot/mod/game/attempt.php?id=$id", '', 0);
}

function game_sudoku_check_number($id, $game, $attempt, $sudoku, $pos, $num){
	$textlib = textlib_get_instance();
	$correct = $textlib->substr($sudoku->data, $pos-1, 1);

	if($correct != $num){
		game_sudoku_play($id, $game, $attempt, $sudoku);
		return;
	}
	
	$leng = $textlib->strlen($sudoku->guess);
	$lend = $textlib->strlen($sudoku->data);
	if($leng < $lend){
		$sudoku->guess .= str_repeat(' ', $lend - $leng);
	}
	game_setchar($sudoku->guess, $pos-1, $correct);
	
	if(!set_field_select('game_sudoku', 'guess', $sudoku->guess, "id=$sudoku->id")){
		error('game_sudoku_check_number: Cannot update table game_sudoku');
	}
	
	game_sudoku_play($id, $game, $attempt, $sudoku);
}

?>
