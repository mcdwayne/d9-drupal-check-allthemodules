# Amazon Cloudfront CDN private files integration

This is an API module to assist with [serving private/protected content](http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/PrivateContent.html)
through Amazon Cloudfront.

Access may be controlled by signed URLs (query string parameters) or a
signed cookie.

This module has no UI and serves as an API for developers to respond to a
Symfony event that requests input of the protection status of CDN-rewritten
URIs.

## Known issues

Once this module signs a URL for private files access, it uses the Drupal
page cache "kill switch," since there's no cache metadata associated
with these rewritten URLs and no support (yet) for that in core. This is
configurable when responding to the event, and it's possible you could set
signed cookies at login, for instance, and keep pages cacheable.

See https://www.drupal.org/project/drupal/issues/2669074 for upcoming changes
in Core to URL generation.

### One approach to protecting content using Flysystem

A sample use case would be to use
[Flysystem](https://drupal.org/project/flysystem)
to create a new "protected" stream wrapper to in effect provide a souped-up
version of the private files functionality in Drupal core. CDN module will
refuse to re-write that URL since Flysystem does not consider its streams
"local," however you could build and mark for signing CDN URLs using
the `CdnCloudfrontPrivateEvent`, and then restrict direct access of those
files to only Cloudfront using Custom Origin Headers and an implementation
of `hook_file_download()`:

```php
/**
 * Implements hook_file_download().
 */
function mymodule_file_download($uri) {
  $scheme = \Drupal::service('file_system')->uriScheme($uri);
  if ($scheme == 'swift-protected') {
    $request = Drupal::request();
    if ($request->headers->get('X-CDN-Token') != getenv('CDN_TOKEN')) {
      return -1;
    }
  }
  return NULL;
}
```

## Copyright and license.

Copyright 2016-2019 Brad Jones LLC and other module authors. GPL-2.
