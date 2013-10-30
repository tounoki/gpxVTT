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
/*
Plugin Name: gpxvtt
Version: 0.1-RC2
Plugin URI: http://tounoki.org
Description: ATTENTION encore en développement / GpxVTT est un plugin qui sert à insérer des traces GPS au format gpx sur votre blog/site. A partir de la trace téléchargée et d'un shortcode, le plugin génère le tracé de la carte sur un fond OpenStreetMap, des statistiques de route (longueur, dénivelé positif), un graphe de dénivelé (le tout avec un positionnement dynamique pour la correspondance trajet/dénivelé).
Author: tounoki (Nicolas Poulain)
Author URI: http://tounoki.org
*/

if ( ! defined( 'WP_CONTENT_URL' ) ) define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )  define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR .DIRECTORY_SEPARATOR. 'plugins' );

if (! defined('GPXVTT_SHORTCODE')) define('GPXVTT_SHORTCODE','gpxvtt');
if (! defined('GPXVTT_PLUGIN_URL')) define ("GPXVTT_PLUGIN_URL", WP_PLUGIN_URL."/gpxvtt/");
if (! defined('GPXVTT_PLUGIN_DIR')) define ("GPXVTT_PLUGIN_DIR",WP_PLUGIN_DIR.DIRECTORY_SEPARATOR."gpxvtt".DIRECTORY_SEPARATOR);

require_once( GPXVTT_PLUGIN_DIR.'include/functions.php' ) ;
require_once( GPXVTT_PLUGIN_DIR.'include/gpxvtt.class.php' ) ;

$inst_gpxvtt = new gpxvtt() ;
$inst_gpxvtt->addAdminMenu() ;
$inst_gpxvtt->addHeader() ;
$inst_gpxvtt->addShortcode() ;
?>
