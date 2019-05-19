Module Name: AM Email Login
========================================================================================================================
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration

INTRODUCTION
------------
This module allows the user registration and login using same form by sending a verifying one time login link to the user's.
User's get registered for the first time.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module.
Simply Enable module from /admin/modules page.

========================================================================================================================
CONFIGURATION
-------------
* MAIL Config
	Default Mail Template:

	Subject:
	One-time Login details for [user:display-name] at [site:name]

	Body:
	[user:display-name],

	You may now log in by clicking this link or copying and pasting it into your browser:

	[user:one-time-login-url]

	This link can only be used once to log in.

	--  [site:name] team


	Email Template Config Path: admin/config/am_registration/settings

* Login Block
  Place login block to any region by visiting /admin/structure/block
  Block should be configured to roles - Anonymous users.	
========================================================================================================================