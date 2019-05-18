CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* FAQ


INTRODUCTION
------------

The official plugin for integrating Finteza web analytics with Drupal websites

What is Finteza?

The system features real-time web analytics:

* Tracking of visits, page views and events
* Incoming traffic quality and visitor behavior analysis
* Conversion funnels
* Intuitive interface
* No delay, no data sampling

For more information, visit [the official Finteza website] (https://www.finteza.com/).

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

    1. Navigate to Administration > Extend Finteza Analytics
    and enable Finteza Analytics and Finteza Analytics CKEditor Plugin modules.
    2. Open "Configuration > Content authoring > CKEditor",
    select the editor profile that supports HTML and click to Configure.
    3. In section "TOOLBAR CONFIGURATION" move (drag and drop) the button
    "Finteza Analytics" from "Available buttons" into "Active toolbar".
    4. Open "Configuration > System > Finteza Analytics" and configure
    params of the Finteza Analytics.

If there is enabled option "Limit allowed HTML tags and correct faulty HTML":

  - you need to add attribute 'data-fz-event' to the HTML tag manually,
  for example: `<a data-fz-event>` and then edit it by plugin button.

  Supported tags: `<a>, <button>, <input type='sumbit'>`.


FAQ
-----------

= What is Finteza? =

The system features real-time web analytics. For more information,
visit [the official Finteza website] (https://www.finteza.com/).

= Is it free? =

Yes, the web analytics system and the plugin are free.

= Where do I get the website ID? =

The ID will be provided to you after registration in Finteza:

* automatically during registration, in plugin settings, or
* in the platform panel

= How do I register in Finteza? =

* In plugin settings, after the plugin installation
* On the [platform website](https://www.finteza.com/en/register)

= Where do I view statistics? =

On the [Finteza panel](https://panel.finteza.com/). Log in using the email
and password specified during registration.
If you forgot the password,
use the [password recovery] (https://panel.finteza.com/recovery) page
