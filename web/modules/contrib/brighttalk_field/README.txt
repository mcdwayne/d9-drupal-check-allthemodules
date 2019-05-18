********************************************************************
                     D R U P A L    M O D U L E
********************************************************************
Name: BrightTalk Field Module
Author: Robert Castelo <www.codepositive.com>
Project:  http://drupal.org/project/brighttalk_field
Drupal: 8.x
********************************************************************

INTRODUCTION
------------

Provides fields that can display a BrightTalk webcast or channel.

BrightTALK embeds are fully functional and allow users to register
and view content both live and on demand.

For a channel field the user needs to enter a channel ID number.

For a webcast field the user can paste in the embed code.

For more information about available BrightTalk content:

https://www.brighttalk.com


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.



CONFIGURATION
-------------

  Add BrightTalk channel or webcast fields to the content type of your
  choice on the Manage Fields page of the content type.

  * BrightTalk Channel
  - Channel ID Option
  Choose field type 'BrightTalk Channel'.
  You will then be able to add channels to nodes by adding a channel ID
  to the field, e.g. '43'

  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

  * BrightTalk Webcast
  - Embed Code Option
  Choose field type 'BrightTalk Webcast'.
  You will then be able to copy embed code from the BrightTalk site and
  paste it into the field to display a webcast.

  The embed code will look like this:

  <script type="text/javascript" src="https://www.brighttalk.com/clients/js/embed/embed.js">
  </script>
  <object class="BrightTALKEmbed" width="705" height="660">
  <param name="player" value="channel_player"/>
  <param name="domain" value="http://www.brighttalk.com"/>
  <param name="channelid" value="288"/>
  <param name="communicationid" value="209525"/>
  <param name="autoStart" value="false"/>
  <param name="theme" value=""/>
  </object>


MAINTAINERS
-----------

Current maintainers:

Robert Castelo (Robert Castelo) - https://www.drupal.org/u/robert-castelo

This project has been sponsored by:
* BrightTALK


