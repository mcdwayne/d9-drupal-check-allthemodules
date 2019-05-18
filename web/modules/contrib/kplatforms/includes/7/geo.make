;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal geographical modules.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Modules
;****************************************

projects[] = addressfield
projects[] = geocoder
projects[] = geofield
projects[] = geophp
projects[] = openlayers_plus
projects[] = proj4js

; Pinned openlayers at version 2, since version 3 (a major change) is now default.
projects[openlayers][version] = 2

;****************************************
; End
;****************************************
