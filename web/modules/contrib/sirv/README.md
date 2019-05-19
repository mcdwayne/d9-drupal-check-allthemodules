# Sirv module

The Sirv module provides support within Drupal for the image services
provided by [Sirv](http://sirv.com/), including dynamic images and Sirv
Zoom.

## Dependencies

To use Sirv’s image services within Drupal, you will need either a free
or paid Sirv account. For more information or to create an account,
visit [sirv.com](http://sirv.com/).

In order to allow images to be uploaded to Sirv within Drupal, the
module requires integration with Amazon’s S3 file service, which is
provided by the [S3 File System](https://www.drupal.org/project/s3fs)
module.

**Important: In order to use Sirv for image fields, you will need to
remove the itok query parameter from image URLs by adding the following
line to your settings.php file:**

```$config['image.settings']['suppress_itok_output'] = TRUE;```

## What does this module do?

### Dynamic images

Sirv can be used as a replacement for Drupal’s core image styles, which
provide options for scaling, cropping, and other methods of
manipulation. Besides the core image effects provided by Drupal, Sirv
offers many more options, including color adjustment and filtering,
vignette effects, frames, and watermarks.

The equivalent of Drupal’s image styles in Sirv are profiles, which are
stored in JSON format in files within your Sirv account. Profiles are
packages of options, available for use by any images uploaded to your
account.

If you are using a module that allows S3 to be used for image fields,
choose S3 as your upload destination when creating the field.

### Sirv Zoom

Sirv Zoom lets you rapidly zoom into images to see detail. This module
does not yet support Sirv Zoom.

### Sirv Spin

Sirv Spin provides support for 360-degree and 3D spinning images. This
module does not yet support Sirv Spin.
