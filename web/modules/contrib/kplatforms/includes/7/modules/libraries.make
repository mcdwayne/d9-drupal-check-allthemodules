;****************************************
; General
;****************************************

; Description
; A drush makefile for libraries required by Drupal modules.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Modules
;****************************************

;projects[colorbox][version] = 2.5
; Colorbox library download URL
; https://drupal.org/node/1901374
; https://drupal.org/node/1991874
; TODO: Add a 'tag' or 'commit' here
libraries[kplatforms_colorbox][directory_name] = colorbox
libraries[kplatforms_colorbox][download][type] = git
libraries[kplatforms_colorbox][download][url] = https://github.com/jackmoore/colorbox.git

;projects[css3pie][version] = 2.1
libraries[kplatforms_pie][directory_name] = PIE
libraries[kplatforms_pie][download][type] = get
libraries[kplatforms_pie][download][url] = https://github.com/lojjic/PIE/archive/1.0.0.zip

;projects[elfinder][version] = 1.x-dev
libraries[kplatforms_elfinder][directory_name] = elfinder
libraries[kplatforms_elfinder][download][type] = get
libraries[kplatforms_elfinder][download][url] = https://github.com/Studio-42/elFinder/archive/1.2.zip
; Removing files to avoid security hole
; https://drupal.org/node/2185757
; If this patch does not work anymore on a new version of the library, we should rebuild it.
libraries[kplatforms_elfinder][patch][2185757] = https://www.drupal.org/files/issues/elfinder-rm-files-5.patch

;projects[geshifilter][version] = 1.2
; Remove libraries module from make file
; https://drupal.org/node/1948852
projects[geshifilter][patch][1948852] = http://drupal.org/files/1948852_make_example_3.patch
libraries[kplatforms_geshi][directory_name] = geshi
libraries[kplatforms_geshi][download][type] = get
libraries[kplatforms_geshi][download][url] = https://github.com/GeSHi/geshi-1.0/archive/RELEASE_1_0_8_11.tar.gz
libraries[kplatforms_geshi][download][subtree]= geshi-1.0-RELEASE_1_0_8_11/src
; GeSHi Puppet language definition
libraries[kplatforms_geshi_puppet][destination] = libraries/geshi
libraries[kplatforms_geshi_puppet][directory_name] = geshi
libraries[kplatforms_geshi_puppet][download][type] = get
libraries[kplatforms_geshi_puppet][download][url] = https://raw.github.com/jasonhancock/geshi-language-files/7fd7a709d857f74b78d42990a2381a45eeb93429/puppet.php
libraries[kplatforms_geshi_puppet][overwrite] = TRUE

;projects[leaflet][version] = 1.1
libraries[kplatforms_leaflet][directory_name] = leaflet
libraries[kplatforms_leaflet][download][type] = get
libraries[kplatforms_leaflet][download][url] = http://cdn.leafletjs.com/leaflet/v1.2.0/leaflet.zip

;projects[leaflet_markercluster]
libraries[kplatforms_leaflet_markercluster][directory_name] = leaflet_markercluster
libraries[kplatforms_leaflet_markercluster][download][type] = get
libraries[kplatforms_leaflet_markercluster][download][url] = https://github.com/Leaflet/Leaflet.markercluster/archive/v1.2.0.zip

;projects[views_slideshow][version] = 3.1
; TODO: Add a 'tag' or 'commit' here
libraries[kplatforms_jquery_cycle][directory_name] = jquery.cycle
libraries[kplatforms_jquery_cycle][download][type] = get
libraries[kplatforms_jquery_cycle][download][url] = https://raw.github.com/malsup/cycle/master/jquery.cycle.all.js
libraries[kplatforms_jquery_cycle][overwrite] = TRUE
; TODO: Add a 'tag' or 'commit' here
libraries[kplatforms_json2][directory_name] = json2
libraries[kplatforms_json2][download][type] = get
libraries[kplatforms_json2][download][url] = https://raw.github.com/douglascrockford/JSON-js/master/json2.js
libraries[kplatforms_json2][overwrite] = TRUE

;projects[wysiwyg][version] = 2.2
; TODO: specify a version here
libraries[kplatforms_ckeditor][directory_name] = ckeditor
libraries[kplatforms_ckeditor][download][type] = get
libraries[kplatforms_ckeditor][download][url] = https://github.com/ckeditor/ckeditor-releases/archive/full/4.6.2.tar.gz

libraries[kplatforms_tinymce][directory_name] = tinymce
libraries[kplatforms_tinymce][download][type] = get
libraries[kplatforms_tinymce][download][url] = https://github.com/downloads/tinymce/tinymce/tinymce_3.4.9_jquery.zip

;libraries[kplatforms_tinymce_fr][directory_name] = tinymce
;libraries[kplatforms_tinymce_fr][download][type] = get
;libraries[kplatforms_tinymce_fr][download][url] = http://www.tinymce.com/i18n3x/index.php%3Fctrl%3Dexport%26act%3Dzip%26la%5B%5D%3Dfr%26pr_id%3D7%26la_export%3Djs
;libraries[kplatforms_tinymce_fr][overwrite] = TRUE
; ^
; "[overwrite] = TRUE" for zip file with Drush 5 overwrites instead of merging as in Drush make 2.3
; https://drupal.org/node/2131945

;****************************************
; End
;****************************************
