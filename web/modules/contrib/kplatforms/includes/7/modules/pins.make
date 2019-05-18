;****************************************
; General
;****************************************

; Description
; A drush makefile to pin Drupal modules at a specific version.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Modules
;****************************************

; The recommended release is on the 3.x branch, so we pin to the latest
; supported version on the 2.x branch.
; For updates, check: https://drupal.org/project/backup_migrate
projects[backup_migrate][version] = 2

; Use 3.x branch of jquery since we started using it earlier for some reason.
projects[jquery_update][version] = 3

; The recommended release is on the 2.x branch now, so we pin to the latest
; supported version on the 1.xd branch. The migration from 1.x to 2.x is
; non-trivial.
; For updates, check: https://www.drupal.org/project/media
;projects[media][version] = 1

; The recommended release is on the 2.x branch, so we pin to the latest
; supported version on the 1.x branch.
; For updates, check: https://drupal.org/project/media_vimeo
;projects[media_vimeo][version] = 1

; The move to 3.x requires changing jQuery versions, so pin to 2.x
projects[nice_menus][version] = 2

; The recommended release is on the 2.x branch, so we pin to the latest
; supported version on the 1.x branch.
; For updates, check: https://drupal.org/project/picture
projects[picture][version] = 1

; The recommended release is on the 2.x branch, so we pin to the latest
; supported version on the 1.x branch.
; For updates, check: https://drupal.org/project/print
projects[print][version] = 1

; Emmanuel unpin webform : 2019-03-22
; The recommended release is on the 4.x branch, so we pin to the latest
; supported version on the 3.x branch.
; For updates, check: https://drupal.org/project/webform
;projects[webform][version] = 3

; Emmanuel unpin webform_layout : 2019-03-22
; The recommended release is on the 2.x branch, which is only compabtible
; with webform 4.x, so we pin to the latest on 1.x
; For updates, check: https://www.drupal.org/project/webform_layout
;projects[webform_layout][version] = 1

; The recommended release is on the 0.x branch, so we pin to the latest
; supported version on the 0.x branch
; For updates, check: https://www.drupal.org/project/elfinder
projects[elfinder][version] = 0


; The recommended release is on the 1.x branch, so we pin to the latest
; supported version on the 1.x branch
; For updates, check: https://www.drupal.org/project/i18n
projects[i18n][version] = 1

; Google Recaptcha has recommended all users upgrade to latest version
projects[recaptcha][version] = 2

; superfish 2 need an update to the superfish lib
; https://www.drupal.org/node/2622774
; we stick with the 1.x branch for now
projects[superfish] = 1

; lightbox2 was previously in the 1.x version and 2 is a major update.
; will stick to version 1.x for now - Emmanuel 2016-04-26
projects[lightbox2] = 1

;****************************************
; End
;****************************************
