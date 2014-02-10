<?php 
  //error_reporting(E_ALL);
  //ini_set("display_errors", 1);

$GLOBALS['DEBUGGING'] = False;


include_once 'conf.php';

# Handle first the preparations, then render page accordingly;
# Has this listener evaluated something already?

$listener=cleanlistener($_GET["listener"]);
$sentencelist;

if ($GLOBALS['DEBUGGING'] == True) {
    print "<pre>";
    print_r ($_POST);
    print "</pre>";
}



if ($listener) {

    if ( isset( $_POST["submissiontag"] ) ) {

# If POST includes evaluation results, we'll write them to disk

	writeresults($resultdir, $listener, $_POST);

    }

# The order of the samples is randomised for each listener,
# and the random order is saved in a file.
    
    $orderfile= getorderfiledir($resultdir).$listener ."_". $personalorderfile;

    if ( ! file_exists(  $orderfile ) ) {
    
# if there is no order file for this listener, then we need to create it:

# Generate the list of sentences for the listener to evaluate;
# Do this by shuffling the sentence list with the checksum of the
# listener email/nickname:

	$sentencelist=range(1001,1000+count($filekey),1);

# srand(crc32($listener));
	#
# In many php installations random functions are disabled for security
# reasons.
	#
# SEOshuffle function provides shuffling functionality based on a seed
	# 
	SEOshuffle($sentencelist, crc32($listener));

	$fh = fopen($orderfile, 'w');
	foreach ($sentencelist as $n) {
	    fwrite($fh, $filekey["$n"]."\n");
	}
	fclose($fh);
    }

    $fh = fopen($orderfile, 'r');
    $samples=array();
    $alreadyevaluated=0;

    while( count($samples) < $samplesperpage &  count($samples)+$alreadyevaluated <  $requiredevaluations ) {
	
	$sample=trim(fgets($fh));

	if ($sample) {
	    if (checklistenerresults($resultdir, $listener, $sample )) {
		if (checkresults($resultdir, $sample) + checklocks($resultdir, $sample,$listener,$allowedtime) < $min_evals) {
		    makelock($resultdir, $sample, $listener);
		    array_push($samples,trim($sample));
		}
	    }
	    else {
		$alreadyevaluated++;
	    }

	}

	else break;
    }

    fclose($fh);

    $samplesonthispage=count($samples);

}

print getheader();



if ($GLOBALS['DEBUGGING']) {
    print "<pre>";
    print_r($_POST);
    print "</pre>";
}

// We'll check first if we have an email or not:

if (!$listener) {
    
    print "<div class=divmain> $introduction 

   <form method=get action=$testurl>
    <input type=text name=listener>
    <input type=submit>    </form> 
 </div>";
    
    

    
}
else {
    if ($alreadyevaluated >= $requiredevaluations) {
	print "<p>Well done! That's all. Thank you.";
    }
    elseif ( $samplesonthispage == 0 ) {
	print "<p>We seem to have enough repetitions on samples already, so we do not need further input from you. ";
	if ($alreadyevaluated > 0)
	    print "<p>Thank you for the $alreadyevaluated samples that you evaluated!";
	else
	    print "<p>Thank you for your interest though!";
    }
    else
    {
	
	#$listenerdir=cleanlistener($listener);
	
	$testurl.="?listener=${listener}";
	
	print "<div class=divmain>";

	if ( $alreadyevaluated > 0 ) {
	    print "<p>You have already evaluated $alreadyevaluated out of $requiredevaluations sentences. Thank you for that</p>
<p>Below you will find another set of sentences synthesised using a low quality voice.
</p>";
	}
	else {
	    print "<p>Below you will find $samplesonthispage sentences synthesised using a low quality voice.<p>";
	}


	$intro = "<p>
Please listen to them and mark which ones are unacceptably bad 
and need to improved, and categorise the most obvious problem in those sentences.";

	print "</p>";



	print $intro;



	print "<form name=\"ff1\" method=\"post\"  action=\"$testurl\" onsubmit=\"beforeSubmit();\">";

	print "<table>";
	print "<tr><th></th><th>Sample</th><th>Quality</th></tr>";



	$first=true;
	foreach ($samples as $n) {
	    $wavfile="audiosamples/roger_$n.wav";

#print "<tr><td>$n</td><td><audio id=\"audio_$n\" src=$wavfile controls width=0 hidden onplay=\"document.ff1.eval_$n.disabled=false;\" ></audio>";
	    print "<tr><td>$n</td><td width=45><audio id=\"audio_$n\" src=$wavfile onended=enable_playbuttons() ></audio>";

	    print "<button type=button id=\"playbutton_$n\" onclick=\"playing_$n()\"> &#9658; play </button> </td>";



	    print "<td><select id=evalselect_$n name=eval_$n size=1 onchange=\"validateForm_$n();\">
<option name=zero value=zero default> please select... </option>
<option name=$n.2 value=$n.2 disabled> Ok: Quality is good </option>
<option name=$n.3 value=$n.3 disabled> Ok: It's not great but it will do </option>
<option name=$n.4 value=$n.4  disabled> Not ok: Mispronunciation of word(s) </option>
<option name=$n.5 value=$n.5  disabled> Not ok: Incomprehensible segments </option>
<option name=$n.6 value=$n.6  disabled> Not ok: Bad rhytmh or prosody </option>
<option name=$n.7 value=$n.7  disabled> Not ok: Bad audio quality (artifacts etc) </option>
</select></td>  
</tr>";
	}

	print "</table>";
	print "<p align=center>";
	print "<input type=hidden name=submissiontag value=submitted>";
	print "<input type=hidden name=timePassed value=0>";
	print "<input type=submit name=submitbutton disabled></form>";

	print "
<p id=changeableText>

<font color=#cc0000>You have rated 0 utterances (".($samplesonthispage)." required)<!-- of which <br>
0 utterances as adequate (1-".($samplesonthispage-1)." required) and <br>
0 utterances as requiring improvement (1-".($samplesonthispage-1)." required).--></font>

</p>";

#    print "</td></tr></table>";
	print "</div>";


	print "\n\n<script type=\"text/JavaScript\">

var green=\"<font color=#00cc00>\";
var red=\"<font color=#cc0000>\";
var endgreen=\"</font>\";
var endred=\"</font>\";

function validateForm() {
  var no=0;var yes=0;
";

	
	foreach ($samples as $n) {
	    print "
  if (document.ff1.eval_$n.value != \"zero\")   { yes++; };";

	}
	print "

  rated=\"You have rated \"+(yes)+\" utterances ($samplesonthispage required) \";

  if (yes==$samplesonthispage) {rated=green+rated+endgreen;} else {rated=red+rated+endred;}

  if (yes==$samplesonthispage) {
    document.getElementById('changeableText').innerHTML=rated+\"<br>\"+green+
       \"Please press submit to save your evaluations!\"+endgreen;
    activateSubmit();
    return true;
  } 
  else {
    document.getElementById('changeableText').innerHTML=rated+\"<br>\";
    deactivateSubmit();
    return false;
  }
}


function activateSubmit() {
  document.ff1.submitbutton.disabled=false;
}

function deactivateSubmit() {
  document.ff1.submitbutton.disabled=true;
}



var timePassed=0;
var myVar=setInterval(function(){myTimer()},1000);

function myTimer()
{
 timePassed++;
 document.ff1.timePassed.value=timePassed;
}
";
	foreach ($samples as $n) {
	    print "
//////////// Sample $n handling /////////////////

var playstamps_$n = new Array();
var answerstamps_$n = new Array();

function validateForm_$n() {
   answerstamps_$n.push( ( new Date().getTime() -loadstamp )/1000);
   validateForm();
}

function playing_$n() {
   if (audio_$n.currentTime != 0) {
    audio_$n.pause();
    audio_$n.currentTime = 0;
    enable_playbuttons() ;    
   }
   else {
     audio_$n.play();
     playstamps_$n.push( (new Date().getTime() -loadstamp)/1000);
     disable_playbuttons()
     playbutton_$n.innerHTML=\" <font color=#00cc00><b>&#8718; stop</b></font> \";
     playbutton_$n.disabled=false;
     enable_selections_$n();
  }
}

function enable_selections_$n() {
   for(var i=0; i<evalselect_$n.length; i++) {
     evalselect_$n.options[i].disabled = false;
   }
}

";
	}



	print "
function disable_playbuttons() {
";
	foreach ($samples as $n) {
	    print "
   playbutton_$n.disabled=true;";
	}
	print "}

function enable_playbuttons() {
";
	foreach ($samples as $n) {
	    print "
   playbutton_$n.disabled=false;
   playbutton_$n.innerHTML=\"&#9658; play \";
";
	}
	print "} 

loadstamp=new Date().getTime();

function beforeSubmit () {

";

	foreach ($samples as $n) {
	    print "
    for (key in playstamps_$n) {
       var myin = document.createElement(\"input\");
       myin.type='hidden';
       myin.name='sample_${n}_listenstamps_'+(key);
       myin.value=playstamps_${n}[key];
       document.ff1.appendChild(myin);
   }

    for (key in answerstamps_$n) {
       var myin = document.createElement(\"input\");
       myin.type='hidden';
       myin.name='sample_${n}_answerstamps_'+(key);
       myin.value=answerstamps_${n}[key];
       document.ff1.appendChild(myin);
   }
   var myin = document.createElement(\"input\");
   myin.type='hidden';
   myin.name='pageloadstamp';
   myin.value=loadstamp;
   document.ff1.appendChild(myin);

";

	}
	print "


    document.ff1.submit();
    return false;
}
</script>
";



    }

}

print "<div class=spacer> </div>";

print "
<div class=divfooter>
<p class=divfooterp>$footertext
Last update to script: ".date('F d Y h:i A P T e', filemtime('test.php'));
print "</p></div>";

print "</body></hmtl>";




/* tweaked from http://www.php.net/manual/en/function.shuffle.php#105931 */
/* $seed variable is optional */
function SEOshuffle(&$items, $seed=false) {
    $original = md5(serialize($items));
    //mt_srand(crc32(($seed) ? $seed : $items[0]));
    for ($i = count($items) - 1; $i > 0; $i--){
	$j = crc32(($seed+$i)) % $i; //@mt_rand(0, $i);
	list($items[$i], $items[$j]) = array($items[$j], $items[$i]);
    }
    if ($original == md5(serialize($items))) {
	list($items[count($items) - 1], $items[0]) = array($items[0], $items[count($items) - 1]);
    }
}


/* File functions for bookkeeping */


function getorderfiledir($resultdir) {
    $orderdir=$resultdir ."orderfiles/";
    if ( ! file_exists( $orderdir ) ) {
	mkdir($orderdir, 0777, true);
    } 
    return $orderdir;
}


function getlockdir($resultdir,$sample) {
    $lockdir =  $resultdir."locks/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $lockdir  ) ) {
	mkdir($lockdir, 0777, true);
    }
    return $lockdir;
}

function getallresultsdir($resultdir,$sample) {
    $resdir=$resultdir."results_all/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $resdir ) ) {
	mkdir($resdir, 0777, true);
    }
    return $resdir;
}

function getlistenerresultsdir($resultdir,$listener) {
    $lisresdir=$resultdir."/listeners/".$listener."/";
    if ( ! file_exists( $lisresdir  ) ) {
	mkdir( $lisresdir, 0777, true);
    }
    return $lisresdir;
}

function checklocks($resultdir, $sample,$listener, $timeout) {
    $lockdir=getlockdir($resultdir, $sample);
    $locks=array_diff(scandir($lockdir), array('..', '.'));
    $lockcount=0;
    foreach ($locks as $l) {
	if ( filemtime(  $lockdir . $l ) >  time()-$timeout  ) {
	    if ( $l != $listener) {
		$lockcount++;
	    }
	    else { unlink($lockdir . $l); };
	}
    }
    return $lockcount;
}

function checkresults($resultdir,$sample) {
    $resdir=getallresultsdir($resultdir, $sample);
    $answers=array_diff(scandir($resdir), array('..', '.'));

    $answercount=0;
    foreach ($answers as $a) {
	$answercount++;
    }    
    return $answercount;

}

function checklistenerresults($resultdir,$listener,$sample) {
   
    $lisresdir=getlistenerresultsdir($resultdir,$listener);

    if ($GLOBALS['DEBUGGING'] ) {print  "<pre>checking \n". $lisresdir . $sample."</pre>";}

    if (! file_exists(  $lisresdir . $sample ) ) {
	return True;
    }
    else return False;

}

function makelock($resultdir,$sample,$listener) {

    $lockdir=getlockdir($resultdir,$sample);
    $fh = fopen(  $lockdir . $listener, 'w');
    fwrite($fh, "locked to ".$listener." on ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);
    return true;
}


/* Writing results from the POST submission data */

function writeresults($resultdir,$listener, $data) {

    foreach ($data as $key => $value) {
	if ($key[0] == 'e') {
	    $sample=$key[5].$key[6].$key[7].$key[8];    
	    
	    
	    if ($GLOBALS['DEBUGGING']) {
		print "<pre>". $resdir ."\n". $listener ."</pre>";
	    }
	    
	    /* Collect the important results and put them into a string */
	    
	    $resstring = "result: ". $data['eval_'.$sample][5]."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php'))."\n";
	    
	    /* Get the timestamps from listening and rating events:  */

	    $ct=0;
	    while (array_key_exists("sample_".$sample."_listenstamps_".$ct, $data) ) {
		$resstring .= "listenstamps_".$ct.": ".$data["sample_".$sample."_listenstamps_".$ct]."\n";
		$ct++;
	    }	
	    $ct=0;
	    while (array_key_exists("sample_".$sample."_answerstamps_".$ct, $data) ) {
		$resstring .= "answerstamps_".$ct.": ".$data["sample_".$sample."_answerstamps_".$ct]."\n";
		$ct++;
	    }

	    /* Write results into two places just to be sure */

	    $resdir=getallresultsdir($resultdir,$sample);

	    $fh = fopen(  $resdir . $listener, 'w');
	    fwrite($fh, $resstring);
	    fclose($fh);
	    
	    $lisresdir=getlistenerresultsdir($resultdir,$listener);
	    

	    $fh = fopen(  $lisresdir . $sample, 'w');
	    fwrite($fh, $resstring);
	    fclose($fh);

	    $lockdir=getlockdir($resultdir,$sample);
	    
	    if ( file_exists(  $lockdir . $listener  ) ) {
		unlink($lockdir . $listener );
	    }
	}
    }
}




/* Remove special characters from the listener name */


function cleanlistener($listener) {

    $cleanlistener = str_replace("'", '', $listener);
    $cleanlistener = str_replace('"', '', $cleanlistener);
    $cleanlistener=preg_replace( "[@]", "_at_", $cleanlistener);
    $cleanlistener=preg_replace('~[^\p{L}\p{N}]~u', '_',$cleanlistener);
    return $cleanlistener;
}


function getheader() {

    return "<HTML>
<HEADER>
<TITLE> Test on selecting sentences to training data pool  </TITLE>
<STYLE TYPE=\"text/css\">
   <!-- 
body {  font-family: Arial, Helvetica, sans-serif; 
        font-size: medium;
}

.spacer { 

height:40px;
}

.divmain {    
         left: 0; right: 0;
         margin: 0 auto;
         position: relative;
         width:600px;
         background:#dddddd; 
         border:2px solid; 
         border-radius:25px;
         padding:20px;
         z-index:2;
}

.divfooter {   
    position: fixed;
    background:#dddddd;
    border-top: 1px solid #000;
    font-size: small;
    padding:0px;
    right: 0px;
    left: 0px;
    bottom: 0px;
    clear: both;
    z-index:1;
}


.divfooterp {
   padding: 5px 5px;
   margin:0px;
}


-->
   </STYLE>
</HEADER>
<BODY>


<img src=aalto-logo-en-2.gif align=right>
<img src=s4a21-300x115.png height=80>
<br><br>




";


}

?>



