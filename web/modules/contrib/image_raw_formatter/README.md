Image Raw Field Formatter
=====================

Image Raw Formatter for Drupal 8. This formatter return URLs absolute of original images or image style if is configured. The default formatter for image doesn't work for REST services because return HTML tags for images.

### Install using Drupal Console project
```bash
$ cd path/to/drupal/8/modules/contrib
$ git clone https://github.com/enzolutions/image_raw_formatter.git
$ drupal module:install image_raw_formatter # or enable this module via UI
```

### Usage

 * In your content type create a new Image field
 * Go to /admin/structure/types/manage/[Content-Type]/display
 * Change the format field to use Image Raw formatter
 * Or select formatter in views fields images

Resources
---------

You can run the unit tests with the following command:

    $ composer install
    $ vendor/bin/phpunit
