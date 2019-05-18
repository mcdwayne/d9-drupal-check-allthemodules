# IntelligenceBank DAM Integration

## Introduction

This modules suite integrates IntelliganceBank DAM for an importing,
or embedding Assets from the service.
Currently, there are two ways to import/embed assets from DAM:

  * using **IB: Media integration** module,
    based on core Media module and [Entity Browser](https://www.drupal.org/project/entity_browser)
  * using **IB: CKEditor WYSIWYG integration**

------
*Which one should I use?*

Use **IB: Media integration** if your asset workflow based on Media module.
In other case use WYSIWYG integration.

## Requirements

For the **ib_dam_media**:

  * core Media (>= 8.5)
  * [Entity Browser](https://www.drupal.org/project/entity_browser) (>= 2.1)

For the **ib_dam_wysiwyg** 

  * core modules: Field, Filter, CKEditor.

## Installation

Modules can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## Configuration

**Media integration**

[Documentation](https://legacy.gitbook.com/book/drupal-media/drupal8-guide/details) about Media module can be found here

Quick configuration steps:

1. Install and enable [Entity_Browser](https://www.drupal.org/project/entity_browser) module.
2. Enable core Media module.
3. Ensure that you have a couple of Media Types in your system.
4. Install and enable **IB: Media integration** module.
5. Go to the *IntelligenceBank Media Configuration* page `admin/config/services/ib_dam/media`
  * Map each source asset type with the corresponding media type.
  * Map **Embed type** with **IntelligenceBank DAM Embed** media type,
    in order to allow embedding assets from DAM without downloading them locally.
6. Create a new Entity Browser ([documentation](https://drupal-media.gitbooks.io/drupal8-guide/content/modules/entity_browser/creating_browser_through_ui.html)),
   or use your existing one.
  * Add **IntelligenceBank Asset Browser** widget to the list on Widgets page,
     `Edit Entity browser > General information > Display > Widget selector > Selection display > Widgets`.
     You can also change there upload directory setting.
7. Create or use existing Entity reference field of the Media type. For example:
  * Go to the Article content type structure page `admin/structure/types/manage/article/fields`
  * Add new Media Reference field type: Add a new field > Reference > Media
  * On the field settings page select all media types that you want to use.
    (This is a list of Media types which are allowed to insert).
  * Next, you need to set Widget for the newly created field from the steps above:
    Administration  > Structure  > Content types  > Article >
    Manage form display > Your field > Widget: Entity browser,
    and select entity browser from steps above
8. Now you are ready to use **IntelligenceBank DAM Media integration**.
    For example go to the Article node edit page and open the entity browser.


**WYSIWYG integration**

The one is based on the CKEditor module to show Asset Browser,
and on the custom Text filter to render assets.

Quick configuration steps:

1. Enable core CKEditor module.
2. Install and enable **IB: CKEditor WYSIWYG integration** module.
3. Go to the "Text formats and editors" configuration page: `/admin/config/content/formats`,
  and for each text format/editor combo where you want to use assets from DAM,
  do the following:
  * Enable the **IntelligenceBank DAM WYSIWYG** filter,
    in the "Enabled filters" section.
  * Drag and drop the ![☀](http://www.intelligencebank.com/themes/pnc/favicon.ico) button into the Active toolbar.
  * In the "CKEditor plugin settings" section configure such options,
    like: *Upload location*, *Allowed file extensions list*, *Allow public assets*
  * In the "Filter processing order" section ensure that the "IntelligenceBank DAM WYSIWYG" filter **comes after** "Restrict images to this site" (if you using this filter), [related information](https://www.drupal.org/project/entity_embed/issues/2752253)
4. Don't forget to enable permissions to use text filter for specific roles,
   on the user permissions page.

----

## Usage

General Asset Browser [documentation](https://help.intelligencebank.com/hc/en-us/articles/115001513243-About-the-IntelligenceBank-Universal-Connector)

**Media integration**

1. Open the Entity Browser on content editing page,
   select newly created upload widget.
2. Use the modal dialog to browse and get assets.
3. Assets will be automatically fetched to Drupal after you click on download buttons in asset browser.

**WYSIWYG integration**

1. For example, create the new *Article* content.
2. Click on the ![☀](http://www.intelligencebank.com/themes/pnc/favicon.ico) button in the text editor,
   you will get modal dialog with the asset browser.
3. Login in to DAM using your credentials.
4. Browse the assets and click either the "Download" or the "Public" button on a certain Asset 
  * in case of "Download" button, Drupal will download the asset file locally
  * in case of "Public" button, Drupal will get an link to the remote asset,
    and create an embedded asset in the editor.
5. After the asset selection, you can change asset display options at the next step.
6. Save the content and check the uploaded Assets.

## Limitations

Currently, there is no support for [Entity Embed](https://www.drupal.org/project/entity_embed) module to embed media within the content.
