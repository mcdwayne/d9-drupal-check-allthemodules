# Apple News

#### Table of Contents

1. [TL;DR](#tldr)
2. [Configuration](#configuration)
    1. [General settings configuration](#settings)
    2. [Template configuration](#template)
    2. [Channel configuration](#channel)
    3. [Field configuration](#field)
    4. [Preview post](#preview)
    5. [Delete from channel](#delete)
3. [Troubleshoot](#troubleshoot)
4. [Contribute](#contribute)


## <a name="tldr"></a>TL;DR
1. Install latest version of [Apple News]() module using [composer]().
   ```shell
   composer require drupal/applenews
   ```

2. Enable Apple News (`applenews`) -- Core module, you will be able to push content.
3. Configuration
   1. Go to `/admin/config/services/applenews` and add a template
   2. Go to `/admin/config/services/applenews/settings` and add Apple News credentials.
    Check [Use your CMS](https://help.apple.com/newspublisher/icloud/#/apd88c8447e6) section for more details.
   3. Go to `/admin/config/services/applenews/channel` to add channel ID
   4. Go to fields page of entity type (e.g. `admin/structure/types/manage/artice/fields`) and add a new field of type `Apple News`.
4. Publish
   1. Create a new entity
   2. Check `Publish to Apple News` checkbox under `Applenews settings` tab
   3. Select template, channel and sections
   4. Save node as usual


## <a name="configuration"></a>Configuration

Follow these configuration instructions to start publishing your content.

### <a name="settings"></a>General Settings configuration

1. Visit [apple.com](http://apple.com) to get your credentials and create a news channel that your Drupal site will use.

2. In your Drupal site, navigate to the "Apple news credentials page" (`admin/config/content/applenews/settings`) and add your Apple News credentials.

3. In your Drupal site, navigate to the "Apple news channels page" (`admin/config/content/applenews/settings/channels`) and add a channel ID from your Apple account. Please add one ID at a time. The channels are validated by the Apple credentials that you added to your Drupal site. If valid, it will fetch the channel information and add them to your site's list of channels.

### <a name="template"></a>Template configuration

Create Apple News "templates" which tie together a content type
 with a layout of Apple News components and the data that is placed into those
components. These are stored as config entities, and so can be defined
in the UI or as YAML in your custom module.


#### Sample template

```
uuid: 4650c85e-ec8c-4ebd-a9f5-d13b61622610
langcode: en
status: true
dependencies: {  }
id: test
label: test
node_type: page
columns: 7
width: 1024
margin: 60
gutter: 20
components:
  ea6c4106-88ea-4171-ad5d-8bfd04664c8d:
    uuid: ea6c4106-88ea-4171-ad5d-8bfd04664c8d
    id: 'default_text:author'
    weight: -10
    component_layout:
      column_start: 0
      column_span: null
      margin_top: 0
      margin_bottom: 0
      ignore_margin: none
      ignore_gutter: none
      minimum_height: 10
      minimum_height_unit: points
    component_data:
      text:
        field_name: title
        field_property: base
      format: none
  4f2c21df-d3cf-4bca-85f3-b45f7862c617:
    uuid: 4f2c21df-d3cf-4bca-85f3-b45f7862c617
    id: 'default_image:photo'
    weight: -9
    component_layout:
      column_start: 0
      column_span: null
      margin_top: 0
      margin_bottom: 0
    component_data:
      URL:
        field_name: title
        field_property: base
      caption:
        field_name: title
        field_property: base
```

#### Components

The module comes with a set of default components as defined by the 
Apple News documentation. Each one is mapped to a Component class from
[chapter-three/AppleNewsAPI](https://github.com/chapter-three/AppleNewsAPI).

Each component has a "meta-type" that defines what it predominantly
displays. Currently, there are 4 types:

- text
- image
- nested
- divider

These are mainly used to determine which normalizer should be used during
serialization. You can define your own "meta-type" by using it in a
custom ComponentType annotation (see below) and by adding the appropriate
schema.

Here is the schema for the text type, as an example:

```
applenews.component_type.text:
  type: mapping
  mapping:
    text:
      type: applenews.field_mapping
    format:
      type: string
      label: 'Format for included text (none, html, or markdown)'
```

You can define your own Apple News component option by putting a class 
in Plugin/applenews/ComponentType, extending ApplenewsComponentTypeBase,
and using the correct annotation.

```
@ApplenewsComponentType(
 id = "your_component_id",
 label = @Translation("Your component label"),
 description = @Translation("Your component description"),
 component_type = "image",
)
```

Component plugins can be altered via 
hook_applenews_component_type_plugin_info_alter().

#### Normalizers

The module makes use of Drupal's Serialization API by defining several
custom normalizers. These are applicable with the format "applenews". 

Overriding a normalizer is another way your module can provide additional
customization. In your *.services.yml file, declare your normalizer
service and give it a priority higher than the one your are trying to
override from applenews.services.yml.

It is recommended, though not required, to have your normalizer class
extend one of the Apple News base normalizers.

### <a name="channel"></a>Channel Configuration

### [NEEDS UPDATE]

An *export* is code that defines how to transform data in a Drupal site so it can be pushed to Apple News. The Apple News module defines a simple export, while the `applenews_example` module defines a more usable style.

To get started, we suggest enabling the `applenews_example` module and using that as a starting point.

1. In your Drupal site, navigate to the "Apple news export manager page" (`admin/config/content/applenews`).
2. Click on the **'edit'** link of the export you would like to connect to an Apple News channel.
3. On the Edit page, the minimum requirements to properly configure a channel to an Apple News channel are:
    1. Under "Add new component", select a component.
    2. Under "Channels", select the channel (Apple News Channel) that this export will be tied to. This export channel will get nodes, process them, and send them to the selected channel for display in the Apple News app.
    3. Under "Content types", select the content types that should be processed with this channel.
    4. Click **Save Changes**.
    5. After saving, you will see "edit" and "delete" options to the right of the new components we just added. Click on **"edit"**.
    6. Configure the component. Most components will require that you specify source fields and the component will use the data in those fields as content in the component.
    7. Click **Save Changes**.

### <a name="field"></a>Field Configuration

### [NEEDS UPDATE]

Once a content type is enabled in an export/channel, the option to add the individual post is in the node's add/edit page. If a content type is not added to any channel export, these options will not be available on the node add/edit page.

1. To add a node to the channel sent to Apple, select _"Publish to Apple"_ in the "Apple News" tab. If you want to temporarily stop publishing to Apple, or make revisions to the post before publishing or re-publishing to Apple, deselect this checkbox. It is the equivalent to the "Publish/Unpublish" feature with Drupal nodes.

2. Select one or more channels from the available list.

3. For each selected channel, select an available "section" that it belongs to. ("Sections" are created on apple.com, where you initially created the channel).

4. Once a node is initially published to an Apple news channel, it will display a general information section showing post date, share URL, and the section and channel where it is published.

### <a name="preview"></a>Preview a Post Before Publishing

If you want to preview a post before sending it to Apple, you will need to first download and install the Apple "News Preview" Application (LINK TBD).

1. After saving the node, return to the node's edit page
2. Find the "Apple News" tab, and click the "Download" link under "Preview." This will download a folder containing the specialy formatted file needed by the News Preview App.
2. Drag the whole folder into the App icon to open, and it will display the page just as the Apple News App will be displaying it.

### <a name="delete"></a>Delete a post from publishing

If you want to delete a post from a channel, but not delete the post itself, There is a **delete** link in the "Apple News" tab.


## Congrats!

You are now ready to start sharing your posts and articles with the Apple News Service and with the world. Happy Posting!

## <a name="troubleshoot"></a>Troubleshoot

---

## <a name="contribute"></a>Contribute
Feel free to open an
[issue](https://www.drupal.org/project/issues/applenews) to improve, add
new features and bug fixes.


### Run Tests

To run module tests, enable the core "Testing" module, from the Modules admin page or with command line.

To enable and run tests from the UI:

1.  Navigate to the Testing admin page (`admin/config/development/testing`).
2.  Select "Apple News" from the list of tests
3.  Click **Run Tests**

To run test from command line, enter the following commands one at a time:

```shell
drush -y en simpletest
php core/scripts/run-tests.sh --verbose --color --module applenews
```
