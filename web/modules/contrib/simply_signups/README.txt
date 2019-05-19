CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Configuration
 * Installation
 * Maintainers

INTRODUCTION
------------

The Simply Signups module allows users to set up a simple event
rsvp/signup system. Every event can have its own unique rsvp form.
There is also templating available. You can set up a form template
that can be loaded and adjusted on a per node/event basis.

REQUIREMENTS
------------

 * Drupal 8
 * Address Module
 * Datetime Module
 * Options Module
 * Telephone Module
 * Token Module

CONFIGURATION
-------------

 1. Visit /admin/modules and enable the simply_signups module.
 2. Visit /admin/config/simply-signups/config and select which content type(s)
    that you wish to enable signups for.

INSTALLATION
------------

 1. Visit /admin/structure/types/manage/[content type]/display and place the
    "Sign up form" field, wherever you want it to be displayed for your
    content type.
 2. Visit /node/[nid]/simply-signups/settings and configure the settings that
    you want to use for this node. start and end date, event information, and
    email settings.
 3. Add fields to your form at /node/2/simply-signups/fields.
    From there you can apply a template and adjust it to your
    needs, or simply create a form from scratch via the interface.

MAINTAINERS
-----------

 * Ivan Rodriguez (irodriguez)
