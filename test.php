<?php 
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

$DEBUGGING=False;

$testurl="http://users.ics.aalto.fi/rkarhila/training_data_quality_test/test.php";

$samplesperpage=5;
$allowedtime=600;
#$timertext="Time remaining:";

$resultdir="/share/public_html/rkarhila/training_data_quality_test/results/";

$personalorderfile="order.txt";

# Handle first the preparations, then render page accordingly;

# Has this listener evaluated something already?

$listener=$_GET["listener"];
$sentencelist;


if ($listener) {

    $listenerdir=cleanlistener($listener);


    if ( ! file_exists(  $resultdir . $listenerdir  ) ) {
        mkdir($resultdir . $listenerdir);
    }


    if ( isset( $_POST["submissiontag"] ) ) {

	foreach ($_POST as $key => $value) {
	    if ($key[0] == 'e') {
		$sample=$key[5].$key[6].$key[7].$key[8];
		$rating=$value[5];

		writeresult($resultdir,$sample,$rating,$listener );
		#print "\n".$sample."-".$rating;
	    }
	}

    }


    
    $orderfile= $resultdir . $listenerdir . $personalorderfile;

    if ( ! file_exists(  $orderfile ) ) {
    
    # if there is no order file for this listener, then we need to create it:

     # Generate the list of sentences for the listener to evaluate;
    # Do this by shuffling the sentence list with the checksum of the
    # listener email/nickname:

	$sentencelist=range(5980,6731,1);
	$sentencelist=array_merge($sentencelist, range(6800,8000,1));
	
	srand(crc32($listener));
	SEOshuffle($sentencelist, crc32($listener));

	$fh = fopen($orderfile, 'w');

	foreach ($sentencelist as $n) {
	    fwrite($fh, $n."\n");
	}

	fclose($fh);
    }

    $fh = fopen($orderfile, 'r');
    $samples=array();
    $nrrounds=0;

    while( count($samples) < $samplesperpage ) {
	$sample=trim(fgets($fh));
	if (checkresult($resultdir, $sample, $nrrounds)) {
	    if (checklock($resultdir, $sample,$allowedtime)) {
		makelock($resultdir, $sample, $listener);
		array_push($samples,trim($sample));
	    }
	}	
    }
    fclose($fh);

}





#$sentencelist=array(  "5891", "5892", "5893", "5894", "5895" );
#srand(crc32($listener));
#SEOshuffle($sentencelist, crc32($listener));

#$samples=$sentencelist;

#$samples=array(  "5891", "5892", "5893", "5894", "5895" );


$header="<HTML>
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

print $header;





// We'll check first if we have an email or not:

$listener=$_GET["listener"];

if (!$listener) {


print "<div class=divmain>
   <p>The evaluated sentences are drawn randomly from a large pool of utterances. 
   To keep track of which ones you've evaluated already, we need some kind of identifier,
   with which you can return to this evaluation later.
   <p>This can be a valid email address or some mumbo jumbo off your head, in case you're paranoid
   that we might contact you (which just might happen in case you win in the prize draw if we have one...)
   <p>
   <form method=get action=$testurl>
Your email address or nickname:<br>
    <input type=text name=listener>
    <input type=submit>    </form>

</div>";

}
else {

    $listenerdir="".$listener;
    $listenerdir=preg_replace( "[@]", "_at_", $listenerdir);
    $listenerdir=preg_replace( "[\.]", "_", $listenerdir);
    $listenerdir=preg_replace( "[^a-zA-Z0-9_]", "", $listenerdir);

    $testurl.="?listener=${listener}";




#    print "<table width=450 border=2 align=center bgcolor=fedcff cellpadding=20><tr><td $testtdstyle>";

    print "<div class=divmain>";
#    print "<p id=countdown>".$timertext." ".($allowedtime/60).":".($allowedtime%60)."</p>";


    if ( 1 < 0 ) {
	print "<p>You have already evaluated n sentences. Thank you for that</p>
<p>Below you will find another $samplesperpage sentences synthesised using a low quality voice.
</p>";
    }
    else {
	print "<p>Below you will find $samplesperpage sentences synthesised using a low quality voice.<p>";
    }


    $intro = "<p>
Please listen to them and mark which ones are unacceptably bad 
and need to improved, and categorise the most obvious problem in those sentences.";
#    print "You must mark 1-".($samplesperpage-1)." sentences for improvement.";
    print "</p>";



    print $intro;



    print "<form name=\"ff1\" method=\"post\"  action=\"$testurl\" onsubmit=\"return validateForm();\">";

    print "<table>";
    print "<tr><th></th><th>Sample</th><th>Acceptable<br>quality</th></tr>";



    $first=true;
    foreach ($samples as $n) {
	$wavfile="bad_samples/roger_$n.wav";

	print "<tr><td>$n</td><td><audio src=$wavfile controls width=100 onplay=\"document.ff1.eval_$n.disabled=false;\"></audio> </td>";

	print "<td><select name=eval_$n size=1 onchange=\"validateForm();\">
<option name=zero value=zero default> please select... </option>
<option name=$n.2 value=$n.2 > Yes: Quality is good </option>
<option name=$n.3 value=$n.3 > Yes: It's not great but it will do </option>
<option name=$n.4 value=$n.4 > No: Mispronunciation of word(s) </option>
<option name=$n.5 value=$n.5 > No: Incomprehensible segments </option>
<option name=$n.6 value=$n.6 > No: Bad rhytmh or prosody </option>
<option name=$n.7 value=$n.7 > No: Bad audio quality (artifacts etc) </option>
</select></td></tr>";
    }

    print "</table>";
    print "<p align=center>";
    print "<input type=hidden name=submissiontag value=submitted>";
    print "<input type=hidden name=timePassed value=0>";
    print "<input type=submit name=submitbutton disabled></form>";

    print "
<p id=changeableText>

<font color=#cc0000>You have rated 0 utterances (".($samplesperpage)." required) of which <br>
0 utterances as adequate (1-".($samplesperpage-1)." required) and <br>
0 utterances as requiring improvement (1-".($samplesperpage-1)." required).</font>

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

  rated=\"You have rated \"+(yes+no)+\" utterances ($samplesperpage required) of which <br>\";
  yeses=yes+\" utterances as adequate (1-".($samplesperpage-1)." required) and <br>\";
  nos=no+\" utterances as requiring improvement (1-".($samplesperpage-1)." required).\";

  if (yes+no==$samplesperpage) {rated=green+rated+endgreen;} else {rated=red+rated+endred;}
  if (yes>0 & yes < $samplesperpage) {yeses=green+yeses+endgreen;} else {yeses=red+yeses+endred;}
  if (no>0 & no < $samplesperpage) {nos=green+nos+endgreen;} else {nos=red+nos+endred;}


 if (yes+no==$samplesperpage & yes > 0 & no>0 & clockOk() ) {
  document.getElementById('changeableText').innerHTML=rated+yeses+nos+\"<br>\"+green+
     \"Please press submit to save your evaluations!\"+endgreen;
  activateSubmit();
  return true;
 } 
 else {

  document.getElementById('changeableText').innerHTML=rated+yeses+nos+\"<br>\";
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

	
}


print "<div class=spacer> </div>";

print "
<div class=divfooter>
<p class=divfooterp>Questions, comments etc to <i>reima &#9830; karhila <b>(attention)</b> aalto &#9830 fi</i><br>
Last update to script: ".date('F d Y h:i A P T e', filemtime('test.php'));
print "</p></div>";






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


function checklock($resultdir, $sample,$timeout) {
    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/";
    if ( ! file_exists(  $lockdir  ) ) {
        mkdir($lockdir);
    }
    if ( ! file_exists(  $lockdir . $sample ) ) {
	return true;
    }
    elseif ( filemtime(  $lockdir . $sample ) <  time()-$timeout  ) {
	return true;
    }
    else return false;
}

function checkresult($resultdir,$sample,$round) {
    $resdir=$resultdir."results_round".$round."/".$sample[0].$sample[1]."/";
    if ( ! file_exists(  $resdir ) ) {
	mkdir($resdir);
    }
    if ( ! file_exists(  $resdir . $sample ) ) {
	return true;
    }
    else return false;
}

function makelock($resultdir,$sample,$listener) {
    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/";
    if ( ! file_exists(  $lockdir  ) ) {
        mkdir($lockdir);
    }
    $fh = fopen(  $lockdir . $sample, 'w');
    fwrite($fh, "locked to ".$listener." on ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);
    return true;
}

function writeresult($resultdir,$sample,$rating,$listener) {
    $listenerdir=cleanlistener($listener);

    if ( ! file_exists(  $resultdir . $listenerdir  ) ) {
        mkdir($resultdir . $listenerdir);
    }

    $round=-1;
    do {
	$round++;
	$resdir=$resultdir."results_round".$round."/".$sample[0].$sample[1]."/";
    }
    while ( file_exists( $resdir . $sample ));
    $fh = fopen(  $resdir . $sample, 'w');
    fwrite($fh, "result: ". $rating."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);

    $lisresdir=$listenerdir.$sample[0].$sample[1]."/";
    if ( ! file_exists(  $lisresdir  ) ) {
        mkdir($resultdir . $lisresdir);
    }

    $fh = fopen(  $lisresdir . $sample, 'w');
    fwrite($fh, "result: ". $rating."\nlistener: ". $listener."\ndate: ".date('F d Y h:i A P T e', filemtime('test.php')));
    fclose($fh);

    $lockdir=$resultdir."locks/".$sample[0].$sample[1]."/";

    if ( ! file_exists(  $lockdir . $sample  ) ) {
	unlink($lockdir . $sample );
    }
}

function cleanlistener($listener) {
    $listenerdir="".$listener;
    $listenerdir=preg_replace( "[@]", "_at_", $listenerdir);
    $listenerdir=preg_replace( "[\.]", "_", $listenerdir);
    $listenerdir=preg_replace( "[^a-zA-Z0-9_]", "", $listenerdir)."/";

}

?>



