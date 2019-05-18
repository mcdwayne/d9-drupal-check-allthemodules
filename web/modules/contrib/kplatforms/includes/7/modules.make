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

projects[] = adminrole
projects[] = admin_menu
projects[] = admin_views
projects[] = advanced_help
projects[] = ais
projects[] = apps
;projects[] = backup_migrate  / pinned
projects[] = backup_migrate_files
projects[] = block_class
;projects[] = boxes  // patched
projects[] = breakpoints
projects[] = captcha
projects[] = calendar
projects[] = ckeditor
projects[] = cck
projects[] = chr
projects[] = colorbox
projects[] = content_access
projects[] = context
projects[] = css3pie
projects[] = ctools
projects[] = date
projects[] = date_ical
projects[] = dblogin
projects[] = diff
projects[] = elements
;projects[] = elfinder // pinned - Emmanuel - 2015-01-23
projects[] = email
projects[] = entity
;projects[] = entityreference  // patched
projects[] = entityreference
projects[] = entity_autocomplete
projects[] = entity_translation
projects[] = environment_indicator
projects[] = facetapi
projects[] = features
projects[] = feeds
projects[] = field_collection
projects[] = field_group
projects[] = file_entity
projects[] = flag
;projects[] = geshifilter  // patched
projects[] = globalredirect
projects[] = google_analytics
projects[] = hidden_captcha
projects[] = honeypot
;projects[] = i18n // pinned - Emmanuel - 2015-02-20
projects[] = i18n_boxes
projects[] = i18nviews
projects[] = imce
projects[] = imce_mkdir
projects[] = imce_wysiwyg
projects[] = insert
projects[] = insert_view
projects[] = jcarousel
projects[] = job_scheduler
;projects[] = jquery_update // set to version 3.x
projects[] = l10n_client
;projects[] = l10n_update // patched
projects[] = l10n_update
projects[] = leaflet
projects[] = leaflet_markercluster
projects[] = leaflet_more_maps
projects[] = libraries
;projects[] = lightbox2 // pinned - Emmanuel 2016-04-26
projects[] = link
projects[] = location
projects[] = mailsystem
projects[] = markdown
projects[] = masquerade
projects[] = maxlength
projects[] = media
projects[media_flickr][version] = 2
projects[] = media_oembed
projects[] = media_vimeo
projects[] = media_youtube
projects[] = memcache
projects[] = memcache_storage
projects[] = menu_attributes
projects[] = menu_block
projects[] = metatag
projects[] = mimemail
projects[] = module_filter
projects[] = multiupload_imagefield_widget
projects[] = multiupload_filefield_widget
projects[] = navigation404
; projects[] = nice_menus // pinned to 2.x
projects[] = nodequeue
projects[] = og
projects[] = openidadmin
projects[] = page_title
projects[] = panels
projects[] = pathauto
projects[] = pathauto_persist
projects[] = pathologic
projects[] = matomo
;projects[] = print  // pinned
projects[] = profile2
projects[] = publishcontent
projects[] = realname
;projects[] = recaptcha // pinned
projects[] = redirect
projects[] = rules
projects[] = service_links
projects[] = simplenews
projects[] = special_menu_items
projects[] = stringoverrides
projects[] = strongarm
projects[] = styles
;projects[] = superfish // pinned
projects[] = taxonomy_menu
projects[] = title
projects[] = token
projects[] = translation_table
projects[] = transliteration
projects[] = variable
projects[] = variable_extra
projects[] = views 
projects[] = views_accordion
projects[] = views_boxes
projects[] = views_bulk_operations
projects[] = views_slideshow
projects[] = webform
projects[] = webform_conditional
projects[] = webform_layout
projects[] = wysiwyg
projects[] = xmlsitemap

;****************************************
;* Libraries and Patches
;****************************************

includes[] = modules/libraries.make
includes[] = modules/patches.make
includes[] = modules/pins.make

;****************************************
; End
;****************************************
