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
if (!class_exists("gpxvtt")) {
	class gpxvtt {
		/**
		 * adress of gpx file to exploit
		 * @var string
		 */
		protected $fileAdress ; // adress of exploited gpx file
		/**
		 * option_name that is stored in the database
		 * @var string
		 */
		private $adminOptionsName = 'wp_gpxvttAdminOptions' ;
		/**
		 * cache for calcul
		 * @var array
		 */
		private $buffer ;

		/**
		 * constructor
		 * @param string
		 * @return bool
		 */
		function __construct($file=null) {
			$this->buffer = NULL ;
			$this->fileAdress = $file ;
			$this->getAdminOptions();
			// start internationalisation
			load_plugin_textdomain('gpxvtt',GPXVTT_PLUGIN_DIR.'lang/'); // internationalisation
			return true ;
		}
		/**
		 * add gpx to the autorised download files with wordpress fonction add_filter('upload_mimes',...)
		 * see http://codex.wordpress.org/Plugin_API/Filter_Reference/upload_mimes
		 * @param none
		 * @return bool
		 */
		function addTypeMime() {
			add_filter('upload_mimes', array(&$this,'customUploadMimes') ) ;
			return true ;
		}
		/**
		 * add gpx to the autorised download files with wordpress fonction add_filter('upload_mimes',...)
		 * deal with addTypeMime()
		 * @param array
		 * @return array
		 */
		function customUploadMimes($existing_mimes=array()) {
			// Add file extension 'extension' with mime type 'mime/type'
			$existing_mimes['extension'] = 'mime/type';
			// add as many as you like e.g.
			if ( !isset($existing_mimes['gpx']) )
				$existing_mimes['gpx'] = 'text/gpx' ;
			// remove items here if desired ...
			// unset...
			// and return the new full result
			return $existing_mimes;
		}
		/**
		 * Set default options at the first call of the plugin, else call the saved in bdd of wordpress
		 * Changed by the admin page
		 * @param string
		 * @return bool
		 */
		private function getAdminOptions() {
			$wp_gpxvttAdminOptions = array(
				'gpxcolorDefault' => 'blue',
				'gpxwidthDefault' => 5,
				'gpxopacityDefault' => 0.5,
				'zoomDefault' => 12,
				'widthDefault' => '500px',
				'heightDefault' => '300px',
				'graphtypeAdmin' => 'd3', // not in shortcode
				'graphwidthDefault' => 600,
				'graphheightDefault' => 250,
				'graphcolorDefault' => "steelblue",
				'graphlinecolorDefault' => "blue"
				) ;
			$wp_gpxvttOptions = get_option($this->adminOptionsName) ;
			if (!empty($wp_gpxvttOptions)) {
				foreach ($wp_gpxvttOptions as $key => $option)
					$wp_gpxvttAdminOptions[$key] = $option;
			}
			update_option($this->adminOptionsName, $wp_gpxvttAdminOptions) ;
			return $wp_gpxvttAdminOptions ;
		}
		/**
		 * list of scripts and css styles required by the plugin
		 * @param none
		 * @return bool
		 */
		function addHeaderCode() {
			print "\n<!-- gpxvtt plugin insertion start -->\n" ;
			print "<!-- Ajout OpenLayers with wp_enqueue http://openlayers.org/api/2.11/OpenLayers.js -->\n" ;
			// there is some conflicts with using the first line with the twenty twelve theme
			// test with online version of openlayers for problems with displaying map
			//wp_enqueue_script('OpenLayers', plugins_url('javascript/OpenLayers/OpenLayers.js',dirname(__FILE__))) ;
			wp_enqueue_script('OpenLayers', 'http://openlayers.org/api/2.11/OpenLayers.js',dirname(__FILE__)) ;

			print "<!-- Ajout OSM wp_enqueue -->\n" ;
			wp_enqueue_script('OpenStreetMap',plugins_url('javascript/OpenStreetMap.js',dirname(__FILE__))) ;

			wp_register_style('gpxvtt',plugins_url('css/gpxvtt.css',dirname(__FILE__)),'',false,'screen');
			wp_enqueue_style('gpxvtt');

			wp_register_style('jquery-ui',plugins_url('css/jquery-ui.css',dirname(__FILE__)),'',false,'screen');
			wp_enqueue_style('jquery-ui') ;

			wp_enqueue_script('jquery') ;
			wp_enqueue_script('jquery-ui-core') ;
			wp_enqueue_script('jquery-ui-slider') ;

			wp_enqueue_script('d3js',plugins_url('javascript/d3.v3.min.js',dirname(__FILE__))) ;
			print "<!-- gpxvtt plugin insertion end -->\n" ;
			return true ;
		}

		/**
		 * public method to valid header needs, add_action from wordpress
		 * @param none
		 * @return bool always true
		 */
		function addHeader() {
			add_action('wp_head', array(&$this,'addHeaderCode'), 1) ;
			return true ;
		}
		/**
		 * set file adress of gpx file
		 * @param string fileadress
		 * @return bool
		 */
		function setGpxFile($file) {
			$this->fileAdress = $file ;
			return true ;
		}
		/**
		 * Get the coordinates of the median point of the gps trace
		 * @param none
		 * @return array =point with lat+lon
		 */
		private function getCenter() {
			if ( isset($this->buffer['Center']) ) // get cache if exist
				return $this->buffer['Center'] ;
			$gpx = simplexml_load_file($this->fileAdress) ;
			$i = true ;
			foreach ( $gpx->trk->trkseg->trkpt as $point)  {
				$p['lat'] = (float) $point['lat'] ;
				$p['lon'] = (float) $point['lon'] ;
				$LAT[] = $p['lat'] ;
				$LON[] = $p['lon'] ;
			}
			$center['lat'] = ( max($LAT) + min($LAT) ) / 2 ;
			$center['lon'] = ( max($LON) + min($LON) ) / 2 ;
			$this->buffer['Center'] = $center ;
			return $center ;
		}
		/**
		 * Get the coordinates of the first point of the gps trace
		 * @param none
		 * @return array =point with lat+lon
		 */
		private function getStart() {
			if ( isset($this->buffer['Start']) ) // get cache if exist
				return $this->buffer['Start'] ;
			$gpx = simplexml_load_file($this->fileAdress) ;
			$point = $gpx->trk->trkseg->trkpt[0] ;
				$p['lat'] = (float) $point['lat'] ;
				$p['lon'] = (float) $point['lon'] ;
			$this->buffer['Start'] = $p ;
			return $p ;
		}
		/**
		 * Get the coordinates of the last point of the gps trace
		 * @param none
		 * @return array =point with lat+lon
		 */
		private function getEnd() {
			if ( isset($this->buffer['End']) ) // get cache if exist
				return $this->buffer['End'] ;
			$gpx = simplexml_load_file($this->fileAdress) ;
			$point = $gpx->trk->trkseg->trkpt[ $gpx->trk->trkseg->trkpt->count()-1 ] ;
				$p['lat'] = (float) $point['lat'] ;
				$p['lon'] = (float) $point['lon'] ;
			$this->buffer['End'] = $p ;
			return $p ;
		}
		/**
		 * Calculates the path length
		 * @param string $unit km / TODO feet and m
		 * @param int $precision of the return value
		 * @return float
		 */
		private function getLong($unit='km',$precision=1) {
			if ( isset($this->buffer['Long'][$unit][$precision]) )
				return $this->buffer['Long'][$unit][$precision] ;
			$gpx = simplexml_load_file($this->fileAdress) ;
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
			}
			$ret = round($distGPX,$precision) ;
			$this->buffer['Long'][$unit][$precision] = $ret ;
			return $ret ;
		}
		/**
		 * Calculates max and min latitude and longitude of the gps trace, used for zoomlevel method
		 * @param none
		 * @return array
		 */
		private function getAmplitude() {
			$gpx = simplexml_load_file($this->fileAdress) ;
			$i = true ;
			foreach ( $gpx->trk->trkseg->trkpt as $point)  {
				$p['lat'] = (float) $point['lat'] ;
				$p['lon'] = (float) $point['lon'] ;
				$LAT[] = $p['lat'] ;
				$LON[] = $p['lon'] ;
			}
			$amp['maxLat'] = max($LAT) ;
			$amp['maxLon'] = max($LON) ;
			$amp['minLat'] = min($LAT) ;
			$amp['minLon'] = min($LON) ;
			return $amp ;
		}

		/**
		 * Calculates the adequate zoomlevel to display the entire gpstrace
		 * @param none
		 * @return int
		 */
		private function calculZoom() {
			// à faire
			$amp = $this->getAmplitude() ;

			$zoomLevel = 17 ;
			return $zoomLevel ;
		}

		/**
		 * Calculates the ascent
		 * @param string $unit m for meter / TODO other values
		 * @param int precision of the return value
		 * @return string
		 */
		private function getDenivelePos($unit='m',$precision=1) {
			$gpx = simplexml_load_file($this->fileAdress) ;
			$lastPoint = NULL ;
			$sum = 0 ;
			foreach ( $gpx->trk->trkseg->trkpt as $point)  {
				$p['ele'] = (float) $point->ele ;
				if ( $lastPoint != NULL ) {
					$sum += ( $p['ele'] > $lastPoint['ele'] ) ? ($p['ele'] - $lastPoint['ele'] ) : 0 ;
				}
				//$point->lat $point->lng
				$lastPoint = $p ;
			}
			$ret = round($sum,$precision) ;
			return $ret.$unit ;
		}
		/**
		 * Calculates the descent
		 * @param string $unit m for meter / TODO other values
		 * @param int precision of the return value
		 * @return string
		 */
		private function getDeniveleNeg($unit='m',$precision=1) {
			$gpx = simplexml_load_file($this->fileAdress) ;
			$lastPoint = NULL ;
			$sum = 0 ;
			foreach ( $gpx->trk->trkseg->trkpt as $point)  {
				$p['ele'] = (float) $point->ele ;
				if ( $lastPoint != NULL ) {
					$sum += ( $p['ele'] < $lastPoint['ele'] ) ? ($lastPoint['ele'] - $p['ele']) : 0 ;
				}
				//$point->lat $point->lng
				$lastPoint = $p ;
			}
			$ret = round($sum,$precision) ;
			return $ret.$unit ;
		}

		/**
		 * count points of the gps trace
		 * @param none
		 * @return int
		 */
		private function getNbOfPoints() {
			$gpx = simplexml_load_file($this->fileAdress) ;
			$nb = 0 ;
			foreach ( $gpx->trk->trkseg->trkpt as $point)  {
				$nb++ ;
			}
			$nb-- ;
			return $nb ;
		}

		/**
		 * add the shortcode for the object gpxvtt using procede() method
		 * @param string
		 * @return bool always true
		 */
		public function addShortcode() {
			add_shortcode("gpxvtt", array(&$this, 'procede')); // return none
			return true ;
		}
		/**
		 * define the content of the shortcode
		 * @param array $atts is the attributs from the shortcode
		 * @return string
		 */
		function procede($atts) { // $atts from shortcode
			if ( $this->fileAdress == NULL )
				$this->setGpxFile( trim($atts['gpx']) ) ;

			$options = $this->getAdminOptions() ;

			extract( shortcode_atts( array(
				'gpxcolor' => $options['gpxcolorDefault'],
				'gpxwidth' => $options['gpxwidthDefault'],
				'gpxopacity' => $options['gpxopacityDefault'],
				'zoom' => $options['zoomDefault'],
				'width' => $options['widthDefault'],
				'height' => $options['heightDefault'],
				'graphwidth' => $options['graphwidthDefault'],
				'graphheight' => $options['graphheightDefault'],
				'graphcolor' => $options['graphcolorDefault'],
				'graphlinecolor' => $options['graphlinecolorDefault']
				), $atts ) );

			$center = $this->getCenter() ;
			$start = $this->getStart() ;

			$rand = rand(1,1000) ; // allow multiples objects on a single page

			$widthSlider = $graphwidth-20-55 ; // deal with pchatr, jpgraph and d3 configuration $graph->SetMargin(55,20,0,50) ;

			$js = '<script type="text/javascript">
				var lat='.$center['lat'].' ;
				var lon='.$center['lon'].' ;
				var zoom='.$zoom.' ;
				var gpx_'.$rand.' = '.$this->getJsArray().' ;


				var gpxvttMap_'.$rand.' ; //complex object of type OpenLayers.Map

				//function init() {
					gpxvttMap_'.$rand.' = new OpenLayers.Map ("gpxvttMap_'.$rand.'", {
						controls:[
							new OpenLayers.Control.Navigation(),
							new OpenLayers.Control.ZoomPanel(),
							new OpenLayers.Control.LayerSwitcher(),
							new OpenLayers.Control.Attribution()],
						maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
						maxResolution: 156543.0399,
						numZoomLevels: 19,
						units: \'m\',
						projection: new OpenLayers.Projection("EPSG:900913"),
						displayProjection: new OpenLayers.Projection("EPSG:4326")
					} );

					// Define the map layer
					// Here we use a predefined layer that will be kept up to date with URL changes
					layerMapnik = new OpenLayers.Layer.OSM.Mapnik("Mapnik");
					gpxvttMap_'.$rand.'.addLayer(layerMapnik);
					layerCycleMap = new OpenLayers.Layer.OSM.CycleMap("CycleMap");
					gpxvttMap_'.$rand.'.addLayer(layerCycleMap);

					layerMarkers_'.$rand.' = new OpenLayers.Layer.Markers("'.__('Marqueur','gpxvtt').'");
					gpxvttMap_'.$rand.'.addLayer(layerMarkers_'.$rand.');

					// Add the Layer with the GPX Track
					var lgpx = new OpenLayers.Layer.Vector("'.__('Trace GPS','gpxvtt').'", {
						strategies: [new OpenLayers.Strategy.Fixed()],
						protocol: new OpenLayers.Protocol.HTTP({
							url: "'.$this->fileAdress.'",
							format: new OpenLayers.Format.GPX()
						}),
						style: {strokeColor: "'.$gpxcolor.'", strokeWidth: '.$gpxwidth.', strokeOpacity: '.$gpxopacity.'},
						projection: new OpenLayers.Projection("EPSG:4326")
					});
					gpxvttMap_'.$rand.'.addLayer(lgpx);

					var lonLat = new OpenLayers.LonLat(lon,lat).transform(new OpenLayers.Projection("EPSG:4326"), gpxvttMap_'.$rand.'.getProjectionObject());
					gpxvttMap_'.$rand.'.setCenter(lonLat, zoom);

					var size = new OpenLayers.Size(21, 25);
					var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
					var icon_'.$rand.' = new OpenLayers.Icon("http://www.openstreetmap.org/openlayers/img/marker.png",size,offset);
					//var newPos = lonLat ;

					var newPos = new OpenLayers.LonLat('.$start['lon'].','.$start['lat'].').transform(
								new OpenLayers.Projection("EPSG:4326"),
								gpxvttMap_'.$rand.'.getProjectionObject());
					var marker_'.$rand.' = new OpenLayers.Marker(newPos,icon_'.$rand.') ;
					layerMarkers_'.$rand.'.addMarker( marker_'.$rand.' );
				//}
				//init() ;

				jQuery(document).ready(function(){
					jQuery( "#gpxvtt_slider_'.$rand.'" ).slider({
						value:0,
						min: 0,
						max: '.$this->getNbOfPoints().',
						step: 1,
						slide: function( event, ui ) {
							if ( ui.value == 0 ) id = 0 ;
							else {
								// while marker.moveTo() doesn t exists
								// remise en correspondance du slider qui avance au pas de 1px et de la trace qui avance irrégulièrement au rythme des points de la trace
								var long = '.$this->getLong().' ; // longueur trajet
								var w = '.$this->getNbOfPoints().' ; // longueur du slider
								var distGraph ;
								distGraph = long * ui.value / w ;
								var id ;
								for (var i=0 ; gpx_'.$rand.'[i].dist < distGraph ; i++ ) {
									id = i ;
								}
							}

							var newPos = new OpenLayers.LonLat(gpx_'.$rand.'[id].lon,gpx_'.$rand.'[id].lat).transform(new OpenLayers.Projection("EPSG:4326"), gpxvttMap_'.$rand.'.getProjectionObject());

							layerMarkers_'.$rand.'.removeMarker(marker_'.$rand.') ;
							marker_'.$rand.' = new OpenLayers.Marker(newPos,icon_'.$rand.'.clone() ) ;
							layerMarkers_'.$rand.'.addMarker( marker_'.$rand.' );

							d3.selectAll("circle.highlightPoint_'.$rand.'")
            							.attr("display", function(d,i) { return i == id ? "block" : "none"});

							// TODO : si marker sort, recentrer la carte
							//jQuery( "#amount" ).val( "Val" + ui.value ); // for debugging
						}
					});
				});
				</script>' ;

			if ( $options['graphtypeAdmin'] == "d3" )
				$jsD3Chart = '<script type="text/javascript">
function printChart(gpx) {
	var margin = {top: 20, right: 20, bottom: 30, left: 55},
		width = '.$graphwidth.' - margin.left - margin.right,
		height = '.$graphheight.' - margin.top - margin.bottom;

	var x = d3.scale.linear()
		.range([0, width]);

	var y = d3.scale.linear()
		.range([height, 0]);

	var xAxis = d3.svg.axis()
		.scale(x)
		.orient("bottom");

	var yAxis = d3.svg.axis()
		.scale(y)
		.orient("left");

	var area = d3.svg.area()
		.x(function(d) { return x(d.dist); })
		.y0(height)
		.y1(function(d) { return y(d.ele); });

	var line = d3.svg.line()
		.x(function(d) { return x(d.dist); })
		.y(function(d) { return y(d.ele); });

	var chart = d3.select("#chart_'.$rand.'").append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")") ;

	var data = new Array() ;

	for(var i=0;i<gpx.length;i++) {
		data[i] = { dist:gpx[i].dist, ele:gpx[i].ele  } ;
	}

		x.domain(d3.extent(data, function(d) { return d.dist; }));
		// affiche elevation min-20 et max+20
		y.domain([d3.min(data, function(d) { return d.ele; })-20 , d3.max(data, function(d) { return d.ele; })+20 ]);

		chart.append("path")
			.datum(data)
			.attr("class", "area")
			.attr("d", area)
			.attr("fill", "'.$graphcolor.'") ;

		var axeX = chart.append("g")
			.attr("class", "x axis")
			.attr("transform", "translate(0," + height + ")")
			.call(xAxis) ;
		var textAxeX = axeX.append("text")
			.attr("y", -15)
			.attr("x", width-10)
			.attr("dy", ".71em")
			.style("text-anchor", "end")
			.text("Distance en km");

		chart.append("g")
			.attr("class", "y axis")
			.call(yAxis)
			.append("text")
				.attr("transform", "rotate(-90)")
				.attr("y", 6)
				.attr("dy", ".71em")
				.style("text-anchor", "end")
				.text("Elevation en mètres");

		var line = chart.append("path")
			.datum(data)
			.attr("class", "lineSup")
			.attr("stroke", "'.$graphlinecolor.'")
			.attr("d",line);

		chart.selectAll("circle.highlightPoint_'.$rand.'")
				.data(data)
			.enter().append("circle")
				.attr("cx", function(d) { return x(d.dist); })
				.attr("cy", function(d) { return y(d.ele); })
				.attr("class", "highlightPoint_'.$rand.'")
				.attr("r",4)
				.attr("innerRadius",0)
				.style("fill", "pink")
				.style("stroke","blue")
				.style("stroke-width",2)
				.attr("display","none") ;
		// show the firts
		d3.select("circle.highlightPoint_'.$rand.'")
			.attr("display","block") ;
} // end of printChart

printChart(gpx_'.$rand.') ;

</script>' ;

			$content = NULL ;
			$content .= "<h2>".__('Description du trajet','gpxvtt')."</h2>" ;
			$content .= "<div id=\"gpxvtt\">" ;
			$content .= "<p>".__('Longueur totale du trajet','gpxvtt')." : <strong>".$this->getLong()." km</strong><br/>" ;
			$content .= "".__('Denivelé négatif','gpxvtt')." : <strong>".$this->getDeniveleNeg()."</strong></br>" ;
			// getDenivelePos(
			$content .= "".__('Denivelé positif','gpxvtt')." : <strong>".$this->getDenivelePos()."</strong></p>" ;
			$content .= "<div style=\"width:".$width.";height:".$height."\" id=\"gpxvttMap_$rand\"></div>" ;
			/*
			$content .= '<p>
				<label for="amount">Numéro du point affiché : </label>
				<input type="text" id="amount" style="border: 0; color: #f6931f; font-weight: bold;" />
				</p>' ;
			*/
			$content .= "<div id=\"gpxvtt_sub\"><div id=\"gpxvtt_slider_".$rand."\" style=\"width:".$widthSlider."px;margin-left:55px;\"></div>" ;

			switch ( $options['graphtypeAdmin'] ) {
				case "jpgraph" :
					$content .= "<br/>".$this->getJpGraph($graphwidth,$graphheight)."</div>" ;
				break ;
				case "pchart" :
					$content .= "<br/>".$this->getPChart($graphwidth,$graphheight)."</div>" ;
				break ;
				case "d3" : // use d3
					$content .= "<br/><div id='chart_".$rand."'></div></div>" ;
				break ;
				default : // do nothing
					$opensource = __("L'openSource c'est bien :)") ;
				;
			}
			$content .= "<br/>".$this->downloadBox()."\n</div>" ;

			$content .= $js ;
			if ( $options['graphtypeAdmin'] == "d3" )
				$content .= $jsD3Chart ;


			$this->fileAdress = null ;
			$this->buffer = null ;


			return $content ;
		}
		/**
		 * Print an image that call the php library JpGraph
		 * @param int,int dimensions of the output graph as a img
		 * @return string to show the chart, html format
		 */
		function getJpGraph($graphWidth,$graphHeight) {
			$graph = "<img src=\"".GPXVTT_PLUGIN_URL."include/jpgraph.php?file={$this->fileAdress}&graphWidth={$graphWidth}&graphHeight={$graphHeight}&graphTitle=".__('Dénivelé','gpxvtt')."&xaxisTitle=".__('Distance en km','gpxvtt')."&yaxisTitle=".__('Elévation en m','gpxvtt')."\" />" ;
			return $graph ;
		}
		/**
		 * Print an image that call the php library pChart
		 * @param int,int dimensions of the output graph as a img
		 * @return string to show the chart, html format
		 */
		function getPChart($graphWidth,$graphHeight) {
			$graph = "<img src=\"".GPXVTT_PLUGIN_URL."include/pchart.php?file={$this->fileAdress}&graphWidth={$graphWidth}&graphHeight={$graphHeight}&graphTitle=".__('Dénivelé','gpxvtt')."&xaxisTitle=".__('Distance en km','gpxvtt')."&yaxisTitle=".__('Elévation en m','gpxvtt')."\" />" ;
			return $graph ;
		}

		/**
		 * Write an array in javascript to use it with d3.js - dist + elevation + lon + lat
		 * @param none
		 * @return string as formatted js element array of points
		 */
		// écrit le chargement de la feuille de points en tableau javascrit
		// objet ??
		function getJsArray() {
			//$out = "var gpx = new array() ;" ;
			$buffer = NULL ;
			$gpx = simplexml_load_file($this->fileAdress) ;
			$lastPoint = NULL ;
			$distGPX = 0 ;
			$i = 0 ;
			foreach ( $gpx->trk->trkseg->trkpt as $point)  {
				$p['lat'] = (float) $point['lat'] ;
				$p['lon'] = (float) $point['lon'] ;
				$p['ele'] = (float) $point->ele ;
				if ( $lastPoint != NULL ) {
					$distGPX += get_distance_km($p,$lastPoint) ;
				}
				//$point->lat $point->lng
				$lastPoint = $p ;
				$buffer[] = "{lon:".$p['lon'].",lat:".$p['lat'].",ele:".$p['ele'].",dist:".round($distGPX,3)."}" ;
				$i++ ;
			}
			$out = "[".implode(",",$buffer)."]" ;
			return $out ;
		}
		/**
		 * Just the div to download the gpx file
		 * @param none
		 * @return string html format
		 */
		function downloadBox() {
			$out = "<div id=\"gpxvtt_downloadBox\"><a href=\"".$this->fileAdress."\">".__('Télécharger la trace GPS de ce parcours','gpxvtt')."</a></div>" ;
			return $out ;
		}
		/**
		 * add_action for the admin menu, fonction from wordpress
		 * @param
		 * @return bool
		 */
		function addAdminMenu() {
			return add_action('admin_menu', array(&$this,'gpxvttMenu')); // always true
		}
		/**
		 * Configuration of the admin page position and rights, fonction from wordpress
		 * @param
		 * @return string The resulting page's hook_suffix (See What add_submenu_page() returns)
		 */
		function gpxvttMenu() {
			return add_options_page(__('Configuration du plugin GpxVTT'),__('GpxVTT'),'administrator','gpxvtt', array(&$this,'adminPage') ) ;
		}
		/**
		 * content of the admin page, html content
		 * @param
		 * @return bool
		 */
		function adminPage() {
			echo '<div class="wrap">' ;
			echo '<div id="icon-options-general" class="icon32"><br></div>' ;
			echo '<h2>'.__('Configuration du plugin GpxVTT','gpxvtt').'</h2>' ;

			echo '<h3>'.__('Bienvenue','gpxvtt').'</h3>' ;
			echo '<p>'.__('GpxVTT est un plugin qui sert à insérer des traces GPS au format gpx sur votre blog/site.<br/>A partir de la trace téléchargée et d\'un shortcode, le plugin génère le tracé de la carte sur un fond OpenStreetMap, des statistiques de route (longueur, dénivelé positif), un graphe de dénivelé (le tout avec un positionnement dynamique pour la correspondance trajet/dénivelé) et un lien pour télécharger la trace.','gpxvtt').'</p>' ;

			echo '<h3>'.__('Utilisation','gpxvtt').'</h3>' ;
			echo '<p>'.__('1. Téléverser (uploader) votre trace gps au format gpx sur votre site','gpxvtt').'</p>' ;
			echo '<p>'.__('2. Relever l\'adresse web du fichier','gpxvtt').'</p>' ;
			echo '<p>'.__('3. Insérer le shortcode','gpxvtt').' <strong>[gpxvtt gpx="http://monsite/.../uploads/monfichier.gpx"]</strong></p>' ;

			echo '<p>'.__('Liste des options','gpxvtt').'</p>' ;
			echo '<ul>' ;
			echo '<li><strong>gpxcolor : </strong>'.__('couleur de la trace gps, nom (blue, red, yellow) ou code hexadecimal (#808080)','gpxvtt').'</li>' ;
			echo '<li><strong>gpxwidth : </strong>'.__('largeur de la trace sur la carte (valeur entière en pixel sans les unités)','gpxvtt').'</li>' ;
			echo '<li><strong>gpxopacity : </strong>'.__('opacité de la trace sur le fond de carte (un nombre en virgule flottante entre 0 et 1)','gpxvtt').'</li>' ;
			echo '<li><strong>zoom : </strong>'.__('niveau de zoom','gpxvtt').'</li>' ;
			echo '<li><strong>width : </strong>'.__('largeur de la carte (en px avec les unités ex. 600px)','gpxvtt').'</li>' ;
			echo '<li><strong>height : </strong>'.__('hauteur de la carte (en px avec les unités ex. 250px)','gpxvtt').'</li>' ;
			echo '<li><strong>graphwidth : </strong>'.__('largeur du graphe (en px sans les unités ex. 600)','gpxvtt').'</li>' ;
			echo '<li><strong>graphheight : </strong>'.__('hauteur du graphe (en px sans les unités ex. 250)','gpxvtt').'</li>' ;
			echo '<li><strong>graphcolor : </strong>'.__('couleur de l\'aire du graphe, nom (blue, red, yellow) ou code hexadecimal (#808080) - valable pour d3 uniquement','gpxvtt').'</li>' ;
			echo '<li><strong>graphlinecolor : </strong>'.__('couleur de la ligne supérieure du graphe, nom (blue, red, yellow) ou code hexadecimal (#808080) - valable pour d3 uniquement','gpxvtt').'</li>' ;
			echo '</ul>' ;


			echo '<h3>'.__('Options de configuration','gpxvtt').'</h3>' ;

			$options = $this->getAdminOptions() ;

			//
			// SAVE THE FORM IF POSTED
			//
			if (isset($_POST['update_gpxvtt'])) {
				if (isset($_POST['graphtypeAdmin'])) {
					$options['graphtypeAdmin'] = $_POST['graphtypeAdmin'];
				}
				if (isset($_POST['widthDefault'])) {
					$options['widthDefault'] = $_POST['widthDefault'];
				}
				if (isset($_POST['heightDefault'])) {
					$options['heightDefault'] = $_POST['heightDefault'];
				}
				if (isset($_POST['gpxcolorDefault'])) {
					$options['gpxcolorDefault'] = $_POST['gpxcolorDefault'];
				}
				if (isset($_POST['gpxwidthDefault'])) {
					$options['gpxwidthDefault'] = $_POST['gpxwidthDefault'];
				}
				if (isset($_POST['zoomDefault'])) {
					$options['zoomDefault'] = $_POST['zoomDefault'];
				}
				if (isset($_POST['gpxopacityDefault'])) {
					$options['gpxopacityDefault'] = $_POST['gpxopacityDefault'];
				}
				if (isset($_POST['graphwidthDefault'])) {
					$options['graphwidthDefault'] = $_POST['graphwidthDefault'];
				}
				if (isset($_POST['graphheightDefault'])) {
					$options['graphheightDefault'] = $_POST['graphheightDefault'];
				}
				if (isset($_POST['graphcolorDefault'])) {
					$options['graphcolorDefault'] = $_POST['graphcolorDefault'];
				}
				if (isset($_POST['graphlinecolorDefault'])) {
					$options['graphlinecolorDefault'] = $_POST['graphlinecolorDefault'];
				}
				update_option($this->adminOptionsName, $options);
				print '<div class="updated"><p><strong>';
				_e("Paramètres mis à jour", "gpxvtt");
				print '</strong></p></div>';
			}

			//
			//  FORM
			//
			echo '<form method="post" action="'.$_SERVER["REQUEST_URI"].'"> ' ;

			echo '<legend>'.__('Les valeurs indiquées sont les valeurs par défaut. Elles restent modifiables par les arguments passés dans le shortcode.').'</legend>' ;

			echo '<h4>'.__('Configuration générale').'</h4>' ;

			echo '<label for="graphtypeAdmin">'.__('Type de graphe utilisé','gpxvtt').'</label>' ;

			$selected['d3'] = ( $options['graphtypeAdmin']=='d3' ) ? "selected" : NULL ;
			$selected['jpgraph'] = ( $options['graphtypeAdmin']=='jpgraph' ) ? "selected" : NULL ;
			$selected['pchart'] = ( $options['graphtypeAdmin']=='pchart' ) ? "selected" : NULL ;

			echo '<select id="graphtypeAdmin" name="graphtypeAdmin" type="text" value="'.$options['graphtypeAdmin'].'" >'.
				"<option value='d3' ".$selected['d3'].">Utiliser d3 (librairie d3.js), affichage en svg généré en javascript</option>
				<option value='pchart' ".$selected['pchart'].">Utiliser pChart (librairie php pChart), requiert les bibliothèques GD et Freetype (courantes)</option>
				<option value='jpgraph' ".$selected['jpgraph'].">Utiliser jpgraph, les graphes seront générés en php</option>
				</select><br/>\n" ;

			echo '<h4>'.__('Paramétrage de la carte').'</h4>' ;

			echo '<label for="widthDefault">'.__('Largeur par défaut de la carte (ex. 500px)','gpxvtt').'</label>' ;
			echo '<input id="widthDefault" name="widthDefault" type="text" value="'.$options['widthDefault'].'" />'."<br/>\n" ;

			echo '<label for="heightDefault">'.__('Hauteur par défaut de la carte (ex. 500px)','gpxvtt').'</label>' ;
			echo '<input id="heightDefault" name="heightDefault" type="text" value="'.$options['heightDefault'].'" />'."<br/>\n" ;

			echo '<label for="zoomDefault">'.__('Niveau de zoom par défaut (1 à 19)','gpxvtt').'</label>' ;
			echo '<input id="zoomDefault" name="zoomDefault" type="text" value="'.$options['zoomDefault'].'" />'."<br/>\n" ;

			echo '<h4>'.__('Paramétrage de la trace').'</h4>' ;

			echo '<label for="gpxcolorDefault">'.__('Couleur par défaut de la trace (nom comme blue ou code hexadecimal comme #808080)','gpxvtt').'</label>' ;
			echo '<input id="gpxcolorDefault" name="gpxcolorDefault" type="text" value="'.$options['gpxcolorDefault'].'" />'."<br/>\n" ;

			echo '<label for="gpxwidthDefault">'.__('Largeur de la trace (en px)','gpxvtt').'</label>' ;
			echo '<input id="gpxwidthDefault" name="gpxwidthDefault" type="text" value="'.$options['gpxwidthDefault'].'" />'."<br/>\n" ;

			echo '<label for="gpxopacityDefault">'.__('Transparence de la trace (0 à 1)','gpxvtt').'</label>' ;
			echo '<input id="gpxopacityDefault" name="gpxopacityDefault" type="text" value="'.$options['gpxopacityDefault'].'" />'."<br/>\n" ;

			echo '<h4>'.__('Paramétrage des propriétés du graphe').'</h4>' ;

			echo '<label for="graphwidthDefault">'.__('Largeur par défaut du graphe de dénivelé (en px, ex. 500)','gpxvtt').'</label>' ;
			echo '<input id="graphwidthDefault" name="graphwidthDefault" type="text" value="'.$options['graphwidthDefault'].'" />'."<br/>\n" ;

			echo '<label for="graphheightDefault">'.__('Hauteur par défaut du graphe de dénivelé (en px, ex. 250)','gpxvtt').'</label>' ;
			echo '<input id="graphheightDefault" name="graphheightDefault" type="text" value="'.$options['graphheightDefault'].'" />'."<br/>\n" ;

			echo '<label for="graphcolorDefault">'.__('Hauteur par défaut du graphe de dénivelé (en px, ex. 250)','gpxvtt').'</label>' ;
			echo '<input id="graphcolorDefault" name="graphcolorDefault" type="text" value="'.$options['graphcolorDefault'].'" />'."<br/>\n" ;

			echo '<label for="graphlinecolorDefault">'.__('Hauteur par défaut du graphe de dénivelé (en px, ex. 250)','gpxvtt').'</label>' ;
			echo '<input id="graphlinecolorDefault" name="graphlinecolorDefault" type="text" value="'.$options['graphlinecolorDefault'].'" />'."<br/>\n" ;

			echo '<div class="submit">
				<input type="submit" name="update_gpxvtt" value="'.__('Mettre à jour', 'gpxvtt').'" />
				</div>' ;
			echo '</form>' ;
			//
			//  END FORM
			//

			//
			// LICENCE
			//
			echo '<h3>'.__('Licence','gpxvtt').'</h3>' ;

			echo '<p>'.__('GpxVTT est distribué sous licence .','gpxvtt').'<a href="http://www.gnu.org/licenses/gpl-3.0.html">GNU GPL V3</a>'.'</p>' ;

			echo '<p>'.__('Ce plugin n\'aurait pas pu voir le jour sans les travaux suivants dont les auteurs ont eu l\'excellentissime et génialissime idée de travailler en opensource :','gpxvtt').'</p>' ;

			echo '<ul>' ;
			echo '<li>'.__('La bibliothèque Jquery déjà intégrée à Wordpress et ses composantes').' : <a href="http://jquery.com">Jquery</a>'.'</li>' ;

			echo '<li>'.__('La bibliothèque Openlayers permettant de manipuler des données cartographiques').' : <a href="http://openlayers.org">Openlayers</a>'.' <a href="http://fr.wikipedia.org/wiki/Licence_BSD">BSD licence</a>'.'</li>' ;

			echo '<li>'.__('Les données cartographiques libres venant du projet mondial de cartographie OpenStreetMap').' : <a href="http://openstreetmap.org">OpenStreetMap</a>'.' <a href="http://www.openstreetmap.org/copyright">'.__('Lire els licences d\'utilisation d\'OSM','gpxvtt').'</a>'.'</li>' ;

			echo '<li>'.__('En option, la bibliothèque pChart pour produire des graphiques','gpxvtt').' : <a href="http://www.pchart.net">pChart</a>'.' <a href="http://www.gnu.org/licenses/gpl-3.0.html">GNU GPL V3</a>'.'</li>' ;

			echo '<li>'.__('En option, la bibliothèque d3.js pour générer des graphiques dynamiques en javascript au format SVG','gpxvtt').' : <a href="http://d3js.org">d3.js</a>'.' <a href="http://opensource.org/licenses/BSD-3-Clause">BSD Licence</a>'.'</li>' ;

			echo '<li>'.__('En option, la bibliothèque jpGraph pour générer des graphiques en php','gpxvtt').' : <a href="http://jpgraph.net/">jpGraph</a>'.' <a href="http://opensource.org/licenses/qtpl.php">Licence QPL</a></li>' ;

			echo '<li>'.__('Bien entendu : thanks to Wordpress Team','gpxvtt').'</li>' ;

			echo '</ul>' ;
			echo '</div>' ;

			return true ;
		}
	}
}
?>