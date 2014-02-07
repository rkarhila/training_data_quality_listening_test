<?php 
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

$GLOBALS['DEBUGGING'] = False;


include_once 'conf.php';

# Handle first the preparations, then render page accordingly;
# Has this listener evaluated something already?

$listener=$_GET["listener"];
$sentencelist;


if ($listener) {

    $listenerdir=cleanlistener($listener)."/";

    if ( ! file_exists(  $resultdir . $listenerdir  ) ) {
        mkdir($resultdir . $listenerdir, 0777, true);
    }

    if ( isset( $_POST["submissiontag"] ) ) {

	# If POST includes evaluation results, we'll write them to disk

	writeresults($resultdir, $listener, $_POST);

    }

    # The order of the samples is randomised for each listener,
    # and the random order is saved in a file.
    
    $orderfile= $resultdir . $listenerdir . $personalorderfile;


    if ( ! file_exists(  $orderfile ) ) {
    
        # if there is no order file for this listener, then we need to create it:

        # Generate the list of sentences for the listener to evaluate;
        # Do this by shuffling the sentence list with the checksum of the
        # listener email/nickname:

	$sentencelist=range(1001,1300,1);

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

    while( count($samples) < $samplesperpage ) {
	$sample=trim(fgets($fh));
	if ($sample) {
	    if (checklistenerresults($resultdir, $listener, $sample )) {
		if (checkresults($resultdir, $sample) + checklocks($resultdir, $sample,$listener,$allowedtime) < $min_evals)
		    makelock($resultdir, $sample, $listener);
		array_push($samples,trim($sample));
	    }
	    else
		$alreadyevaluated++;
	}

	else break;
    }

    fclose($fh);

}

if ($alreadyevaluated >= $requiredevaluations) {
    print "Well done! That's all. Thank you.";
}
else
{
    
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

    $listenerdir=cleanlistener($listener);

    $testurl.="?listener=${listener}";




    print "<div class=divmain>";

    if ( $alreadyevaluated > 0 ) {
	print "<p>You have already evaluated $alreadyevaluated out of $requiredevaluations sentences. Thank you for that</p>
<p>Below you will find another set of sentences synthesised using a low quality voice.
</p>";
    }
    else {
	print "<p>Below you will find $samplesperpage sentences synthesised using a low quality voice.<p>";
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
	print "<tr><td>$n</td><td><audio id=\"audio_$n\" src=$wavfile onended=enable_playbuttons() ></audio>";

	print "<button  id=\"playbutton_$n\" onclick=\"playSample_$n()\"> &#9658; play </button> </td>";



	print "<td><select name=eval_$n size=1 onchange=\"validateForm_$n();\">
<option name=zero value=zero default> please select... </option>
<option name=$n.2 value=$n.2 > Ok: Quality is good </option>
<option name=$n.3 value=$n.3 > Ok: It's not great but it will do </option>
<option name=$n.4 value=$n.4 > Not ok: Mispronunciation of word(s) </option>
<option name=$n.5 value=$n.5 > Not ok: Incomprehensible segments </option>
<option name=$n.6 value=$n.6 > Not ok: Bad rhytmh or prosody </option>
<option name=$n.7 value=$n.7 > Not ok: Bad audio quality (artifacts etc) </option>
</select></td>  
</tr>";
    }

    print "</table>";
    print "<p align=center>";
    print "<input type=hidden name=submissiontag value=submitted>";
    print "<input type=hidden name=timePassed value=0>";
    print "<input type=submit onclick=\"beforeSubmit()\" name=submitbutton></form>";

    print "
<p id=changeableText>

<font color=#cc0000>You have rated 0 utterances (".($samplesperpage)." required)<!-- of which <br>
0 utterances as adequate (1-".($samplesperpage-1)." required) and <br>
0 utterances as requiring improvement (1-".($samplesperpage-1)." required).--></font>

</p>";

#    print "</td></tr></table>";
    print "</div>";


    print "\n\n<script type=\"text/JavaScript\">

var green=\"<font color=#00cc00>\";
var red=\"<font color=#cc0000>\";
var endgreen=\"</font>\";
var endred=\"</font>\";

function validateForm() {
//alert(\"Validate the number of corrections (1-4)\");
//Let's count no and yes answers:\n
var no=0;var yes=0;
";

	
    foreach ($samples as $n) {
	print "
var val=document.ff1.eval_$n.value;
";
	
	    print"
if (val == $n.2) { no++; };";

	    print "
//if (val == $n.3 || val == $n.4 || val == $n.5 || val $n.6 ) { yes++; };
if (val == $n.3 | val == $n.4 | val == $n.5 | val == $n.6)  { yes++; };
\n";

    }
    print "
// alert(\"yes:\"+yes+\" no:\"+no);

  rated=\"You have rated \"+(yes+no)+\" utterances ($samplesperpage required) \" //of which <br>\";
  yeses=yes+\" utterances as adequate (1-".($samplesperpage-1)." required) and <br>\";
  nos=no+\" utterances as requiring improvement (1-".($samplesperpage-1)." required).\";

  if (yes+no==$samplesperpage) {rated=green+rated+endgreen;} else {rated=red+rated+endred;}
//  if (yes>0 & yes < $samplesperpage) {yeses=green+yeses+endgreen;} else {yeses=red+yeses+endred;}
//  if (no>0 & no < $samplesperpage) {nos=green+nos+endgreen;} else {nos=red+nos+endred;}


// if (yes+no==$samplesperpage & yes > 0 & no>0 & clockOk() ) {
 if (yes+no==$samplesperpage) {
  document.getElementById('changeableText').innerHTML=rated+\"<br>\"+green+
     \"Please press submit to save your evaluations!\"+endgreen;
  activateSubmit();
  return true;
 } 
 else {

//  document.getElementById('changeableText').innerHTML=rated+yeses+nos+\"<br>\";
  document.getElementById('changeableText').innerHTML=rated+\"<br>\";
  deactivateSubmit();
  return false;
 }
}
</script>

";

  print "\n\n<script type=\"text/JavaScript\">
function activateSubmit() {
//alert(\"activating\");
document.ff1.submitbutton.disabled=false;
}
</script>

";

  print "\n\n<script type=\"text/JavaScript\">
function deactivateSubmit() {
//alert(\"deactivating\");
document.ff1.submitbutton.disabled=true;
}
</script>

";


print "\n\n<script type=\"text/JavaScript\">
function clockOk() {
// if (timeLeft>0) { return true;}
 if (timePassed > 20) { return true; }
 else  {return false;}
}
</script>

";


print "\n\n<script type=\"text/JavaScript\">
//var timeLeft=$allowedtime;
var timePassed=0;
var myVar=setInterval(function(){myTimer()},1000);

function myTimer()
{
 timePassed++;
 document.ff1.timePassed.value=timePassed;
// timeLeft--;
// var min=parseInt(timeLeft/60);
// var sec=timeLeft%60;

// if (timeLeft>0) {
//  var tim=\"$timertext \"+min+\":\"+sec;
//  if (timeLeft < 60) {
//   tim=\"<FONT COLOR=#FF0000>\"+tim+\"</FONT>\";
//  }
//  document.getElementById(\"countdown\").innerHTML=tim;
// }
// else 
// {
//  document.getElementById(\"countdown\").innerHTML=\"0:00\";
//  deactivateSubmit();
//";
foreach ($samples as $n) {
    print "
//document.ff1.eval_$n.disabled=true;
";
}
print "
//  document.getElementById('changeableText').innerHTML=red+\"Unfortunately you have run out of time to evaluate these sentences.<br> Please reload.<br> Some of the sentences might have been given to another listener for evaluation.\"+endred;
// }
}
</script>

";

print "\n\n<script type=\"text/JavaScript\">";

foreach ($samples as $n) {
    print "
function validateForm_$n() {
   answerstamps_$n.push( ( new Date().getTime() -loadstamp )/1000);
   validateForm();
}
";
}




print "</script>";



print "\n\n<script type=\"text/JavaScript\">";
foreach ($samples as $n) {

    print"


function playSample_$n() {
   audio_$n.play();
   playbutton_$n.innerHTML=\" <font color=#00cc00><b>&#9658; play</b></font> \";
   playstamps_$n.push( (new Date().getTime() -loadstamp)/1000);
   disable_playbuttons()
};";
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

</script>";




print "\n\n<script type=\"text/JavaScript\">
";

foreach ($samples as $n) {

    print "
loadstamp=new Date().getTime();
playstamps_$n = new Array();
answerstamps_$n = new Array();
";
}

print "
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


print "<div class=spacer> </div>";

print "
<div class=divfooter>
<p class=divfooterp>$footertext
Last update to script: ".date('F d Y h:i A P T e', filemtime('test.php'));
print "</p></div>";

print "</body></hmtl>";
}



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


function checklocks($resultdir, $sample,$listener, $timeout) {
    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $lockdir  ) ) {
        mkdir($lockdir, 0777, true);
    }
    $locks=array_diff(scandir($lockdir), array('..', '.'));
    $lockcount=0;
    foreach ($locks as $l) {
	if ( filemtime(  $lockdir . $l ) <  time()-$timeout  ) {
	    if ( $l != cleanlistener($listener)) {
		$lockcount++;
	    }
	}
    }
    return $lockcount;
}

function checkresults($resultdir,$sample) {
    $resdir=$resultdir."results_all/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $resdir ) ) {
	mkdir($resdir, 0777, true);
    }

    $answers=array_diff(scandir($resdir), array('..', '.'));

    $answercount=0;
    foreach ($answers as $a) {
	    $answercount++;
    }    
    return $answercount;

}

function checklistenerresults($resultdir,$listener,$sample) {
    
    $cleanlistener=cleanlistener($listener);

    $lisresdir="listeners/".$cleanlistener."/";

    if ( ! file_exists(  $resultdir . $lisresdir  ) ) {
	mkdir($resultdir . $lisresdir, 0777, true);
    }
    if ($GLOBALS['DEBUGGING'] ) {print  "<pre>checking \n".$resultdir . $lisresdir . $sample."</pre>";}
    if (! file_exists(  $resultdir . $lisresdir . $sample ) ) {
	return True;
    }
    else return False;

}

function makelock($resultdir,$sample,$listener) {
    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $lockdir  ) ) {
        mkdir($lockdir, 0777, true);
    }
    $fh = fopen(  $lockdir . cleanlistener($listener), 'w');
    fwrite($fh, "locked to ".$listener." on ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);
    return true;
}
/*
function writeresult($resultdir,$sample,$rating,$listener) {

    $cleanlistener=cleanlistener($listener);

    if ( ! file_exists(  $resultdir . "listeners/". $cleanlistener  ) ) {
        mkdir($resultdir."listeners/". $cleanlistener, 0777, true);
    }

    $resdir=$resultdir."results_all/".$sample[0].$sample[1]."/".$sample."/";
    mkdir($resdir, 0777, true);

    print "<pre>". $resdir . $cleanlistener ."</pre>";

    $fh = fopen(  $resdir . $cleanlistener, 'w');
    fwrite($fh, "result: ". $rating."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);

    $lisresdir="listeners/".$cleanlistener."/";
    if ( ! file_exists(  $lisresdir  ) ) {
        mkdir($resultdir . $lisresdir, 0777, true);
    }

    $fh = fopen(  $resultdir . $lisresdir . $sample, 'w');
    print "<pre>opened ". $lisresdir . $sample . " and writing</pre>";
    fwrite($fh, "result: ". $rating."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);

    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/";

    if ( ! file_exists(  $lockdir . $sample  ) ) {
	unlink($lockdir . $sample );
    }
}
*/

function writeresults($resultdir,$listener, $data) {

    $cleanlistener=cleanlistener($listener);
    
    if ( ! file_exists(  $resultdir . "listeners/". $cleanlistener  ) ) {
        mkdir($resultdir."listeners/". $cleanlistener, 0777, true);
    }

    foreach ($data as $key => $value) {
	if ($key[0] == 'e') {
	    $sample=$key[5].$key[6].$key[7].$key[8];    
	    
	    $resdir=$resultdir."results_all/".$sample[0].$sample[1]."/".$sample."/";
	    mkdir($resdir, 0777, true);
	    
	    if ($GLOBALS['DEBUGGING']) {
		print "<pre>". $resdir . $cleanlistener ."</pre>";
	    }
	    
	    $fh = fopen(  $resdir . $cleanlistener, 'w');
	    fwrite($fh, "result: ". $data['eval_'.$sample][5]."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php'))."\n");
	    
	    $ct=0;
	    while (array_key_exists("sample_".$sample."_answerstamps_".$ct, $data) || array_key_exists("sample_".$sample."_listenstamps_".$ct, $data)) {
		if (array_key_exists("sample_".$sample."_listenstamps_".$ct, $data) )
		{
		    fwrite($fh, "listenstamps_".$ct.": ".$data["sample_".$sample."_listenstamps_".$ct]."\n");
		}	
		
		if (array_key_exists("sample_".$sample."_answerstamps_".$ct, $data) )
		{
		    fwrite($fh, "answerstamps_".$ct.": ".$data["sample_".$sample."_answerstamps_".$ct]."\n");
		}
		$ct++;
	    }


	    fclose($fh);

	    $lisresdir="listeners/".$cleanlistener."/";
	    if ( ! file_exists(  $lisresdir  ) ) {
		mkdir($resultdir . $lisresdir, 0777, true);
	    }
	    
	    $fh = fopen(  $resultdir . $lisresdir . $sample, 'w');
	    if ($GLOBALS['DEBUGGING']) {
		print "<pre>opened ". $resultdir . $lisresdir . $sample . " and writing</pre>";
	    }
	    fwrite($fh, "result: ". $rating."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php')));
	    $ct=0;
	    while (array_key_exists("sample_".$sample."_answerstamps_".$ct, $data) || array_key_exists("sample_".$sample."_listenstamps_".$ct, $data)) {
		if (array_key_exists("sample_".$sample."_listenstamps_".$ct, $data) )
		{
		    fwrite($fh, "listenstamps_".$ct.": ".$data["sample_".$sample."_listenstamps_".$ct]."\n");
		}	
		
		if (array_key_exists("sample_".$sample."_answerstamps_".$ct, $data) )
		{
		    fwrite($fh, "answerstamps_".$ct.": ".$data["sample_".$sample."_answerstamps_".$ct]."\n");
		}
		$ct++;
	    }
	    fclose($fh);
	    
	    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/";
	    
	    if ( ! file_exists(  $lockdir . $sample  ) ) {
		unlink($lockdir . $sample );
	    }
	}
    }
}
    






function cleanlistener($listener) {
    $cleanlistener="".$listener;
    $cleanlistener=preg_replace( "[@]", "_at_", $cleanlistener);
    $cleanlistener=preg_replace( "[\.]", "_", $cleanlistener);
    $cleanlistener=preg_replace( "[^a-zA-Z0-9_]", "", $cleanlistener);
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



