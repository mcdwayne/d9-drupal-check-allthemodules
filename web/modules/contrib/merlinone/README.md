# MerlinOne DAM Integration #

## INTRODUCTION ##

The MerlinOne module enables searching for and importing Media from the
MerlinOne DAM using an Entity Browser.

## REQUIREMENTS ##

For the Image provider:

 * [Entity Browser](https://www.drupal.org/project/entity_browser)

Additionally, for the File provider:

 * MerlinOne Document (included)

## Recommended Modules ##

 * [Media Entity Browser](https://www.drupal.org/project/media_entity_browser)
   for image browsing and previews
 * [Entity Embed](https://www.drupal.org/project/entity_embed) to embed media
   within content

## INSTALLATION ##

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.

## CONFIGURATION ##

As a first step after installation you must configure the location of your
Merlin MX system at
Administration > Configuration > Web Services > MerlinOne Settings
(/admin/config/services/merlinone).

Once you have done this, create a Media type using the MerlinOne Image provider
(or the MerlinOne Document provider if you are using MerlinOne Document). You
can also create optional metadata fields and map them to fields that will be
filled with content from the MerlinOne DAM when you do an import.

Assets can then be imported from Merlin using an Entity Browser widget. Once the
Media type is created, add a MerlinOne Search widget to an Entity Browser
configuration.

In the configuration for the Entity Browser module we recommend configuring the
Entity Browser setting for Display plugin to iFrame, and on the next page of the
configuration set the Width of the iFrame to 900 and height to 700 initially.

You can find additional information on using and configuring the Media module in
the [Drupal 8 Media
Guide](https://www.gitbook.com/book/drupal-media/drupal8-guide/details).

## Usage ##

After you log in through the MerlinOne Entity Browser widget, select an asset
by single-clicking on it. You can bring up the large screen view by
double-clicking. After making your selections, click the "Select entities"
button at the bottom of the panel. Once that button is clicked Drupal will
import the items from Merlin and placed in the Media Library and insert them
into the content.
