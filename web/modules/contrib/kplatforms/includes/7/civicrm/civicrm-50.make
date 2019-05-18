;****************************************
; General
;****************************************

; Description
; A drush makefile for CiviCRM.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; CiviCRM core
;****************************************

; Checking for new updates at:
; https://civicrm.org/download/list

libraries[civicrm][destination] = modules
libraries[civicrm][directory_name] = civicrm
libraries[civicrm][download][type] = get
libraries[civicrm][download][url] = https://download.civicrm.org/civicrm-5.13.2-drupal.tar.gz

; Include translation. Order is important so it follows civicrm.
libraries[z_civicrm_l10n_core][destination] = modules/civicrm
libraries[z_civicrm_l10n_core][directory_name] = l10n
libraries[z_civicrm_l10n_core][download][type] = file
libraries[z_civicrm_l10n_core][download][url] = https://download.civicrm.org/civicrm-l10n-core/archives/civicrm-l10n-daily.tar.gz
libraries[z_civicrm_l10n_core][overwrite] = TRUE

;****************************************
; End
;****************************************
