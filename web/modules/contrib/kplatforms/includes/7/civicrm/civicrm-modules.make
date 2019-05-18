;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal modules for CiviCRM.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Modules
;****************************************

; Override the version of webform from 7/modules/pins.make @see https://www.drupal.org/node/2538524
projects[webform][version] = 4
; Override the version of webform_layout from 7/modules/pins.make @see https://www.drupal.org/node/2538524
projects[webform_layout][version] = 2
projects[] = webform_civicrm

; 3.x of the jquery_update module since the civi platforms started using it early.
projects[jquery_update][version] = 3

;****************************************
; End
;****************************************
