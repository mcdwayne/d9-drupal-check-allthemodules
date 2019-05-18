/**
 * @file
 * Contains javascript libs for gpx_trackelevator module.
 *
 * When the DOM is ready initialize function is launched to prepare
 * data for rendering. Then plot_track is launched to create the map
 * and then google core_cart is load. Finally, the elevation graph
 * is created by plot_elevation_graph function.
 *
 * Expected drupalSettings.gpx_track_elevation properties:
 * - points: an object with field instance & formatter data. See below for details.
 * - trColor: track color.
 * - epColor: alevation profile color.
 * - trstroke: track stroke.
 * - maptype: map type to use as default.
 * - enableBilink: if bidirectional link is enabled.
 * - trStartPoint: start points marker image.
 * - trEndPoint: end points marker image.
 * - trLastPoint: last points marker image.
 * - parDistance: "Distance" translation.
 * - parElevation: "Elevation" translation.
 * - parAsl: "asl" (above see level) translation.
 * - parLengthUnit: "m" (meter) translation.
 * - wpt_types: object with one method for each recognized waypoint type.
 *    Method name is the type and its value is the associated image url.
 *
 * points object has one method for each node to show. Metod name is id-nid, where nid is the node id
 * points[id-nid] return an array whose elements are:
 *  - gpx_start_point (int: how to show track start point for this formatter)
 *  - gpx_start_point (int: how to show track start point for this formatter)
 *  - array with the following elements:
 *   - array with an element for each <trk> tag:
 *    - <trk> name
 *    - array with an element for each <trkpt>
 *     - latitude
 *     - longitude
 *     - elevation
 *   - array with an element for each waypoint
 *    - waypoint name
 *    - waypoint latitude
 *    - waypoint longitude
 *    - waypoint description
 *    - waypoint elevation
 *    - waypoint type
 */

(function ($, Drupal, drupalSettings) {
  "use strict";

  var points = [];
  var bounds;
  var map = {};
  var elevations = {};
  var markerPoint = {};
  var trackNumber = {};
  var chart = {};
  var trackName = {};
  var StartPoints = [];
  var EndPoints  = [];
  var markers_type;
  var trackNids;
  var track_points_marker = {};
  var DEFAULT_MARKER = "http://www.google.com/mapfiles/marker.png";
  var DEFAULT_START_MARKER = "http://www.google.com/mapfiles/dd-start.png";
  var DEFAULT_END_MARKER = "http://www.google.com/mapfiles/dd-end.png";
  var DEFAULT_MARKER_SIZE = 30;
  var GOOGLE_CHART_LOADED = false;
  var nIndex;
  var selected_marker;
  var data = [];


  /**
   * Function plot_track creates the map and the listeners and plots the track.
   */
  function plot_track(){
    var $theMap; 
    var rawWayPoints = {};
    rawWayPoints[nIndex] = trackNids['id-' + nIndex][2][1];
    var myTrack = [];
    var wayPoints = [];
    var infoBox;

    // Show the map.
    $('.gpxtrele-container .map-canvas').height('20em');
    $theMap  = document.getElementById('gpxtrele-container-' + nIndex).getElementsByClassName('map-canvas');

    map[nIndex] = new google.maps.Map($theMap[0],
    {
      draggable: true,
      keyboardShortcuts: false,
      mapTypeId: google.maps.MapTypeId[drupalSettings.gpx_track_elevation.maptype],
      overviewMapControl: true,
      overviewMapControlOptions: false,
      streetViewControl: false,
      mapTypeControl: true,
      zomcontrol: true,
    });

    // Prepare the mouse move marker.
    markerPoint[nIndex] = new google.maps.Marker({
      position: new google.maps.LatLng(0,0),
      map:map[nIndex],
      visible: true,
      opacity: 0,
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 3,
        strokeColor: drupalSettings.gpx_track_elevation.epColor
      },
    });

    // Prepare waypoints infowindow.
    infoBox = new google.maps.InfoWindow({});

    // Show waypoints.
    for (var i = 0; i < rawWayPoints[nIndex].length; i += 1) {
      var point = new google.maps.LatLng(rawWayPoints[nIndex][i][1], rawWayPoints[nIndex][i][2]);
      bounds.extend(point);
      wayPoints.push(new google.maps.Marker({
        map:map[nIndex],
        position: point,
        visible: true,
        title: rawWayPoints[nIndex][i][0],
        opacity: 1,
        icon: setMarkerImage(rawWayPoints[nIndex][i][5]),
        zIndex: 2

      }));
      google.maps.event.addListener(wayPoints[i], 'click', bindIndexWaypoint(rawWayPoints, nIndex, i));
    }

    map[nIndex].fitBounds(bounds);

    // Show last track point.
    for (i = 0; i < trackNumber[nIndex]; i += 1) {
      if (trackNids['id-' + nIndex][1] > 0) {
        if ((trackNids['id-' + nIndex][1] > 1) || (i == (trackNumber[nIndex] - 1))) {
          EndPoints.push(new google.maps.Marker({
            map:map[nIndex],
            position: points[nIndex][i][points[nIndex][i].length - 1],
            icon: setTrackImage((i == (trackNumber[nIndex] - 1)) ? 'last' : 'end', DEFAULT_END_MARKER),
            title: trackName[nIndex][i],
            zIndex: 1
          }));
        }
      }

      // Show first track point.
      if (trackNids['id-' + nIndex][0] > 0) {
        if ((trackNids['id-' + nIndex][0] > 1) || (i == 0)) {
          StartPoints.push(new google.maps.Marker({
            map:map[nIndex],
            position: points[nIndex][i][0],
            icon: setTrackImage('start', DEFAULT_START_MARKER),
            title: trackName[nIndex][i],
            zIndex: 3
          }));
        }
      }

      // Show tracks.
      myTrack.push(new google.maps.Polyline({
        path: points[nIndex][i],
        strokeColor: drupalSettings.gpx_track_elevation.trColor,
        strokeOpacity: .7,
        strokeWeight: drupalSettings.gpx_track_elevation.trstroke,
        clickable: false,
      }));

      myTrack[i].setMap(map[nIndex]);
    }

    if (drupalSettings.gpx_track_elevation.enableBilink) {
      // We have to catch mousemove of the map to emulate that of the track
      // mousemove of the polyline would be better, but it does not return
      // point info for not editable polylines.
      google.maps.event.addListener(map[nIndex], 'mousemove', marker_on_map.bind(nIndex));

      function marker_on_map (evt){
        var nIndex = this;
        var minDist = 1000000000;
        var dist;
        var thePoint;

        for (var j = 0; j < trackNumber[nIndex]; j += 1) {
          for (var i = 0; i < points[nIndex][j].length; i += 1) {
            dist = google.maps.geometry.spherical.computeDistanceBetween(points[nIndex][j][i], evt.latLng);
            if (dist < minDist) {
              minDist = dist;
              thePoint = [j,i];
            }
          }
        }

        // In order to emulate the "track mousemove" we need to be close
        // to the track. Unfortunately the term "close to" depends on the
        // zoom level, so we have to check the zoom based distance.
        var zoom = 591658 / Math.pow(2, map[nIndex].getZoom() - 1);
        if (zoom / minDist > 1) {
          markerPoint[nIndex].setPosition(points[nIndex][thePoint[0]][thePoint[1]]);
          markerPoint[nIndex].setOpacity(1);
          chart[nIndex][thePoint[0]].setSelection([{row:thePoint[1],column:1}]);
        }
        else {
          for (var k = 0; k < trackNumber[nIndex]; k += 1) {
            chart[nIndex][k].setSelection(null);
          }
          markerPoint[nIndex].setOpacity(0);
        }
      }
    }

    /**
     * Function bindIndexWaypoint shows the waypoints InfoWindow.
     *
     * @param i
     *   index of the waypoint to be shown.
     */
    function bindIndexWaypoint(rawWayPoints, nIndex, i) {
      return (function(){
        var inboxInfo = '';
        if (this.title != '') {
          inboxInfo = '<b>' + this.title + '</b><br>';
        }
        if (rawWayPoints[nIndex][i][4] != '') {
          inboxInfo += drupalSettings.gpx_track_elevation.parElevation + ': ' + round10(rawWayPoints[nIndex][i][4]) + '<br>';
        }
        if (rawWayPoints[nIndex][i][3] != '') {
          inboxInfo += rawWayPoints[nIndex][i][3] + '<br>';
        }
        infoBox.setContent(inboxInfo);
        infoBox.open(map[nIndex],this);
      });
    }
  }

  /**
   * Function to redraw the elevation profile
   */
  function resize () {
    // Google chart are not responsive, so the function is needed to resize.
    $.each(chart, function(i,ival){
      $.each(ival, function(j,jval){
        jval.draw(data[j],{
        height: 200,
        legend: 'none',
        title: Drupal.checkPlain(trackName[i][j]), //TO DO: test if double escaping is occuring
        titleX: drupalSettings.gpx_track_elevation.parDistance,
        titleY: drupalSettings.gpx_track_elevation.parElevation,
        hAxis: {showTextEvery: Math.round(points[nIndex][j].length / 10)},
        colors: [drupalSettings.gpx_track_elevation.epColor],
        tooltip: {isHtml: true, trigger: 'both'},
          
        });
      });
    });
  }
  
  /**
   * Function plot_elevation_graph creates elevation profile(s).
   */
  function plot_elevation_graph() {
    nIndex = this;
    data = [];
    chart[nIndex] = [];
    for (var j = 0; j < trackNumber[nIndex]; j += 1) {
      var divId = 'elevation_chart' + j;
      $('#gpxtrele-container-' + nIndex + ' .elevation_chart').append('<div id="elevation_chart-' + nIndex + '-' + j + '"></div>');
      data.push(new google.visualization.DataTable());
      data[j].addColumn('string', drupalSettings.gpx_track_elevation.parDistance);
      data[j].addColumn('number', drupalSettings.gpx_track_elevation.parElevation);
      data[j].addColumn({'type': 'string', 'role': 'tooltip', 'p': {'html': true}});
      for (var i = 1; i < elevations[nIndex][j].length; i++) {
        data[j].addRow([elevations[nIndex][j][i][0] + '', elevations[nIndex][j][i][1], drupalSettings.gpx_track_elevation.parDistance + ': <b>' + elevations[nIndex][j][i][0] + '</b> ' + drupalSettings.gpx_track_elevation.parLengthUnit + '<br> ' + drupalSettings.gpx_track_elevation.parElevation + ': <b>' + elevations[nIndex][j][i][1] + '</b> ' + drupalSettings.gpx_track_elevation.parLengthUnit + ' ' + drupalSettings.gpx_track_elevation.parAsl]);
      }
      chart[nIndex].push(new google.visualization.AreaChart(document.getElementById('elevation_chart-' + nIndex + '-' + j)));
      document.getElementById('elevation_chart-' + nIndex + '-' + j).parentNode.style.display = 'block';
      document.getElementById('elevation_chart-' + nIndex + '-' + j).style.display = 'block';

      google.visualization.events.addListener(chart[nIndex][j], 'onmouseover', show_marker.bind([parseInt(nIndex), j]));

      google.visualization.events.addListener(chart[nIndex][j], 'onmouseout', hide_marker.bind([parseInt(nIndex), j]));
      chart[nIndex][j].draw(data[j],{
        height: 200,
        legend: 'none',
        title: Drupal.checkPlain(trackName[nIndex][j]), //TO DO: test if double escaping is occuring
        titleX: drupalSettings.gpx_track_elevation.parDistance,
        titleY: drupalSettings.gpx_track_elevation.parElevation,
        hAxis: {showTextEvery: Math.round(points[nIndex][j].length / 10)},
        colors: [drupalSettings.gpx_track_elevation.epColor],
        tooltip: {isHtml: true, trigger: 'both'},
      });
    }

    /**
     * Function show_marker shows a marker on the map.
     *
     * @param pnt
     *   position where marker has to be shown.
     */
    function hide_marker (pnt) {
      markerPoint[this[0]].setOpacity(0);
    }

    /**
     * Function show_marker shows a marker on the map.
     *
     * @param pnt
     *   position where marker has to be shown.
     */
    function show_marker (pnt) {
      markerPoint[this[0]].setPosition(points[this[0]][this[1]][pnt.row]);
      markerPoint[this[0]].setOpacity(1);
    }
  }

  /**
   * Function round10 rounds a number to the nearest 10.
   *
   * @param number
   *   number to be rounded.
   *
   * @return
   *   rounded number.
   */
  function round10(number) {
    return Math.round(Math.round(number / 10) * 10);
  }

  function setMarkerImage (wpt) {
    var selected_marker;
    if ((wpt != '') && (markers_type) && (wpt in markers_type)) {
      selected_marker = {
        url: markers_type[wpt],
        scaledSize: new google.maps.Size(DEFAULT_MARKER_SIZE, DEFAULT_MARKER_SIZE),
        anchor: new google.maps.Point(DEFAULT_MARKER_SIZE / 2, DEFAULT_MARKER_SIZE / 2),
      }
    }
    else if ((markers_type) && ('default' in markers_type)) {
      selected_marker = {
        url: markers_type['default'],
        scaledSize: new google.maps.Size(DEFAULT_MARKER_SIZE, DEFAULT_MARKER_SIZE),
        anchor: new google.maps.Point(DEFAULT_MARKER_SIZE / 2, DEFAULT_MARKER_SIZE / 2),
      }
    }
    else {
      selected_marker = DEFAULT_MARKER;
    }
    return selected_marker;
  }

  function setTrackImage (pointType, defaultImage) {
    defaultImage = defaultImage || DEFAULT_MARKER;
    if (pointType in track_points_marker) {
      selected_marker = track_points_marker[pointType] || defaultImage;
    }
    else {
      selected_marker = defaultImage;
    }
    return selected_marker;
  }
  
  function Initiate(context, settings) {
    
    var index;
    var trackPoints = [];
      $(context).find('div.gpxtrele-container').once('gpxtrele-container').each(function () {
        track_points_marker.start = drupalSettings.gpx_track_elevation.trStartPoint;
        track_points_marker.end = drupalSettings.gpx_track_elevation.trEndPoint;
        track_points_marker.last = drupalSettings.gpx_track_elevation.trLastPoint;
        markers_type = drupalSettings.gpx_track_elevation.wpt_types;
        bounds = new google.maps.LatLngBounds();
        trackNids = drupalSettings.gpx_track_elevation.points;

        nIndex = this.id.substring(19);
        index = 'id-' + nIndex;
          trackPoints = trackNids[index][2][0];
          trackNumber[nIndex] = trackPoints.length;
          nIndex = index.substring(3);
          points[nIndex] = [];
          trackName[nIndex] = [];
          elevations[nIndex] = [];

          for (var j = 0; j < trackNumber[nIndex]; j += 1) {
            trackName[nIndex][j] = trackPoints[j][0];
            var totalDistance = 0;
            points[nIndex][j] = [];
            elevations[nIndex][j] = [];
            var point = new google.maps.LatLng(trackPoints[j][1][0][0], trackPoints[j][1][0][1]);
            elevations[nIndex][j].push(['0', trackPoints[j][1][0][2]]);
            points[nIndex][j].push(point);
            bounds.extend(point);
            for (var i = 1; i < trackPoints[j][1].length; i += 1) {
              point = new google.maps.LatLng(trackPoints[j][1][i][0], trackPoints[j][1][i][1]);
              points[nIndex][j].push(point);
              totalDistance += google.maps.geometry.spherical.computeDistanceBetween(points[nIndex][j][i - 1], points[nIndex][j][i]);
              bounds.extend(point);
              elevations[nIndex][j].push([round10(totalDistance) + '', Math.round(trackPoints[j][1][i][2])]);
            }
          }
          plot_track(nIndex);

          google.load('visualization', '1', {packages: ['corechart'], callback: plot_elevation_graph.bind(nIndex)});

      });
  }


  /**
  * Attaches the table drag behavior to tables.
  *
  * @type {Drupal~behavior}
  * 
  * @prop {Drupal~behaviorAttach} attach
  *   Loading Google Maps API and attaching the javascript to the formatter.
  */
  Drupal.behaviors.gpx_track_elevation = {
    // Google Maps have to be loaded only once
    attach: function (context, settings) {
      if (typeof google === 'object' && typeof google.maps === 'object') {
        Initiate(context, settings);
      }
      else {
        $.getScript(drupalSettings.gpx_track_elevation.google_map_url)
          .done(function () {
            Initiate(context, settings);
          });
      }
      if (window.addEventListener) {
        window.addEventListener('resize', resize);
      }
      else {
        window.attachEvent('onresize', resize);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
