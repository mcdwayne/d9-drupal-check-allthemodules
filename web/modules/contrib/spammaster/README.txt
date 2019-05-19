CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Spam Master Protection module for drupal blocks new user registrations,
comments and threads using Real Time anti-spam lists as Saas.

 * For a full description of the module visit:
   https://www.drupal.org/project/spammaster

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/spammaster


FEATURES
--------

 * Protects Drupal from User Registrations
 * Protects Drupal Comments
 * Protects Drupal Forum Threads
 * Protects Drupal Pages and Posts
 * Checks for emails, domains, ip's, and words
 * Uses real time scan from millions of known spamming sources
 * Includes Statistical information
 * Spam Master Learning makes your Drupal an Anti-Spam enforcer
 * Allows the user to customize the frontend blocked registration message
 * Allows the user to hide the website field from the theme comments form
 * Includes Character Blocking option to individually activate Russian, Chinese,
   Asian, and Arabic characters
 * Includes the famous Google reCAPTCHAs
 * Includes Honeypot fields for registration, login, or comments forms
 * Includes Firewall technology
 * Includes Signatures
 * Includes a brand new automatic Threat Alert Level
 * Includes Spam Activity Probability
 * Includes a bunch of useful blocks
 * Includes several optional email reports and warnings
 * Contact Form Ready
 * PHP 7 ready
 * IPv6 ready


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

To utilize this service completely, a valid license key is required.

 * https://www.techgasp.com/downloads/spam-master-license/


INSTALLATION
------------

 * Install the Spam Master Protection module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > System > Spam Master
       settings to configure the module.
    3. On the Settings tab, pre-set module configuration is included along with
       a trial license. When a license key is acquired enter it here. Save and
       Refresh License.

To configure Protection Tools:
    1. Navigate to Administration > Configuration > System > Spam Master
       settings > Protection Tools to configure the tools.
    2. Block message allows the user to change the message displayed when a user
       is blocked.
    3. Basic Tools activates individual basic tools to implement Spam Master
       across the site. The user can choose to activate the following scans:
       Firewall, Registration, Comment, and Contact scans.
    4. Extra tools allow configurations for Honeypot and Captcha services.
    5. Signatures are a huge deterrent of human scan. The following signatures
       can be displayed: Registration, Login, Comment, and Contact.
    6. Emails and reports can be configured to be sent daily or weekly.


MAINTAINERS
-----------

Current maintainers:
