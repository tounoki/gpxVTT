<?php
/*****************************************************************************************
** © 2013 POULAIN Nicolas – nicolas.poulain@ouvaton.org **
** **
** Ce fichier est une partie du logiciel libre GpxVTT, licencié **
** sous licence "GNU GPL V3". **
** La licence est décrite plus précisément dans le fichier : LICENSE.txt **
** **
** ATTENTION, CETTE LICENCE EST GRATUITE ET LE LOGICIEL EST **
** DISTRIBUÉ SANS GARANTIE D'AUCUNE SORTE **
** ** ** ** **
** This file is a part of the free software project GpxVTT,
** licensed under the "GNU GPL V3". **
**The license is discribed more precisely in LICENSES.txt **
** **
**NOTICE : THIS LICENSE IS FREE OF CHARGE AND THE SOFTWARE IS DISTRIBUTED WITHOUT ANY **
** WARRANTIES OF ANY KIND **
*****************************************************************************************/
require_once ('./jpgraph/jpgraph.php');
require_once ('./jpgraph/jpgraph_line.php');

if ( empty($_GET['file']) ) die('ERROR') ;

function dataClean($data,$type="text") {
	// faire un case plutôt que l'enfilade de if :(
	switch ($type) {
	case "text":
		// suppression des caractères pas catholiques
		$data = preg_replace('/[^[:alnum:]éèàçâêüô@-_.]/i','_',$data);
		//$data = preg_replace("[^([:alnum:]éèàçâê\.ô\-_\ )+]"," ",$data);
		// suppression des espaces répétés
		$data = preg_replace('/\s\s+/', ' ', $data) ;
		break ;
	case "int" :
		$data = preg_replace('/[^0-9]/','',$data);
		if ( $data == "" ) $data = 0 ;
		$data = (int) $data ;
		break ;
	}
	return $data ;
}

$file = $_GET['file'] ;
$graphWidth = dataClean($_GET['graphWidth'],'int') ;
$graphHeight = dataClean($_GET['graphHeight'],'int') ;
$graphTitle = dataClean($_GET['graphTitle']) ;
$xaxisTitle =  dataClean($_GET['xaxisTitle']) ;
$yaxisTitle =  dataClean($_GET['yaxisTitle']) ;

$gpx = simplexml_load_file("$file") ;

function get_distance_km($pA, $pB) {
	$lat1 = $pA['lat'] ;
	$lng1 = $pA['lon'] ;
	$lat2 = $pB['lat'] ;
	$lng2 = $pB['lon'] ;
	$earth_radius = 6378137;   // Terre = sphère de 6378km de rayon
	$rlo1 = deg2rad($lng1);
	$rla1 = deg2rad($lat1);
	$rlo2 = deg2rad($lng2);
	$rla2 = deg2rad($lat2);
	$dlo = ($rlo2 - $rlo1) / 2;
	$dla = ($rla2 - $rla1) / 2;
	$a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
	$d = 2 * atan2(sqrt($a), sqrt(1 - $a));
	return ($earth_radius * $d / 1000) ;
}

//Create some test data
$xdata = array();
$ydata = array();

$i = 0 ;
$lastPoint = NULL ;
$distGPX = 0 ;
foreach ( $gpx->trk->trkseg->trkpt as $point)  {

	$p['lat'] = (float) $point['lat'] ;
	$p['lon'] = (float) $point['lon'] ;
	if ( $lastPoint != NULL ) {
		$distGPX += get_distance_km($p,$lastPoint) ;
	}
	//$point->lat $point->lng
	$lastPoint = $p ;

	$xdata[$i] = round($distGPX,1) ;
	$ydata[$i] = (float) $point->ele >= 0 ? (float) $point->ele : 0 ;
	$i++ ;
}

// Create the graph.
$graph  = new Graph($graphWidth, $graphHeight); // en pixels
$graph->title->Set($graphTitle." ".$gpx->trk->name." / ".round($distGPX,1)."km") ;
$graph->SetScale( 'textlin',round(min($ydata)/10)*10-20,round(max($ydata)/10)*10+20 ) ;

$graph->xaxis->title->Set($xaxisTitle); // Denivele en m
$graph->yaxis->title->Set($yaxisTitle); // "Hauteur en m"

//$graph->yaxis->scale->ticks->Set(10,200);

// Setup margin color
$graph->SetMarginColor('green@0.95') ;

// Adjust the margin to make room for the X-labels
//$graph->SetMargin(40,30,40,120);
$graph->SetMargin(55,20,0,50) ;

// Turn the tick marks out from the plot area
$graph->xaxis->SetTickSide(SIDE_TOP);
//$graph->yaxis->SetTickSide(SIDE_LEFT);

$graph->yaxis->SetTitleMargin(40) ; // ajustement du titre des y

$graph->xaxis->SetTickLabels($xdata);
$graph->xaxis->SetTextLabelInterval( count($xdata)/10 >= 1 ? count($xdata)/10 : 1 );

$p0 =new LinePlot($ydata);
$p0->SetFillColor('sandybrown');
$ap = new AccLinePlot(array($p0));
// Add the plot to the graph
$graph->Add($ap);

// Set the angle for the labels to 90 degrees
$graph->xaxis->SetLabelAngle(0);

// Display the graph
$graph->Stroke();

?>