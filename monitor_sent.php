<?php

  //error_reporting(E_ALL);
  //ini_set("display_errors", 1);

$GLOBALS['DEBUGGING'] = False;


include_once 'conf.php';

$qualities=Array( 'result: 2' => 'Ok: Quality is good',
		  'result: 3' => 'Ok: Its not great but it will do',
		  'result: 4' => 'Not ok: Mispronunciation of word(s)',
		  'result: 5' => 'Not ok: Incomprehensible segments',
		  'result: 6' => 'Not ok: Bad rhytmh or intonation',
		  'result: 7' => 'Not ok: Bad audio quality (artifacts etc)' 
);

$ratingnames=Array( 'result: 2' => '2',
		  'result: 3' => '3',
		  'result: 4' => '4',
		  'result: 5' => '5',
		  'result: 6' => '6',
		  'result: 7' => '7' 
);

$ratingcolors=Array( 'result: 2' => '#00ff00',
		  'result: 3' => '#00cc00',
		  'result: 4' => '#cc0000',
		  'result: 5' => '#ff0000',
		  'result: 6' => '#999900',
		  'result: 7' => '#990099' 
);


# Handle first the preparations, then render page accordingly;
# Has this listener evaluated something already?


if ($GLOBALS['DEBUGGING'] == True) {
    print "<pre>";
    print_r ($_POST);
    print_r ($_GET);
    print "</pre>";
}


print getheader();

print "<table>\n";
foreach (array_keys($qualities) as $q) {
    print "<tr><td bgcolor=$ratingcolors[$q]>$ratingnames[$q] $qualities[$q]</td></tr>";
}
print "</table><br><br>";

$sentencegroups=array_diff(scandir($resultdir.'results_all/'), array('..', '.'));

$sentcount=0;

foreach($sentencegroups as $group) {
  $sentences=array_diff(scandir($resultdir.'results_all/'.$group.'/'), array('..', '.'));

  foreach($sentences as $sentence) {

        $sentcount++;
	$ratingcount=0;
        print "<p>Utterance $sentence:\n";
	$listenedcount=0;
	$ratings=Array( 'result: 2' => 0,
		  'result: 3' => 0,
		  'result: 4' => 0,
		  'result: 5' => 0,
		  'result: 6' => 0,
		  'result: 7' => 0,
		  );
	
        $resultfiles=array_diff(scandir($resultdir.'results_all/'.$group.'/'.$sentence), array('..', '.'));
	
        foreach($resultfiles as $result) {
	  $ratingcount++;
	  $fh = fopen($resultdir.'results_all/'.$group.'/'.$sentence.'/'.$result, 'r');
	  $res=trim(fgets($fh));
	  $ratings[$res]++;
          fclose($fh);	  
        }
        print "<table border=1 width=".(10*$ratingcount)."> <tr>";
        foreach(array_keys($ratings) as $ratingstr) {
	    for ($i=0; $i<$ratings[$ratingstr];$i++ ) {
	  print "<td border=1 width=10 bgcolor='$ratingcolors[$ratingstr]'>".$ratingnames[$ratingstr]."</td>";
//	  print '<td border=1 width='.(floor(600.0*$ratings[$ratingstr]/$ratingcount))." bgcolor='$ratingcolors[$ratingstr]'>".$ratingnames[$ratingstr]."</td>";
	}
       }
       print "</tr></table>";
}
# The order of the samples is randomised for each listener,
# and the random order is saved in a file.
 }   








print "<div class=spacer> </div>";

print "
<div class=divfooter>
<p class=divfooterp>$footertext
Last update to script: ".date('F d Y h:i A P T e', filemtime('test.php'));
print "</p></div>";

print "</body></hmtl>";






/* File functions for bookkeeping */


function getorderfiledir($resultdir) {
    $orderdir=$resultdir ."orderfiles/";
    if ( ! file_exists( $orderdir ) ) {
    	#mkdir($orderdir, 0777, true);
    } 
    return $orderdir;
}

function getstatfiledir($resultdir) {
    $statdir=$resultdir ."listenerstatfiles/";
    if ( ! file_exists( $statdir ) ) {
    	#mkdir($statdir, 0777, true);
    } 
    return $statdir;
}


function getlockdir($resultdir,$sample) {
    $lockdir =  $resultdir."locks/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $lockdir  ) ) {
	#mkdir($lockdir, 0777, true);
    }
    return $lockdir;
}

function getallresultsdir($resultdir,$sample) {
    $resdir=$resultdir."results_all/".$sample[0].$sample[1]."/".$sample."/";
    if ( ! file_exists(  $resdir ) ) {
	#mkdir($resdir, 0777, true);
    }
    return $resdir;
}

function getlistenerresultsdir($resultdir,$listener) {
    $lisresdir=$resultdir."/listeners/".$listener."/";
    if ( ! file_exists( $lisresdir  ) ) {
	#mkdir( $lisresdir, 0777, true);
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
    fwrite($fh, "locked to ".$listener." on ".date('F d Y h:i A P T e')."\n");
    fclose($fh);
    return true;
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

.divlistenerinfo {
 border:1px solid; 
 border-radius:5px;
 padding:5px;
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



