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

;projects[] = devel
libraries[kplatforms_firephpcore][directory_name] = FirePHPCore
libraries[kplatforms_firephpcore][download][type] = svn
libraries[kplatforms_firephpcore][download][url] = http://firephp.googlecode.com/svn/branches/Library-FirePHPCore-0.3

;projects[] = simplehtmldom
libraries[kplatforms_simplehtmldom][directory_name] = simplehtmldom
libraries[kplatforms_simplehtmldom][download][type] = get
libraries[kplatforms_simplehtmldom][download][url] = http://downloads.sourceforge.net/project/simplehtmldom/simplehtmldom/1.5/simplehtmldom_1_5.zip

;****************************************
; End
;****************************************
