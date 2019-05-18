;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal modules.

; drush make API version
api = 2

; Drupal core
core = 8.x

;****************************************
; Modules
;****************************************

projects[packery][version] = 1.x
projects[packery][patch][2618452] = "https://www.drupal.org/files/issues/2018-03-13/packery-allow_numeric_settings-2618452-10.patch"
