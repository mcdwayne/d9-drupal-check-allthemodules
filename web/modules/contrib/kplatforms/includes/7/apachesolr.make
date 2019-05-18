;****************************************
; General
;****************************************

; Description
; A drush makefile for "Apache Solr Search Integration" (apachesolr).

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Core
;****************************************

projects[] = apachesolr
projects[] = apachesolr_attachments

; alternatives
projects[] = search_api
projects[] = search_api_solr


;****************************************
; End
;****************************************
