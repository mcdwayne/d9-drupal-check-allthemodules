;****************************************
; General
;****************************************

; Description
; A drush makefile for libraries required by Drupal modules.

; drush make API version
api = 2

; Drupal core
core = 8.x

;****************************************
; Modules
;****************************************

libraries[kplatforms_colorbox][directory_name] = colorbox
libraries[kplatforms_colorbox][download][type] = get
libraries[kplatforms_colorbox][download][url] = https://github.com/jackmoore/colorbox/archive/1.6.4.zip

libraries[kplatforms_flexslider][directory_name] = flexslider
libraries[kplatforms_flexslider][download][type] = get
libraries[kplatforms_flexslider][download][url] = https://github.com/woocommerce/FlexSlider/archive/2.6.4.tar.gz

;projects[leaflet][version] = 1.1
libraries[kplatforms_leaflet][directory_name] = leaflet
libraries[kplatforms_leaflet][download][type] = get
libraries[kplatforms_leaflet][download][url] = http://cdn.leafletjs.com/leaflet/v1.2.0/leaflet.zip

;projects[leaflet_markercluster]
libraries[kplatforms_leaflet_markercluster][directory_name] = leaflet_markercluster
libraries[kplatforms_leaflet_markercluster][download][type] = get
libraries[kplatforms_leaflet_markercluster][download][url] = https://github.com/Leaflet/Leaflet.markercluster/archive/v1.2.0.zip

;****************************************
; End
;****************************************
