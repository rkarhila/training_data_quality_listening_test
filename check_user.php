<?php

include_once 'conf.php';

$listener=cleanlistener($_GET["listener"]);


  
$orderfile= getorderfiledir($resultdir).$listener ."_". $personalorderfile;

if ( ! file_exists(  $orderfile ) ) {
    print "new";
}
else
{
    print "old";
}

function getorderfiledir($resultdir) {
    $orderdir=$resultdir ."orderfiles/";
    if ( ! file_exists( $orderdir ) ) {
	mkdir($orderdir, 0777, true);
    } 
    return $orderdir;
}

function cleanlistener($listener) {

    $cleanlistener = str_replace("'", '', $listener);
    $cleanlistener = str_replace('"', '', $cleanlistener);
    $cleanlistener=preg_replace( "[@]", "_at_", $cleanlistener);
    $cleanlistener=preg_replace('~[^\p{L}\p{N}]~u', '_',$cleanlistener);
    return $cleanlistener;
}



?>