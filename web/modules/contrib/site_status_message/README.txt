
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Customisation
 * The Future


INTRODUCTION
------------

Current Maintainer: Gideon Cresswell (SkidNCrashwell)
<https://www.drupal.org/u/skidncrashwell>

The Site Status Message is a simple module to display a site wide message to
your users at the top of each page. Use cases could be to inform of known
downtime in the future, to advertise a special offer on the site or some
important news that needs highlighting.

An optional link to a page with more information can be displayed after the
message.

It was completely inspired by the Drupal.org downtime message displayed October
2013 before the update of Drupal.org from Drupal 6 to Drupal 7.


REQUIREMENTS
------------

None.


INSTALLATION
------------

Install as usual, see
https://www.drupal.org/documentation/install/modules-themes/modules-7 (Druapl 7)
or https://www.drupal.org/documentation/install/modules-themes/modules-8 (Drupal
8) for further information.


CONFIGURATION
-------------

The module can be configured on the Site Information (Drupal 7) or Basic site
settings (Drupal 8) configuration page at admin/config/system/site-information.
The message to be displayed can be up to 256 characters in length and the page
with more information can be an internal path (or external on the
Drupal 8 version) on the site. The message can optionally be displayed on all
the Admin pages of the site too.

If the message box is left blank, no message will be displayed.


CUSTOMISATION
-------------

The module ships with a template file that can be overridden in your own theme.

Copy the entire site-status-message.tpl.php (Drupal 7) or
site-status-message.html.twig (Drupal 8) into your theme directory to make
your own HTML changes.

On Drupal 7, further customisation can be made by copying the preprocess
function site_status_message_preprocess_site_status_message() to your
template.php and rename to THEME_preprocess_site_status_message().

A single CSS class #site-status is provided in the CSS file with the module
which can be overridden in your own CSS.


THE FUTURE
----------

In the future I would like to add these enhancements -

 * Allow external URLs to be used for the Read More link (Drupal 7)
 * Schedule messages
 * Show messages on specific site pages
