;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal core.

; drush make API version
api = 2

; Drupal core
core = 8.x

;****************************************
; Warnings
;****************************************

; Drupal 8 support
; https://drupal.org/node/1892496

; Aegir / Provision does not yet support Drupal 8 platforms.
; https://drupal.org/node/1194602

;****************************************
; Core
;****************************************

projects[drupal][type] = core
;projects[drupal][download][type] = git
;projects[drupal][download][url] = git://git.drupal.org/project/drupal.git
;projects[drupal][download][branch] = 8.0.x

; Use the regular core download.
projects[drupal][version] = 8.6.16
projects[drupal][download][type] = get
projects[drupal][download][url] = https://ftp.drupal.org/files/projects/drupal-8.6.16.tar.gz
;****************************************
; End
;****************************************
