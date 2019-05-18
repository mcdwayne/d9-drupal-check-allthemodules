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

; NT2 Extras
projects[] = bibcite
projects[] = commerce
projects[] = commerce_cart_blocks
; used for commerce modules
projects[] = ludwig

; Swiftmailer + Dependencies
projects[] = mailsystem
projects[] = swiftmailer

; @TODO: swiftmailer libraries?

libraries[commerceguys_addressing][directory_name] = v1.0.0
libraries[commerceguys_addressing][destination] = modules/contrib/address/lib/commerceguys-addressing
libraries[commerceguys_addressing][download][type] = get
libraries[commerceguys_addressing][download][url] = https://github.com/commerceguys/addressing/archive/v1.0.0.zip

libraries[commerceguys_intl][directory_name] = v1.0.1
libraries[commerceguys_intl][destination] = modules/contrib/commerce/lib/commerceguys-intl
libraries[commerceguys_intl][download][type] = get
libraries[commerceguys_intl][download][url] = https://github.com/commerceguys/intl/archive/v1.0.1.zip

; state_machine is a commerce dependency
projects[] = state_machine

;****************************************
; End
;****************************************
