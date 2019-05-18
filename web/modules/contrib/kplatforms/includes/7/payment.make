;****************************************
; General
;****************************************

; Description
; A drush makefile for Payment modules.

; drush make API version
api = 2

; Drupal core
core = 7.x

;****************************************
; Core
;****************************************

projects[] = payment

;****************************************
; Modules
;****************************************

projects[] = currency
projects[] = payment_webform
;projects[] = paypal_payment // patched
projects[] = webform_paypal

;****************************************
; End
;****************************************
