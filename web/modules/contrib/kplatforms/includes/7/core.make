;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal core.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Core
;****************************************

; Checking for new updates at:
; https://github.com/pressflow/7/tags
; https://drupal.org/project/drupal

; Pressflow
projects[drupal][type] = core
projects[drupal][download][type] = git
projects[drupal][download][url] = git://github.com/pressflow/7.git
projects[drupal][download][tag] = pressflow-7.67
;projects[drupal][patch][2857054] = https://www.drupal.org/files/issues/pressflow-merge-7.54.patch
;projects[drupal][download][revision] = 64074f823121dcf1ca29c62a062235342e3769f3

; To use the standard core from drupal.org instead,
; uncomment the line below and comment out the lines above.
;projects[drupal][version] = 7.65

;****************************************
; Patches
;****************************************

; Improvements to the core file robots.txt in Drupal
; https://drupal.org/node/1317338#comment-5146596
; If this patch does not work anymore on a new version of Drupal, we should remake it.
projects[drupal][patch][1317338] = https://www.drupal.org/files/issues/robots.txt.drupal-7.50.patch

; Enforce not using core module statistics
; https://drupal.org/node/2007316 , https://www.drupal.org/node/2601714
; If this patch does not work anymore on a new version of Drupal, we should remake it.
projects[drupal][patch][2007316] = https://www.drupal.org/files/issues/kplatforms-statistics_module_patch_fails-2601714-5.patch

; Empty $account->roles causes a sql error in user_access
; https://drupal.org/node/777116#comment-4283336
projects[drupal][patch][777116] = http://drupal.org/files/issues/777116-no-roles-error.patch

; ACL support for D7
; https://drupal.org/node/1798242
; ./sites/default/files directory permission check is incorrect during install AND status report
; https://drupal.org/node/944582#comment-5872786
projects[drupal][patch][944582] = http://drupal.org/files/d7-944582-59-do-not-test.patch

; node_access integrity constraint violation on module_invoke_all
; https://drupal.org/node/1865072#comment-6841614
;projects[drupal][patch][1865072] = http://drupal.org/files/1865072-node_insert_save_d7.patch

; https://www.drupal.org/node/2289229
; http://cgit.drupalcode.org/ais/tree/README.txt#n71
projects[drupal][patch][ais] = "http://cgit.drupalcode.org/ais/plain/ais.htaccess.patch?id=7.x-1.6"

; https://www.drupal.org/node/1366716#comment-6386986
; ref.: https://www.drupal.org/node/2327511
projects[drupal][patch][1366716] = https://www.drupal.org/files/1366716_1.patch

; Block Ahrefs for disrespect & attacks
; https://www.drupal.org/node/2399539
projects[drupal][patch][2399539] = "https://www.drupal.org/files/issues/kplatforms-block_ahref_crawler-2399539-7.patch"

;****************************************
; End
;****************************************
