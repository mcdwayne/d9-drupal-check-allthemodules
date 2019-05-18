;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal modules.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Modules
;****************************************

; This patch generate an error now that will not allow the building of the kplatform.
; A new version was released on Sept 19 2016: 7.x-1.2
; See https://www.drupal.org/project/entityreference
; https://drupal.org/node/1858402
; People seems to be able to disable a Field module which still has fields, and core seems to call field hooks on disabled modules
; https://drupal.org/node/1459540#comment-6810146
;projects[entityreference][version] = 1.1
;projects[entityreference][patch][1459540] = http://drupal.org/files/entityreference-1459540-47-workaround-fatal-error.patch

; Remove libraries module from make file
; https://drupal.org/node/1948852
;projects[geshifilter][version] = 1.2
projects[geshifilter][patch][1948852] = http://drupal.org/files/1948852_make_example_3.patch

; Patch for l10n_update's failing downloads
; https://www.drupal.org/node/2167585
; (kplatforms issue: https://www.drupal.org/node/2498411)
; 
;projects[l10n_update][patch][2167585] = "https://www.drupal.org/files/issues/l10n_update-unable-to-download-translations-updates-216758-29.patch"

; patch wysiwyg for htauth sites
; https://drupal.org/node/1980850
; https://drupal.org/node/1802394#comment-6556656
; Emmanuel 2017-01-13 commenting below, wysiwyg module is at 2.3: https://www.drupal.org/project/wysiwyg
;projects[wysiwyg][version] = 2.2
;projects[wysiwyg][patch][1802394] = http://drupal.org/files/wysiwyg-1802394-4.patch

;projects[views][patch][1685144] = "http://www.drupal.org/files/views-1685144-localization-bug_1.patch"

; patch boxes for wysiwys ckeditor in civicrm compatibility
projects[boxes][patch][2175471] = "https://www.drupal.org/files/issues/boxes_civicrm_wysiwyg_conflict-2175471-2.patch"
projects[paypal_payment][patch][2870870] = https://www.drupal.org/files/issues/install_file_issue.2870870.2.patch

;****************************************
; End
;****************************************
