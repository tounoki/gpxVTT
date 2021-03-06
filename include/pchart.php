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
//if ( empty($_GET['file']) ) die('ERROR') ;

function dataClean($data,$type="text") {
	// faire un case plutôt que l'enfilade de if :(
	switch ($type) {
	case "text":
		// suppression des caractères pas catholiques
		$data = preg_replace('/[^[:alnum:]éèàçâêüô@-_. ]/i','',$data);
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

// faire sécurisation
if ( empty($_GET['file']) ) die('ERROR') ;

if ( empty($_GET['file']) ) {
	$gpx = simplexml_load_file("./SaintJacques.gpx") ;
} else {
	$file = $_GET['file'] ;
	$gpx = simplexml_load_file($file) ;
}

$graphWidth = (isset($_GET['graphWidth']) ) ? dataClean($_GET['graphWidth'],'int') : 600 ;
$graphHeight = (isset($_GET['graphHeight']) ) ? dataClean($_GET['graphHeight'],'int') : 350 ;
$graphTitle = (isset($_GET['graphTitle']) ) ? dataClean($_GET['graphTitle']) : "Titre du graphe" ;
$xaxisTitle = (isset($_GET['xaxisTitle']) ) ? dataClean($_GET['xaxisTitle']) : "Titre des x" ;
$yaxisTitle = (isset($_GET['yaxisTitle']) ) ? dataClean($_GET['yaxisTitle']) : "Titre des y" ;

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

 /* CAT:Scatter chart */

 /* pChart library inclusions */
 include("./pchart/class/pData.class.php");
 include("./pchart/class/pDraw.class.php");
 include("./pchart/class/pImage.class.php");
 include("./pchart/class/pScatter.class.php");

 /* Create the pData object */
 $myData = new pData();

 /* Create the X axis and the binded series */
 //for ($i=0;$i<=360;$i=$i+10) { $myData->addPoints(cos(deg2rad($i))*20,"Probe 1"); }
 //for ($i=0;$i<=360;$i=$i+10) { $myData->addPoints(sin(deg2rad($i))*20,"Probe 2"); }

 $myData->addPoints($xdata,"AxeX");
 $myData->setAxisName(0,$xaxisTitle);
 $myData->setAxisXY(0,AXIS_X);
 $myData->setAxisPosition(0,AXIS_POSITION_BOTTOM);
 $myData->setAxisDisplay(0,AXIS_FORMAT_METRIC);

 /* Create the Y axis and the binded series */
 $myData->addPoints($ydata,"AxeY");
 $myData->setSerieOnAxis("AxeY",1);
 $myData->setAxisName(1,$yaxisTitle);
 $myData->setAxisXY(1,AXIS_Y);
 //$myData->setAxisUnit(1,"meter");
 $myData->setAxisPosition(1,AXIS_POSITION_LEFT);

 /* Create the 1st scatter chart binding */
 $myData->setScatterSerie("AxeX","AxeY",0);
 $myData->setScatterSerieDescription(0,"Denivelé");
 //$myData->setScatterSerieTicks(0,4);
 $myData->setScatterSerieColor(0,array("R"=>0,"G"=>0,"B"=>0));

 /* Create the 2nd scatter chart binding
 $myData->setScatterSerie("Probe 2","Probe 3",1);
 $myData->setScatterSerieDescription(1,"Last Year");
 */

 /* Create the pChart object */
 $myPicture = new pImage($graphWidth,$graphHeight,$myData);

 /* Draw the background */
 //$Settings = array("R"=>170, "G"=>183, "B"=>87, "Dash"=>1, "DashR"=>190, "DashG"=>203, "DashB"=>107);
 $Settings = array("R"=>170, "G"=>183, "B"=>87, "DashR"=>190, "DashG"=>203, "DashB"=>107);
 $myPicture->drawFilledRectangle(0,0,$graphWidth,$graphHeight,$Settings);

 /* Overlay with a gradient */
 //$Settings = array("StartR"=>219, "StartG"=>231, "StartB"=>139, "EndR"=>1, "EndG"=>138, "EndB"=>68, "Alpha"=>50);
 $Settings = array("StartR"=>255, "StartG"=>255, "StartB"=>255, "EndR"=>219, "EndG"=>231, "EndB"=>139, "Alpha"=>50);
 $myPicture->drawGradientArea(0,0,$graphWidth,$graphHeight,DIRECTION_VERTICAL,$Settings);
 //$myPicture->drawGradientArea(0,0,$graphWidth,20,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80));
 $myPicture->drawGradientArea(0,$graphHeight-20,$graphWidth,$graphHeight,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>80));

 /* Write the picture title */
 $myPicture->setFontProperties(array("FontName"=>"./pchart/fonts/verdana.ttf","FontSize"=>9));
 $myPicture->drawText(10,$graphHeight-4, $graphTitle ,array("R"=>255,"G"=>255,"B"=>255));

 /* Add a border to the picture */
 $myPicture->drawRectangle(0,0,$graphWidth-1,$graphHeight-1,array("R"=>0,"G"=>0,"B"=>0));

 /* Set the default font */
 $myPicture->setFontProperties(array("FontName"=>"./pchart/fonts/pf_arma_five.ttf","FontSize"=>6));
 /* Set the graph area */
 $myPicture->setGraphArea(55,20,$graphWidth-20,$graphHeight-60); // deal with jpgraph and d3 configuration $graph->SetMargin(55,20,0,50)

 /* Create the Scatter chart object */
 $myScatter = new pScatter($myPicture,$myData);

 $AxisBoundaries = array(0=>array("Min"=> min($xdata) , "Max" => max($xdata) ), 1=>array("Min"=> min($ydata) , "Max" => max($ydata) ) ) ;
 $ScaleSettings = array("Mode"=>SCALE_MODE_MANUAL,"ManualScale"=>$AxisBoundaries,"DrawSubTicks"=>TRUE);

 /* Draw the scale */
 $myScatter->drawScatterScale($ScaleSettings);

 /* Turn on shadow computing */
 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>10));

 /* Draw a scatter plot chart */
 $myScatter->drawScatterLineChart();

 /* Draw the legend */
 $myScatter->drawScatterLegend(280,380,array("Mode"=>LEGEND_HORIZONTAL,"Style"=>LEGEND_NOBORDER));


 /* Render the picture (choose the best way) */
 //$myPicture->autoOutput("pictures/example.drawLineChart.png");
 $myPicture->stroke() ;
?>