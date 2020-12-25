<?php  // $Id: exporthtml_millionaire.php,v 1.7.2.2 2010/07/24 03:30:22 arborrow Exp $
/**
 * This page export the game millionaire to html
 * 
 * @author  bdaloukas
 * @version $Id: exporthtml_millionaire.php,v 1.7.2.2 2010/07/24 03:30:22 arborrow Exp $
 * @package game
 **/
function game_millionaire_html_getquestions( $game, &$max)
{
	global $CFG, $USER;
	
	$max = 0;
	
	if( ($game->sourcemodule != 'quiz') and ($game->sourcemodule != 'question')){
		error( get_string('millionaire_sourcemodule_must_quiz_question', 'game'));
	}
	
	if( $game->sourcemodule == 'quiz'){
		if( $game->quizid == 0){
			error( get_string( 'must_select_quiz', 'game'));
		}		
		$select = "qtype='multichoice' AND quiz='$game->quizid' ".
						" AND qqi.question=q.id";
		$table = "question q,{$CFG->prefix}quiz_question_instances qqi";
	}else
	{
		if( $game->questioncategoryid == 0){
			error( get_string( 'must_select_questioncategory', 'game'));
		}
		
		//include subcategories				
		$select = 'category='.$game->questioncategoryid;
        if( $game->subcategories){
            $cats = question_categorylist( $game->questioncategoryid);
            if( strpos( $cats, ',') > 0){
                $select = 'category in ('.$cats.')';
            }
        }  						
		$select .= " AND qtype='multichoice'";
		
		$table = "question q";
	}
	$select .= " AND q.hidden=0";
	
	$recs = get_records_select( $table, $select, '', "q.id as id, q.questiontext");
	$ret = '';
	foreach( $recs as $rec){
	    $recs2 = get_records_select( 'question_answers', "question=$rec->id", 'fraction DESC', 'id,answer');
	    $line = $rec->questiontext;
	    foreach( $recs2 as $rec2)
	        $line .= '#'.str_replace( array( '"', '#'), array( "'", ' '), $rec2->answer);
	    if( $ret != '')
	        $ret .= ",\r";
	    $ret .= '"'.base64_encode( $line).'"';
	    
	    if( count( $recs2) > $max)
	        $max = count( $recs2);
    }
    
    return $ret;
}

function game_millionaire_html_print( $game,  $questions, $maxquestions)
{
?>

<body onload="Reset();">

<script type="text/javascript">

    //Millionaire for Moodle by Vasilis Daloukas
    
    <?php echo 'var questions = new Array('.$questions.");\r"; ?>
    var current_question = 0;
    var level = 0;
    var posCorrect = 0;
    var infoCorrect = "";
    var flag5050 = 0;
    var flagTelephone = 0;
    var flagPeople = 0;
    var countQuestions = 0;
    var maxQuestions = <?php echo $maxquestions;?>;
    
	function Highlite( ans)
	{	    
		document.getElementById( "btAnswer" + ans).style.backgroundColor = 'DarkOrange';
	}

	function Restore( ans)
	{
		document.getElementById( "btAnswer" + ans).style.backgroundColor = 'Black';
	}
	
	function OnSelectAnswer( ans)
	{
	    if( posCorrect == ans)
	    {
	        if( level+1 > 15)
	        {
	            alert( "<?php echo get_string( 'millionaire_win', 'game');?>");
	            Reset();
	        }else
	        {
	            UpdateLevel( level+1);
	            SelectNextQuestion();
	        }
	    }else
	    {
	        OnGameOver( ans);
	    }
	}
	
    function OnGameOver( ans)
    {
        document.getElementById( "info").innerHTML = "<?php echo get_string( 'millionaire_info_wrong_answer', 'game');?> "+document.getElementById( "lblAnswer" + posCorrect).innerHTML;
        Highlite( posCorrect);
        Restore( ans);
        document.getElementById( "lblAnswer" + posCorrect).style.backgroundColor = 'DarkOrange';
        
        alert( "<?php echo strip_tags( get_string( 'hangman_loose', 'game')); ?>");
       
        Restore( posCorrect); 
        document.getElementById( "lblAnswer" + posCorrect).style.backgroundColor = 'Black';
        
        Reset();
    }
    
	function UpdateLevel( newlevel)
	{
	    if( level > 0)
	    {
	        document.getElementById( "levela" + level).bgColor = "Black";	    
    	    document.getElementById( "levelb" + level).bgColor = "Black";	    
	        document.getElementById( "levelc" + level).bgColor = "Black";
	        document.getElementById( "levela" + level).style.color = "White";	    
	        document.getElementById( "levelb" + level).style.color = "White";	    
	        document.getElementById( "levelc" + level).style.color = "White";
	    }
	    
	    level = newlevel;

	    document.getElementById( "levela" + level).bgColor = "Orange";	    
	    document.getElementById( "levelb" + level).bgColor = "Orange";	    
	    document.getElementById( "levelc" + level).bgColor = "Orange";
	    document.getElementById( "levela" + level).style.color = "White";	    
	    document.getElementById( "levelb" + level).style.color = "White";	    
	    document.getElementById( "levelc" + level).style.color = "White";
    }
	
	function OnHelp5050( ans)
	{
	    if( flag5050)
	        return;
	        
        document.getElementById( "Help5050").src = "5050x.gif";
        flag5050 = 1;
        
        for(pos = posCorrect;pos == posCorrect;pos = 1+Math.floor(Math.random()*countQuestions));

        for( i=1; i <= countQuestions; i++)
        {   
            if( (i != pos) && (i != posCorrect))
            {         
                document.getElementById( "lblAnswer" + i).style.visibility = 'hidden';
        	    document.getElementById( "btAnswer" + i).style.visibility = 'hidden';
        	}
        }
	}
	
	function OnHelpTelephone( ans)
	{
	    if( flagTelephone)
	        return;
	    flagTelephone = 1;
        document.getElementById( "HelpTelephone").src = "telephonex.gif";
	    
		if( countQuestions < 2){
			wrong = posCorrect;
		}else
		{
			for(;;)
			{
				wrong = 1+Math.floor(Math.random()*countQuestions);
				if( wrong != posCorrect)
					break;
			}
		}
		//with 80% gives the correct answer
		if( Math.random() <= 0.8)
			pos = posCorrect;
		else
			pos = wrong;
			
        info = "<?php echo get_string( 'millionaire_info_telephone','game').'<br><b>';?> ";
        info += document.getElementById( "lblAnswer" + pos).innerHTML;
        document.getElementById( "info").innerHTML = info;
	}

	function OnHelpPeople( ans)
	{
        if( flagPeople)
	        return;
	    flagPeople = 1;
        document.getElementById( "HelpPeople").src = "peoplex.gif";
        
        sum = 0;
        var aPercent = new Array();
        for( i = 0; i < countQuestions-1; i++)
        {
			percent = Math.floor(Math.random()*(100-sum));
			aPercent[ i] = percent;
			sum += percent;
        }
        aPercent[ countQuestions - 1] = 100 - sum;
        if( Math.random() <= 0.8)
        {
            //with percent 80% sets in the correct answer the biggest percent
            max_pos = 0;
            for( i=1; i < countQuestions; i++)
            {
                if( aPercent[ i] >= aPercent[ max_pos])
                    max_pos = i;
            }
            temp = aPercent[ max_pos];
            aPercent[ max_pos] = aPercent[ posCorrect-1];
            aPercent[ posCorrect-1] = temp;
        }
        
        var letters = "<?php echo get_string( 'millionaire_letters_answers', 'game');?>";
        info = "<?php echo '<br>'.get_string( 'millionaire_info_people', 'game').':<br>';?>";
        for( i=0; i < countQuestions; i++){
            info += "<br>" + letters.charAt( i) + " : " + aPercent[ i] + " %";
		}
	    
	    document.getElementById( "info").innerHTML = info;
	}

	function OnQuit( ans)
	{
	    Reset();
	}
	
	function Reset()
	{
	    for(i=1; i <= 15; i++)
	    {
	        document.getElementById( "levela" + i).bgColor = "Black";	    
    	    document.getElementById( "levelb" + i).bgColor = "Black";	    
	        document.getElementById( "levelc" + i).bgColor = "Black";
	        document.getElementById( "levela" + i).style.color = "White";	    
	        document.getElementById( "levelb" + i).style.color = "White";	    
	        document.getElementById( "levelc" + i).style.color = "White";	    
	    }
	    
        flag5050 = 0;
        flagTelephone = 0;
        flagPeople = 0;
        
        document.getElementById( "Help5050").src = "5050.gif";
        document.getElementById( "HelpPeople").src = "people.gif";
        document.getElementById( "HelpTelephone").src = "telephone.gif";

	    document.getElementById( "info").innerHTML = "";
	    UpdateLevel( 1);
	    SelectNextQuestion();
	}

    function RandomizeAnswers( elements)
    {
        posCorrect = 1;
        countQuestions = elements.length-1;

        for( i=1; i <= countQuestions; i++)
        {
            pos = 1+Math.floor(Math.random()*countQuestions);
            if( posCorrect == i)
                posCorrect = pos;
            else if( posCorrect == pos)
                posCorrect = i;
                
            var temp = elements[ i];
            elements[ i] = elements[ pos];
            elements[ pos] = temp;
        }
    }
    
	function SelectNextQuestion()
	{   
	    current_question = Math.floor(Math.random()*questions.length);
	    question = Base64.decode( questions[ current_question]);
	    
	    var elements = new Array();
        elements = question.split('#');
        
        RandomizeAnswers( elements);

	    document.getElementById( "question").innerHTML = elements[ 0];
	    for( i=1; i < elements.length; i++)
	    {
    	    document.getElementById( "lblAnswer" + i).innerHTML = elements[ i];
    	    document.getElementById( "lblAnswer" + i).style.visibility = 'visible';
    	    document.getElementById( "btAnswer" + i).style.visibility = 'visible';
	    }
	    for( i=elements.length; i<= maxQuestions; i++)
	    {
    	    document.getElementById( "lblAnswer" + i).style.visibility = 'hidden';
    	    document.getElementById( "btAnswer" + i).style.visibility = 'hidden';
    	}
    	
    	document.getElementById( "info").innerHTML = "";
    }
    
/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/
 
var Base64 = {
 
	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
 
	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
 
		while (i < input.length) {
 
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
 
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);bgColor = "Black";
			chr3 = ((enc3 & 3) << 6) | enc4;
 
			output = output + String.fromCharCode(chr1);
 
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
 		}
 
		output = Base64._utf8_decode(output);
 
		return output;
 
	}, 
 
	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
		while ( i < utftext.length ) {
			c = utftext.charCodeAt(i);
 
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
 
		}
 
		return string;
	}
 
}
		
</script>



<table cellpadding=0 cellspacing=0 border=0>
<tr style='background:#408080'>
<td rowspan=<?php echo 17+$maxquestions;?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td colspan=6>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td rowspan=<?php echo 17+$maxquestions;?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
</tr>

<tr height=10%>
<td style='background:#408080' rowspan=3 colspan=2>
<input type="image"  name="Help5050" id="Help5050" Title="50 50" src="5050.gif" alt="" border="0" onmousedown=OnHelp5050();>&nbsp;
<input type="image" name="HelpTelephone"  id="HelpTelephone" Title="<?php echo get_string( 'millionaire_telephone', 'game');?>" src="telephone.gif" alt="" border="0" onmousedown="OnHelpTelephone();">&nbsp;
<input type="image" name="HelpPeople"  id="HelpPeople" Title="<?php echo get_string( 'millionaire_helppeople', 'game');?>" src="people.gif" alt="" border="0" onmousedown="OnHelpPeople();">&nbsp;
<input type="image" name="Quit" id="Quit" Title="<?php echo get_string( 'millionaire_quit', 'game');?>" src="x.gif" alt="" border="0" onmousedown=OnQuit();>&nbsp;
</td>
<td rowspan=<?php echo 16+$maxquestions;?> style='background:#408080'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td id="levela15" align=right>15</td>
<td id="levelb15">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td id="levelc15" align=right>    150000</td>
</tr>

<tr><td id="levela14" align=right>14</td>
<td id="levelb14"></td><td id="levelc14" align=right>       800000</td>
</tr>

<tr><td id="levela13" align=right>13</td>
<td id="levelb13"></td><td id="levelc13" align=right>       400000</td>
</tr>

<tr><td rowspan=12 colspan=2 valign=top style='background:black;color:white'><div id="question">aa</div></td>
<td id="levela12" align=r0ight>12</div></td>
<td id="levelb12"></td><td id="levelc12" align=right>       200000</td>
</tr>

<tr><td id="levela11" align=right>11</td>
<td id="levelb11"></td><td id="levelc11" align=right>       10000</td>
</tr>

<tr><td id="levela10" align=right>10</td>
<td id="levelb10"></td><td id="levelc10" align=right>       5000</td>
</tr>

<tr><td id="levela9" align=right>9</td>
<td id="levelb9"></td><td id="levelc9" align=right>       4000</td>
</tr>

<tr><td id="levela8" align=right>8</td>
<td id="levelb8"></td><td id="levelc8" align=right>       2000</td>
</tr>

<tr><td id="levela7" align=right>7</td>
<td id="levelb7"></td><td id="levelc7" align=right>       1500</td>
</tr>

<tr><td id="levela6" align=right>6</td>
<td id="levelb6"></td><td id="levelc6" align=right>       1000</td>
</tr>

<tr><td id="levela5" align=right>5</td>
<td id="levelb5"></td><td id="levelc5" align=right>       500</td>
</tr>

<tr><td id="levela4" align=right>4</td>
<td id="levelb4"></td><td id="levelc4" align=right>       400</td>
</tr>

<tr><td id="levela3" align=right>3</td>
<td id="levelb3"></td><td id="levelc3" align=right>       300</td>
</tr>

<tr><td id="levela2" align=right>2</td>
<td id="levelb2"></td><td id="levelc2" align=right>       200</td>
</tr>

<tr><td id="levela1" align=right>1</td>
<td id="levelb1"></td><td id="levelc1" align=right>       100</td>
</tr>

<tr style='background:#408080'><td colspan=10>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
</tr>

<?php
$letters = get_string( 'millionaire_letters_answers', 'game');
$textlib = textlib_get_instance();
for($i=1 ; $i <= $maxquestions; $i++)
{
    $s = $textlib->substr( $letters, $i-1, 1);
    echo "<tr>\n";
    echo "<td style='background:black;color:white'>";
    echo "<input style=\"background:Black;color:white\" type=\"submit\" name=\"btAnswer$i\" value=\"$s\" id=\"btAnswer$i\"";
    echo " onmouseover=\"Highlite( $i);\" onmouseout=\"Restore( $i);\"  onmousedown=\"OnSelectAnswer( $i);\">";
    echo "</td>\n";
    echo "<td style=\"background:Black;color:white\" width=100%> &nbsp; <span id=lblAnswer$i style=\"background:Black;color:white\" onmouseover=\"Highlite($i);\r \n\" onmouseout=\"Restore( $i);\" onmousedown=\"OnSelectAnswer( $i);\"></span></td>\n";
    if( $i == 1)
    {
        echo "<td style='background:#408080' rowspan=".$maxquestions." colspan=3><div id=\"info\"></div></td>\n";
    }
    echo "</tr>\n";
}
?>

<tr><td colspan=10 style='background:#408080'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td></tr>

</table>


</body>
</html>
<?php
}
