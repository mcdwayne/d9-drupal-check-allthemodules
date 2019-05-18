;****************************************
; General
;****************************************

; Description
; A drush makefile for Drupal modules.

; drush make API version
api = 2

; Drupal core
core = 8.x

;****************************************
; Modules
;****************************************

projects[] = address
projects[] = auto_entitylabel
projects[] = backup_migrate
projects[] = blockgroup
projects[] = captcha
projects[] = colorbox
projects[] = colorbox_inline
projects[] = colorbox_load
projects[] = context
projects[] = ctools
projects[] = devel
projects[] = empty_page
projects[] = entity
projects[] = entity_clone
projects[] = entity_reference_revisions
projects[] = environment_indicator
projects[] = eva
projects[] = features
projects[] = field_group
projects[] = flexslider
projects[] = footnotes
projects[] = google_analytics
projects[] = honeypot
projects[] = inline_entity_form
projects[] = leaflet
projects[] = leaflet_markercluster
projects[] = libraries
projects[] = memcache
projects[] = memcache_storage
projects[] = metatag
projects[] = ng_lightbox
; ^ Depency of colorbox_load
projects[] = nice_menus
; Patched version to allow numeric values in config options
; projects[] = packery
projects[] = pathauto
projects[] = paragraphs
projects[] = profile
projects[] = matomo
projects[] = recaptcha
projects[] = redirect
projects[] = search_api
projects[] = simple_gmap
projects[] = simple_sitemap
projects[] = token
projects[] = views_infinite_scroll
projects[] = views_slideshow
projects[] = webform

;; Includes for other module related content.

includes[] = modules/libraries.make
includes[] = modules/patches.make
