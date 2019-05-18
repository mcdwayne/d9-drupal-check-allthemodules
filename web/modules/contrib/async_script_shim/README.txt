Rewrite asynchronous script tags to inline, old-browser-compatible scripts.

Rewrites all scripts in the page with an "async" attribute to an inline JavaScript loading the script asynchronously in an old browser
compatible way.

This module is mostly a proof of concept and is Drupal 8 only. A Drupal 7 version depends on [#1140356](http://drupal.org/node/1140356).

## Detailed description

Adding scripts with `drupal_add_js()` you can specify that the script should be loaded asynchronously like this:

    drupal_add_js(drupal_get_path('module', 'my-module') . '/my-module.js', array(
      'type' => 'file',
      'async' => TRUE,
    ));

    drupal_add_js('http://example.com/script.js', array(
      'type' => 'external',
      'async' => TRUE,
    ));

which will result in the following rendered HTML5:

    <script type="text/javascript" src="http://my-site/sites/all/modules/my-module/my-module.js?m5ir3t" async="async"></script>
    <script type="text/javascript" src="http://example.com/script.js" async="async"></script>

Unfortunately that will not work in older legacy browsers.

Instead there is a another approach that will load the script asynchronously:

    (function() {
      var s = document.createElement('script');
      s.type = 'text/javascript';
      s.async = true;
      s.src = 'http://example.com/script.js';
      var d = document.getElementsByTagName('script')[0];
      d.parentNode.insertBefore(s, d);
    })();

This module will take care of rewriting your script tags automatically you can just go on and specify "async" to `drupal_add_js()` in a nice semantically way.

## Which browsers *do* support "async"?

According to http://caniuse.com/script-async the "async" attribute is supported from these browser versions:

 * Internet Explorer 10 (not yet released)
 * Firefox 3.6
 * Chrome 8.0
 * Safari 5.0
 * Opera (status unknown)

So once all commonly used browsers support the "async" attribute you can happily disable, uninstall and forget about this module.

## Caveats

This module will work with aggregated, asynchronous JavaScripts once Drupal 8 supports aggregated, asynchronous JavaScripts, see [#1587536](http://drupal.org/node/1587536).
