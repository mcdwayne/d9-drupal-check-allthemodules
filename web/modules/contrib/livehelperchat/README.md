
LIVE HELPER CHAT INTEGRATION
============================

INTRODUCTION
------------

Live Helper Chat module makes it easy add a live help widget from an existing
Live Helper Chat server to your site.

The module configuration allows you to choose which pages the widget is shown
on, which roles it is shown to, and define all the widget options.

Live Helper Chat is an open source live-support chat service made with PHP.
It also supports the operators using various client software, including XMPP and
 a custom client.

https://livehelperchat.com/

* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/livehelperchat


REQUIREMENTS
------------

You must have a Live Helper Chat instance already running, either the hosted
version at livehelperchat.com or a self-hosted version. This module does not
provide chat functionality on its own.


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. Visit:
  https://drupal.org/documentation/install/modules-themes/modules-7
  for further information.


CONFIGURATION
-------------

* Configure user permissions in Administration » People » Permissions:

  - Administer livehelperchat module
    Permission to change livehelperchat settings.

  - Use PHP for livehelperchat visibility
    Permission to set PHP conditions to customize livehelperchat visibility on
    various pages.

* Customize the widget settings in Administration » Configuration » System »
  Live Helper Chat.

  - Set the Live Helper Chat base URL to point to your LHC installation.

  - Configure the widget settings to match your preferences. This will be very
    similar to configuring the widget from the Live Helper Chat back office GUI.

  - Configure Role and Page specific visibility settings.


CREDITS
-------
Current maintainer:
* Jenna Tollerson (jenna.tollerson) - https://drupal.org/user/147099

This module was originally created by Jyri-Petteri Paloposki (ZeiP -
https://www.drupal.org/user/201465), who based it on the Zopim Drupal module:
http://drupal.org/project/zopim
Zopim was originally developed by Nicholas Alipaz of Stitch Technologies:
http://www.stitch-technologies.com/
