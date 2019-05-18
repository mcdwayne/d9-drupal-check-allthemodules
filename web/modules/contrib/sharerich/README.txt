Module: Sharerich

Description
===========
Customisable Share buttons for social media.

Installation
============
Put the modules into your sites/module/contrib folder and enable the module.
Go to the permissions page and set them accordingly.

Requiring RRSSB library
=======================
Add this vcs to your main composer.json

	"repositories": [
		{
			"type": "package",
			"package": {
				"name": "kni-labs/rrssb",
				"version": "1.13.1",
				"type": "drupal-library",
				"source": {
					"url": "https://github.com/kni-labs/rrssb.git",
					"type": "git",
					"reference": "master"
				},
				"dist": {
					"url": "https://github.com/kni-labs/rrssb/archive/1.13.1.zip",
					"type": "zip"
				},
				"require": {
					"composer/installers": "~1.0"
				}
			}
		}
	]

then

	"require": {
		"php": ">=5.5.0",
		"kni-labs/rrssb": "~1.0"
	}

Configuration
=============
- Visit /admin/config/sharerich/settings for general settings;
- Visit /admin/structure/sharerich and create your own button sets;
- Visit /admin/structure/block and place Sharerich blocks.
- Out of the box, Drupal doesn't allow the whatsapp and javascript protocols. If you want to use the
  whatsapp or print button, you need to add entries to services.yml (On the same folder where the
  site's settings.php is). Add the following entries.

  parameters:
    filter_protocols:
      - whatsapp
      - javascript

Notes
=====

  - Facebook share:

  It looks like Facebook is now ignoring any custom parameters on the share widget (https://developers.facebook.com/x/bugs/357750474364812/)
  Since facebook.inc service uses www.facebook.com/sharer/sharer.php, it will pull the information from the Open graph tags of the Url being shared.
  If you want to use custom information, you need to use the widget below. Please note that you will need to have a Facebook App Id and Site Url.

  <a href="https://www.facebook.com/dialog/feed?redirect_uri=[sharerich:fb_site_url]&display=popup&app_id=[sharerich:fb_app_id]&link=[sharerich:url]&name=[sharerich:title]&description=[sharerich:summary]" class="popup">
    <span class="icon">
        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="28px" height="28px" viewBox="0 0 28 28" enable-background="new 0 0 28 28" xml:space="preserve">
            <path d="M27.825,4.783c0-2.427-2.182-4.608-4.608-4.608H4.783c-2.422,0-4.608,2.182-4.608,4.608v18.434
                c0,2.427,2.181,4.608,4.608,4.608H14V17.379h-3.379v-4.608H14v-1.795c0-3.089,2.335-5.885,5.192-5.885h3.718v4.608h-3.726
                c-0.408,0-0.884,0.492-0.884,1.236v1.836h4.609v4.608h-4.609v10.446h4.916c2.422,0,4.608-2.188,4.608-4.608V4.783z"/>
        </svg>
    </span>
    <span class="text">facebook</span>
  </a>


  - To alter the buttons markup.

  hook_sharerich_buttons_alter(&$buttons) {

  }

TODO
====
- google_analytics_et (If the module is enabled, GA event tracking will be added to the share buttons)
