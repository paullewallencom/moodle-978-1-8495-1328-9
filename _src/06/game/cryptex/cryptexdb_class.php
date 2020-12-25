<?php  // $Id: cryptexdb_class.php,v 1.3.2.4 2010/08/02 14:39:59 bdaloukas Exp $

class CryptexDB extends CrossDB
{
	function save( $game, &$crossm, $crossd, $id, $letters)
	{
		global $USER;

        CrossDB::delete_records( $id);

		if( (CrossDB::save( $game, $crossm, $crossd, $id)) == false){
			return false;
		}
		
		$crossm->id = $id;
		
        $newrec->id = $id;
		$newrec->letters = $letters;
		
		if (!($cryptexid = game_insert_record( 'game_cryptex', $newrec))){
			error("Insert page: new page game_cryptex not inserted");
		}		
				
		return $newrec;
	}


	function computeletters( $crossm, $crossd)
	{
		$textlib = textlib_get_instance();
		
		$letters = '';
		$cols = $crossm->cols + 1;
		$letters = str_repeat('.', $crossm->cols).'#';
		$letters = str_repeat($letters, $crossm->rows) ;
		
		$freqs1 = array();
		$count1 = $count2 = 0;
		foreach( $crossd as $rec)
		{
			$pos = $rec->col - 1 + ($rec->row-1) * $cols;
			$s = $rec->answertext;
			$len = $textlib->strlen( $s);
			
			$a = array();
			for( $i=0; $i < $len; $i++){
				$a[] = $textlib->substr( $s, $i, 1);
			}
			
			for( $i=0; $i < $len; $i++){
				$this->setchar( $letters, $pos,  $a[ $i]);
				$pos += ( $rec->horizontal ? 1 : $cols);
				
				$freqs1[ ++$count1] = $a[ $i];
				if( $i+1 < $len){
					$freqs2[ ++$count2] = $a[ $i].$a[ $i+1];
				}
			}
		}
	
		$len = $textlib->strlen( $letters);
		$spaces = 0;
		for( $i=0; $i < $len; $i++){
			if( $textlib->substr( $letters, $i, 1) == '.'){
				$spaces++;
			}
		}	
		
		$step = 1;
		while( $spaces)
		{
			if( $step == 1){
				$step = 2;
				$i = array_rand( $freqs1);
				$this->insertchar( $letters, $crossm->cols, $crossm->rows, $freqs1[ $i], $spaces);
			}else
			{
				$step=1;
				$i = array_rand( $freqs2);
				$this->insertchars( $letters, $crossm->cols, $crossm->rows, $freqs2[ $i], $spaces);
			}
		}

		$ret_letters = "";
		for( $row=0; $row < $crossm->rows; $row++){
			$ret_letters .= $textlib->substr( $letters, $cols * $row, ($cols-1));
		}


		return $ret_letters;
	}

    function display( $cols, $rows, $letters, $mask, $showsolution=false, $textdir)
    {
		$textlib = textlib_get_instance();

		echo "<table class=\"cryptex-grid\" $textdir>";
		for( $row=0; $row < $rows; $row++)
		{
			echo "<tr>";
			for( $col=0; $col < $cols; $col++){
				$pos = $cols * $row+$col;
				$c = $textlib->substr( $letters, $pos, 1);
				$m = $textlib->substr( $mask, $pos, 1);
				
				if( $showsolution and $m > '0'){
					echo '<td class="hi"><b>'.$c.'</b></td>'; #NDF.
				}else if( $m == '1'){
					echo '<td class="hi"><b>'.$c.'</b></td>';
				}else
				{
					echo "<td >".$c."</td>";
				}
			}
			echo "</tr>\r\n";
		}
		echo "</table>";
    }
	
	function insertchar( &$letters, $cols, $rows, $char, &$spaces)
	{
		$textlib = textlib_get_instance();
		
		$len = $textlib->strlen( $letters);
		for( $i=0; $i < $len; $i++){
			if( $textlib->substr( $letters, $i, 1) == '.'){
				$this->setchar( $letters, $i, $char);
				$spaces--;
				return;
			}
		}		
	}
	
	function insertchars( &$letters, $cols, $rows, $char, &$spaces)
	{
		$textlib = textlib_get_instance();
		
		$len = $textlib->strlen( $letters);
		for( $i=0; $i < $len; $i++){
			if( $textlib->substr( $letters, $i, 1) == '.'  and $textlib->substr( $letters, $i+1, 1) == '.' ){
				$this->setchar( $letters, $i, $textlib->substr( $char, 0, 1));
				$this->setchar( $letters, $i+1, $textlib->substr( $char, 1, 1));
				$spaces-=2;
				return true;
			}
			if( $textlib->substr( $letters, $i, 1) == '.'  and $textlib->substr( $letters, $i+$cols+1, 1) == '.' ){
				$this->setchar( $letters, $i, $textlib->substr( $char, 0, 1));
				$this->setchar( $letters, $i + $cols+1, $textlib->substr( $char, 1, 1));
				$spaces-=2;
				return true;
			}
		}	
		
		return false;
	}
	
	function gethash( $word)
	{
		$textlib = textlib_get_instance();
		
		$x = 37;
		$len = count( $textlib->strlen( $word));
		
		for($i=0; $i < $len; $i++){
			$x = $x xor ord( $textlib->substr( $word, $i, 1));
		}
		
		return $x;
	}

	function load( $crossm, &$mask, &$corrects, &$language)
	{
		$textlib = textlib_get_instance();
		
		$questions = array();
		$corrects = array();
		
		$mask = str_repeat( '0', $crossm->cols * $crossm->rows);
		
		if ($recs = get_records_select('game_queries', "attemptid=$crossm->id"))
		{
			foreach ($recs as $rec)
			{
			    if( $rec->questiontext == ''){
			        $rec->questiontext = ' ';
			    }
				$key = $this->gethash( $rec->questiontext).'-'.$rec->answertext.'-'.$rec->id;
				$questions[ $key] = $rec;
				
				$word = $rec->answertext;
				$pos = $crossm->cols * ($rec->row-1)+($rec->col-1);
				$len = $textlib->strlen( $word);
				$found = ($rec->answertext == $rec->studentanswer);

				for( $i=0; $i < $len; $i++)
				{
					$c = ( $found ? '1' : '2');
					
					if( $textlib->substr( $mask, $pos,  1) != '1'){
						game_setchar( $mask, $pos, $c);
					}
					
					$pos += ($rec->horizontal ? 1 : $crossm->cols);
				}
				
				if( $found){
					$corrects[ $rec->id] = 1;
				}

                if( $language == ''){
                    $language = game_detectlanguage( $rec->answertext);
                }
			}
			ksort( $questions);
		}
		
		return $questions;
	}


	function setwords( $answers, $maxcols, $reps)
	{		
		return Cross::setwords( $answers, $maxcols, $reps);
	}
    
	function computedata( &$crossm, &$crossd, &$letters, $maxwords)
	{	
		if( !cross::computedata( $crossm, $crossd, $maxwords)){
			return false;
		}
		
		$letters = $this->computeletters( $crossm, $crossd);
		
		return true;
	}
}

