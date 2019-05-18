/*

DEFAULT OPTIONS
See http://www.jacklmoore.com/zoom/ for more info on settings/options

url: false
callback: false
target: false
duration: 120
on: 'mouseover' // other options: grab, click, toggle
touch: true // enables a touch fallback
onZoomIn: false
onZoomOut: false
magnify: .75

*/

var options = {
  url: drupalSettings.colorbox_zoom.options.url ? drupalSettings.colorbox_zoom.options.url : false,
  callback: drupalSettings.colorbox_zoom.options.callback ? drupalSettings.colorbox_zoom.options.callback : false,
  target: drupalSettings.colorbox_zoom.options.target ? drupalSettings.colorbox_zoom.options.target : false,
  duration: drupalSettings.colorbox_zoom.options.duration,
  on: drupalSettings.colorbox_zoom.options.on_action, // other options: grab, click, toggle
  touch: drupalSettings.colorbox_zoom.options.touch ? true : false, // enables a touch fallback
  onZoomIn: drupalSettings.colorbox_zoom.options.onZoomIn ? drupalSettings.colorbox_zoom.options.onZoomIn : false,
  onZoomOut: drupalSettings.colorbox_zoom.options.onZoomOut ? drupalSettings.colorbox_zoom.options.onZoomOut : false,
  magnify: drupalSettings.colorbox_zoom.options.magnify,
};