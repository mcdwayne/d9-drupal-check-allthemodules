# CKEditor TweetThis

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module is a plugin for the CKEditor in Drupal 8.
This module adds a TweetThis button in CKEditor for creating tweetable links.
TweetThis button can be used to convert CKEditor text contents into
tweetable links.
The tweetable links upon clicking will navigate to the twitter page
with a text, page url and a via attriute.
The via option can be edited from the module configurations.
The tweetable links can be disabled by using the 'Unlink' 
button in the CKEditor.
This plugin is an implementation of the [Tweet Web Intent]
(https://dev.twitter.com/web/tweet-button/web-intent)

REQUIREMENTS
------------

This module requires the following:
Drupal 8.
CKEditor (Now part of the Drupal  8 core )

INSTALLATION
------------

Install as usual,
see https://www.drupal.org/docs/8/extending-drupal-8/
for further information.

CONFIGURATION
-------------

After successfully installing the module, enable the module.
Go to the text formats and editors [/admin/config/content/formats].
Configure the text format in which the TweetThis option need
to be added. For Eg. configure the 'Full HTML'
In the configuration inteface a button with Twitter logo
will be visible. 
Drag and drop that 'TweetThis' button into the 'Active toolbar'.
Group the 'TweetThis' under the 'Linking' section.
Save the configuartion.

The content with the TweetThis enabled text format will have
the TweetThis button in the editing page.
The TweetThis button works similar to a link button.
Select a plain text and press the TweetThis button.
The selected button will be convered to a tweetable link.
The tweetable links can be disabled by using the 'Unlink'
button in the CKEditor.

MAINTAINERS
-----------

Sajini Antony
https://www.drupal.org/u/sajiniantony

Sreeraj P.
https://www.drupal.org/u/sreerajp
