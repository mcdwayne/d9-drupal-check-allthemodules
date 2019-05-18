# Resource Hints

Configure resource hints for better user agent performance

https://www.w3.org/TR/resource-hints/

## Instructions

Unpack in the *modules* folder (currently in the root of your Drupal 8 installation) and enable in `/admin/modules`.

Then, visit `/admin/config/development/resource-hints` and enter your own set of configurations for the various resource hint types.

## Contributing

Please follow the standards as explained in the Examples for Developers module:

http://cgit.drupalcode.org/examples/tree/STANDARDS.md

### PHPCS using Drupal standard

```shell
# From drupal root - please adjust to your environment
$ vendor/bin/phpcs -p -s --standard=Drupal modules
..

Time: 97ms; Memory: 8Mb
```

### Tests

```shell
# From drupal root - please adjust to your environment
$ php core/scripts/run-tests.sh --url http://127.0.0.1:8888 --color --module resource_hints

Drupal test run
---------------

Tests to be run:
  - Drupal\Tests\resource_hints\Functional\ResourceHintsOutputTest

Test run started:
  Sunday, January 15, 2017 - 14:46

Test summary
------------

Drupal\Tests\resource_hints\Functional\ResourceHintsOutputTe   0 passes             1 exceptions             
FATAL Drupal\Tests\resource_hints\Functional\ResourceHintsOutputTest: test runner returned a non-zero error code (2).
Drupal\Tests\resource_hints\Functional\ResourceHintsOutputTe   0 passes   1 fails                            

Test run duration: 7 sec
```

## Helpful resources

https://www.w3.org/TR/resource-hints/
https://www.igvita.com/2015/08/17/eliminating-roundtrips-with-preconnect/
https://css-tricks.com/prefetching-preloading-prebrowsing/
https://medium.com/@luisvieira_gmr/html5-prefetch-1e54f6dda15d#.liri85j7v
https://developer.mozilla.org/en-US/docs/Web/HTTP/Link_prefetching_FAQ
https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types
https://www.w3.org/TR/html4/types.html#type-links
