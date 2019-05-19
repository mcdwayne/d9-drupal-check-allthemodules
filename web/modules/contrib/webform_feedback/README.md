
Module: Webform Feedback
Author: Protitude <http://drupal.org/user/79388>


Description
===========
Adds a pop-up lightbox like feedback form.
Inspired by http://www.feedbackify.com

Requirements
============

* Webform
* webform_node

Installation
============
* Copy the 'webform_feedback' module directory in to your Drupal
modules/contrib directory as usual.
* Enable module (requires webform module).
* Set the "Webform Feedback Block" to show up
on your site either through the default block page
(/admin/structure/block). The location is up to you.
I usually set it in the footer area.
* For Drupal 8, this module is using the built in modal,
so you will need to enable the Quick Edit permission for
anyone that needs to see the pop-up. The permission is
called 'Access in-place editing'.

Optional - for current webforms
===============================
* Create a webform that you want to use as a pop-up.
* Go to webform_feedback settings
(admin/config/content/webform-feedback).
	* Select your webform and hit save.
  * Set the region on the default block page.
