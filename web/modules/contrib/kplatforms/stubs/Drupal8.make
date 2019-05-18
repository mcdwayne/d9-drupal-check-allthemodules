;****************************************
; General
;****************************************

; Description
; A drush stub makefile for Drupal 8.x.

; drush make API version
api = 2

; Drupal core
core = 8.x

; Defaults
defaults[projects][subdir] = "contrib"

;****************************************
; Includes
;****************************************

; Core
includes[] = ../includes/8/core.make

; Modules
includes[] = ../includes/8/modules.make

; Themes
includes[] = ../includes/8/themes.make

; Profiles
includes[] = ../includes/8/profiles.make

;****************************************
; End
;****************************************
