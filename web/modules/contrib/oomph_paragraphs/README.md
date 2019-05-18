# Oomph Paragraphs

Reusable paragraph bundles for Drupal 8.

## Getting Started

This module is only available currently as a private repository in Oomph, Inc's
organization on GitHub. In order to include this module in your project you will
need to add a new repository to your Composer configuration and install composer
from a device that is setup to access our private repostitories over SSH.

### Requirements

This module requires your project to be running Drupal 8 and PHP 7.1+. A list of dependencies can be found in [composer.json][] and [oomph_paragraphs.info.yml][].

### Installation

In your terminal require `drupal/oomph_paragraphs` with Composer:

```bash
$ composer require drupal/oomph_paragraphs
```

If you're using a project that's setup with `composer/installers` listening for
the `drupal-module` package type, then the module will be installed in the
`modules/contrib` directory of your Drupal project.

Video Paragraph Bundle - To allow users the maximum flexibility we removed the
need for Video Embed as a dependency. This allows users to select between Core
Media or whatever media solution they would like. As noted below after install
the user will need to add a Video field to the Video Paragraph Bundle.

## Paragraph Bundles

Currently five (5) pre-configured paragraph bundles are included with this
module. The paragraph types that ship with this module are configured based on a
component design.

### Row

The Row is a container for other components. It supports fields for:
- Alignment: along horizontal and vertical axis via drop down select lists
- Background color: Drop down select list of color class names
- Layout: 18 different layout configurations via drop down select list
- Border top and bottom on the row container as a boolean value
- Borders between components inside as a boolean value
- Animation: Drop down select list of animation values
- Extras: "triangle" down caret design element
- Custom CSS Class(es): Plain text field for string

A background image on a row is used for decoration. The image is centered in the
row container, but the contents of the row determine the size. The image is
centered vertically and will always be as wide as the row container. For mobile,
the image will sit above the content of the row inside of it, and the row has
an additional configuration to hide the image for mobile viewports.

### Hero

The Hero acts like a row, and supports a background image and ONE WYSIWYG
component. It will maintain the aspect ratio of an image in modern browsers
(non IE11). The contents maintain their position over the image content for
mobile.

Fields include:
- alignment along horizontal and vertical axis
- simplified layout for as single componernt

Fields/Config:
- Content: CK Editor enhanced textarea
- Background color: Drop down select list of color class names
- Alignment: Vertical and horizontal via drop down select lists
- Layout: Drop down select list of Layout properties for the content area inside
- Animation: Drop down select list of animation values for the content area inside
- Custom CSS Class(es): Plain text field for string

#### Row with Background Image vs. Hero

There might be come confusion between the two. Let's compare/contrast:
- Hero keeps any content over the image at all times, even for mobile
- Row with background image takes the content off of the image for mobile
- Hero maintains the image's natural aspect ratio throughout the RWD cycle
- Row with background image uses the image as decoration under the content —
content determines the size of the row container after mobile viewports

The Hero component allows the page design to be more intentional about these
visual breaks on a page between rows, and reduces the confusion around how
background images work on rows (why don't they do what I expect a Hero to do?).

### Column Group

Layout inside of a row is done with CSS Flexbox. Components that sit inside a
row will be treated as columns when a layout is applied. In some circumstances,
an author may want to group two or more components together to be treated as a
single column. The column group allows this, and supports any number or type of
component inside, to be treated as a single column entity in the Layout chosen.

Fields/Config:
- Background color: Drop down select list of color class names
- Custom CSS Class(es): Plain text field for string

### WYSIWYG

The wysiwyg component is similar to the "body" field found by default on Drupal
content types. This allows content editors to style text with in a CKEditor
field with similar controls to a word processing application.

Fields/Config:
- Content: CK Editor enhanced textarea
- Background color: Drop down select list of color class names
- Boolean: Drop shadow on or off
- Animation: Drop down select list of animation values
- Custom CSS Class(es): Plain text field for string

### Image

The image component accepts image uploads. The default configuration that ships
with this module currently uses the `image` field, however, any image fields can
be removed and replaced with a `responsive_image` field with the same machine
name for the field.

Fields/Config:
- Image: Upload field for image media with Alt
- Boolean: Image border on or off
- Animation: Drop down select list of animation values
- Custom CSS Class(es): Plain text field for string

### Video

The video paragraph component uses the `video_embed_field` module to embed
videos into content types. This allows for content editors to upload a video
from a 3rd party provider.

Fields/Config:
- Video: This Field NEEDS TO BE ADDED after installation.
- Animation: Drop down select list of animation values
- Custom CSS Class(es): Plain text field for string

### Accordion

The accordion component works as the name suggests. A summary is displayed along
with an optional icon/image, and a default "+" visual indicator. On click, tap,
or key enter, the accordion will open or close.

Fields/Config:
- Summary: the text shown at all times
- Background Image/Icon, optional
- Details: CK Editor enhanced textarea, hidden by default
- Background color: Drop down select list of color class names
- Boolean = Accordion Open. Force the accordion details to be open on page load
- Custom CSS Class(es): Plain text field for string

## Configuration

This module ships with "optional" configuration. That means it will install
when the module is enabled only if that set of configuration does not already
exist in Drupal.

### Updating configuration for this module

If the need arises to update configuration for this module using the
Configuration Management UI in Drupal or using Drush, the configuration files
exported will include a `uuid` value associated with the site's configuration.
The Configuration Installer component in Drupal cannot import configuration from
a module with a `uuid` value.

A script is defined with this module's `composer.json` to automatically strip
the `uuid` values in the configuration files located in `config/optional`.

```bash
$ composer remove-config-uuid
```

## TODOs

- [x] Revisit structual styles created by @jhogue to reduce the amount of scss
      files to be compiled and group styles in individual component files.
- [x] Register compiled styles in `oomph_paragraphs.libraries.yml` and load
      component styles in the component twig templates using
      `{{ attach_library('oomph-paragraphs/[component_name]') }}`
- [x] Investigate what bootstrap components are dependencies of the styles for
      this module.
- [x] Change style selectors from `.par__` to `.paragraph__`.
- [ ] Clean up Twig template code in template files.
- [x] Investigate if this module can be uploaded to drupal.org so we can easily
      require this module in our projects.
- [ ] Create process of contributing to this project and create process for
      release management.
- [ ] Investigate if `response_image` fields should be shipped with this module
      and if we should create a default set of responsive image configuration.

[composer.json]: composer.json
[oomph_paragraphs.info.yml]: oomph_paragraphs.info.yml
