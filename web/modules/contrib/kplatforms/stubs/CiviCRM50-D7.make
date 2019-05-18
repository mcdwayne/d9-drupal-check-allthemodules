;****************************************
; General
;****************************************

; Description
; A drush stub makefile for Drupal 7.x.

; drush make API version
api = 2

; Drupal core
core = 7.x

; Defaults
defaults[projects][subdir] = "contrib"

;****************************************
; Includes
;****************************************

; Core
includes[] = ../includes/7/core.make

; CiviCRM module overrides - in later version of drush, overidding existing modules later
; doesn't work
includes[] = ../includes/7/civicrm/civicrm-modules.make

; Modules
includes[] = ../includes/7/apachesolr.make
;includes[] = ../includes/7/dev.make
;includes[] = ../includes/7/geo.make
includes[] = ../includes/7/modules.make
includes[] = ../includes/7/payment.make

; Themes
includes[] = ../includes/7/themes.make

; CiviCRM
includes[] = ../includes/7/civicrm/civicrm-50.make

;****************************************
; End
;****************************************
