CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

Simple module that provides a configurable block and field formatter
which automatically sync your tour information, ticket links, Facebook events,
and Bandsintown specials to your website. It is based on the Bandsintown widget:
http://news.bandsintown.com/home. The widget settings are configurable in the
block settings or in the field settings on the content type. There are also
few settings on the Bandsintown admin page. They are available for users with
"Administer Bandsintown widget" permission. The widget itself is available for
users with "Access Bandsintown widget" permission.


REQUIREMENTS
------------

Bandsintown has two dependencies.

Drupal core modules
 * Block
 * Field


INSTALLATION
------------

* Install as usual,
see https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------

* Go to People > Permissions
* Configure "Administer Bandsintown widget" and "Access Bandsintown widget"
permissions
* Go to Configuration > User interface > Bandsintown
* Configure "Show Track button" and "Include Artist Name" settings
* Go to Structure > Blocks
* Place Bandsintown block in a preferred region (e.g. Content) and configure
block and widget settings. The main required setting is Artist name. Others
are set by default and ready to go. You can find detailed explanation to each
of them on Bandsintown API official page:
http://www.bandsintown.com/artist_platform/tour_dates_widget/documentation
* Go to the settings on preferred content type and add Bandsintown field and
configure settings for that field


Current Maintainers:

 * buenos https://www.drupal.org/u/buenos
