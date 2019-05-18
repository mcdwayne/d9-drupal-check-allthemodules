********************************************************************
D R U P A L    M O D U L E
********************************************************************
Name: Feeds BrightTALK Module
Author: Robert Castelo <www.codepositive.com>
Project:  http://drupal.org/project/brighttalk_field
Drupal: 8.x
********************************************************************
DESCRIPTION:

The Feeds BrightTALK module provides a custom feed parser. This
parser makes it very simple to map BrightTALK feed data onto Drupal
fields.

You can set up your own content type and fields to map feeds onto,
or more simply enable the BrightTALK Channel module which will
automatically create and map these for you.


DEPENDENCIES
------------

Feeds module
http://drupal.org/project/feeds


INSTALLATION
------------

Do not enable this module!

Instead copy the parser file of this module into the parsers
directory of the Feeds module.

feeds_brighttalk/copy-to-feeds/BrightTALKParser.php

Copy to:

feeds/src/Feeds/Parser/BrightTALKParser.php


CONFIGURATION
-------------

1. Create a content type for webcasts.

2. Create a feed importer at admin/structure/feeds

    Fetcher = Download

    Parser = BrightTALK

    Processor = node.

    Content type = the content type for webcasts

3. Click on the Mapping tab and map source and target fields

   Title (Source) = Title (Target)


4. Set up a feed at admin/content/feed

   Click '+Add feed'





********************************************************************
USAGE:

Create a BrightTALK Channel node with these settings

     Title
     ---------------------------------------------------------------
     Set to title of the channel.

     Overview
     ---------------------------------------------------------------
     A description of the channel.

     Feed URL
     ---------------------------------------------------------------
     The URL of the channel's feed.

     Example: http://www.brighttalk.com/channel/43/feed

     Channel ID
     ---------------------------------------------------------------
     Will be filled in automatically.

     Channel Homepage URL
     ---------------------------------------------------------------
     Will be filled in automatically.

     Channel Description
     ---------------------------------------------------------------
     Will be filled in automatically.

     Channel Title
     ---------------------------------------------------------------
     Will be filled in automatically.


Once a Channel node has been created Webcast nodes featuring
BrightTALK webcasts will be created automatically.



