# Media: Webdam

[![Build Status](https://travis-ci.org/mobomo/media_webdam.svg?branch=8.x-1.x)](https://travis-ci.org/mobomo/media_webdam)
[![Coverage Status](https://coveralls.io/repos/github/mobomo/media_webdam/badge.svg?branch=8.x-1.x)](https://coveralls.io/github/mobomo/media_webdam?branch=8.x-1.x)

## About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

- NOTE: For more information on media fields please see the [Media Entity](https://www.drupal.org/project/media_entity) module and the [Drupal 8 Media Guide](https://drupal-media.gitbooks.io/drupal8-guide/content/modules/media_entity/intro.html)

## About Media entity webdam

This module provides Webdam integration for Media entity (i.e. media type provider plugin).  When a Webdam asset is added to a piece of content, this module will create a media entity which provides a "local" copy of the asset to your site.  These media entities will be periodically synchronized with Webdam via cron.

### Webdam API
This module uses Webdam's REST API to fetch assets and all the metadata.  The Webdam REST API client is provided by [php-webdam-client](https://github.com/cweagans/php-webdam-client)
At a minimum you will need to:

- Create a Media bundle with the type provider "Webdam".
- On that bundle create a field for the Webdam Asset ID (this should be an integer field).
- On that bundle create a field for the Webdam Asset file (this should be a file field).
- Return to the bundle configuration and set "Field with source information" to use the assetID field and set the field map to the file field.

### API authentication
This module uses 2 types of authentication as required by the Webdam API.  The root API credentials for your Webdam account should be configured on the Webdam config page (/admin/config/media/webdam).  Individual Webdam user accounts should be created for each Drupal user that needs to manage and use Webdam assets.  Users will be prompted for their Webdam credentials upon accessing the Webdam asset browser

### Storing field values
If you want to store the fields that are retrieved from Webdam you should create appropriate fields on the created media bundle (id) and map this to the fields provided by WebdamAsset.php.

### Asset status and expiration
If you want to use the Webdam asset status and asset expiration functionality you should map the "Status" field to "Publishing status"

This would be an example of that (the field_map section):

```
langcode: en
status: true
dependencies:
  module:
    - crop
    - media_webdam
third_party_settings:
  crop:
    image_field: field_file
id: webdam
label: Webdam
description: 'Webdam media assets to be used with content.'
type: webdam_asset
type_configuration:
  source_field: field_asset_id
field_map:
  description: field_description
  file: field_file
  type_id: field_type_id
  filename: field_filename
  filesize: field_filesize
  width: field_width
  height: field_height
  filetype: field_filetype
  colorspace: field_colorspace
  version: field_version
  datecreated: created
  datemodified: changed
  datecaptured: field_captured
  folderID: field_folder_id
  status: status
```

Project page: http://drupal.org/project/media_webdam

Maintainers:
 - Jason Schulte https://www.drupal.org/user/143978
 - Cameron Eagans https://www.drupal.org/u/cweagans


## Step-by-step setup guide
This guide provides an example for how to implement the media_webdam module on your Drupal 8 site.

### Module installation
Download and install the media_webdam module and all dependencies.  [See here for help with installing modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).  

- (2017-09-25) The current version of media_entity depends on entity (>=8.x-1.0-alpha3) which may have to be [installed manually](https://www.drupal.org/node/1499548)
- The [Crop](https://www.drupal.org/project/crop) and Entity Browser IEF modules are recommended for increased functionality

### Configure Webdam API credentials and Cron settings
You webdam API credentials and Cron settings should be configured at /admin/media/config/webdam.  This module uses cron to periodically synchronize the mapped media entity field values with webdam.  The synchronize interval will be dependent on how often your site is configured to run cron.

- NOTE: The Password and Client Secret fields will appear blank after saving.

### Create Media Bundle for webdam assets
Add a new media bundle (admin/structure/media/add) which uses "Webdam Asset" as the "Type Provider".  

- NOTE:  The media bundle must be saved before adding fields.  More info on creating a media bundle can be found at: https://drupal-media.gitbooks.io/drupal8-guide/content/modules/media_entity/create_bundle.html
- NOTE:  It may be desirable to create separate media bundles for different types of Webdam assets (i.e. "Webdam Images", "Webdam Documents", "Webdam videos", etc).

#### Add a field to store the Webdam asset ID
Add a field to your newly created media bundle for storing the Webdam Asset ID.  The Asset ID field type should be "Number -> Number (integer)" and it should be limited to 1 value.  The Asset ID field should not be configured with a "Default value", "Minimum", "Maximum", "Prefix", or "Suffix"

#### Add a field to store the Webdam asset file
Add a field to your newly created media bundle for storing the Webdam Asset File.  The Asset file field type should be "Reference -> File" and it should be limited to 1 value.

- NOTE: It is not recommended to "Enable display field" for the file field as this currently causes new entities to be "Not Published" by default regardless of the "Files displayed by default" setting.
- NOTE: Webdam asset files are downloaded locally when they are added to a piece of content.  Therefore you may want to [configure private file storage](https://www.drupal.org/docs/8/core/modules/file/overview) for your site in order to prevent direct access.
- NOTE: You must configure the list of allowed file types for this field which will be specific to this media bundle.  Therefore you can create separate media bundles for different types of Webdam assets (i.e. "Webdam Images", "Webdam Documents", "Webdam videos", etc).

#### Optionally add fields to store Webdam asset metadata
Additional fields may be added to store the Webdam asset metadata.  Here is a list of metadata fields which can be mapped by the "Webdam Asset" type provider and the recommended field type.

- status: General -> Boolean
- description: Text -> Text (plain)
- type_id: Text -> Text (plain)
- filename: Text -> Text (plain)
- filesize: Number -> Number (decimal)
- width: Number -> Number (integer)
- height: Number -> Number (integer)
- filetype: Text -> Text (plain)
- colorspace: Text -> Text (plain)
- version: Number -> Number (integer)
- datecreated: General -> Timestamp
- datemodified: General -> Timestamp
- datecaptured: General -> Timestamp
- folderID: Number -> Number (integer)

#### Configure field mapping for media bundle fields
Return to the media bundle configuration page and set the field mappings for the fields that you created.  When a Webdam asset is added to a piece of content, this module will create a media entity which provides a "local" copy of the asset to your site.  When the media entity is created the Webdam values will be mapped to the entity fields that you have configured.  The mapped field values will be periodically synchronized with Webdam via cron.

- REQUIRED: You must create a field for the Webdam asset ID and set the "Type provider configuration" to use this field as the "Field with source information".
- REQUIRED: You must create a field for the Webdam asset file and map this field to "File" under field mappings.

#### Asset status
If you want your site to reflect the Webdam asset status you should map the "Status" field to "Publishing status" in the media bundle configuration.  This will set the published value (i.e. status) on the media entity that gets created when a Webdam asset is added to a piece of content.  This module uses cron to periodically synchronize the mapped media entity field values with webdam.

- NOTE: If you are using the asset expiration feature in Webdam, be aware that that the published status will not get updated in Drupal until the next time that cron runs (after the asset has expired in Webdam).
- (2017-09-26) When an inactive asset is synchronized the entity status will show blank because of [this issue](https://www.drupal.org/node/2855630)

#### Date created and date modified
If you want your site to reflect the Webdam values for when the asset was created or modified you should map the "Date created" field to the "Created" and the "Date modified" field to "Changed" in the media bundle configuration.  This will set the "created" and "changed" values on the media entity that gets created when a Webdam asset is added to a piece of content.  This module uses cron to periodically synchronize the mapped media entity field values with Webdam.

#### Crop configuration
If you are using the [Crop](https://www.drupal.org/project/crop) module on your site, you should map the "Crop configuration -> Image field" to the field that you created to store the Webdam asset file.

### Configure an Entity Browser for Webdam
In order to use the Webdam asset browser you will need to create a new entity browser or add a Webdam widget to an existing entity browser (/admin/config/content/entity_browser).

- NOTE: For more information on entity browser configuration please see the [Entity Browser](https://www.drupal.org/project/entity_browser) module and the [documentation](https://github.com/drupal-media/d8-guide/blob/master/modules/entity_browser/inline_entity_form.md) page on github
- NOTE: When editing and/or creating an entity browser, be aware that the "Modal" Display plugin is not compatible with the WYSIWYG media embed button.  
- NOTE: When using the "Modal" Display plugin you may want to disable the "Auto open entity browser" setting.

### Add a media field
In order to add a Webdam asset to a piece of content you will need to add a media field to one of your content types.

- NOTE: For more information on media fields please see the [Media Entity](https://www.drupal.org/project/media_entity) module and the [Drupal 8 Media Guide](https://drupal-media.gitbooks.io/drupal8-guide/content/modules/media_entity/intro.html)
- NOTE: The default display mode for media fields will only show a the media entity label.  If you are using a media field for images you will likely want to change this under the display settings (Manage Display).

### WYSIWYG configuration
The media entity module provides a default embed button which can be configured at /admin/config/content/embed.  It can be configured to use a specific entity browser and allow for different display modes.

- NOTE: When choosing an entity browser to use for the media embed button, be aware that the "Modal" Display plugin is not compatible with the WYSIWYG media embed button.  You may want to use the "iFrame" display plugin or create a separate Entity Browser to use with the media embed button

