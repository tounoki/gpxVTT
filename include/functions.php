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

?>