;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal development modules.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Modules
;****************************************

projects[] = coder
projects[] = devel
projects[] = devel_themer
projects[] = drupalforfirebug
projects[] = performance
projects[] = search_krumo
projects[] = simplehtmldom

;****************************************
;* Libraries and Patches
;****************************************

includes[] = dev/libraries.make


;****************************************
; End
;****************************************
