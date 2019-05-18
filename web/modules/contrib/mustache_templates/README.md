# Mustache Logic-less Templates

Integrates Mustache.php plus Mustache.js into Drupal.

Includes a generic framework to build DOM content
which can be automatically synchronized on the client-side.
Any RESTful Json endpoint can be used
for automatic content synchronization.

Using Mustache.php by
 * Copyright (c) 2010-2015 Justin Hileman

Using Mustache.js by
* Copyright (c) 2009 Chris Wanstrath (Ruby)
* Copyright (c) 2010-2014 Jan Lehnardt (JavaScript)
* Copyright (c) 2010-2015 The mustache.js community

More about Mustache: http://mustache.github.io/

## Requirements

The following libraries are required:
- Mustache.php by bobthecow v2.12.0 or newer as vendor library
  https://github.com/bobthecow/mustache.php
- Mustache.js by janl v2.3.0 or newer as Drupal Javascript library
  https://github.com/janl/mustache.js

## Recommendations

The Prepared Data module can help you to build a Json endpoint
for delivering aggregated and expensively generated data.

It's recommended to use a Composer-based workflow to install
any dependency of this module.

## How Mustache templating works in Drupal

The usage of Mustache templates is mainly defined via render arrays.
Therefore, the module provides a special render element called 'mustache'.

Mustache templates can be put besides Twig templates
into the templates directory of your theme. A file which is a Mustache template
must end with .mustache.tpl.

Example: A template for rendering social share buttons could be named
as "social-share-buttons.mustache.tpl" and put into yourtheme/templates/.
To render the Mustache template, a render array can be created as follows:

```php
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $data,
];
```

Note that `#template` does not contain the .mustache.tpl file ending.
See also section "Render array examples" below to find out
how to create render arrays for Mustache templates.

In case a module wants to define a Mustache template, it can do so by
implementing `hook_mustache_templates()`. With this hook, you can also
define default values for building the render array. All Mustache template hooks
are documented in the mustache.api.php file of this module.

You don't enjoy writing render arrays? Try out and have a look at the section
"Using the helper class for rendering Mustache templates" below.

## Render array examples

You can either build render arrays directly, or build them with the helper
class `MustacheRenderTemplate`.

Using the helper class is preferable, because it's covered by an automated
unit test, and since it's providing a more stable and convenient API,
it will support you on automatically covering upcoming changes
regards building the render array.

```php
// Example 1:
// Renders the template (only) via Mustache.php.
// #data can either be an array or a url pointing to the Json data source.
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $data,
];
// Equivalent using the helper class:
$render = $render = MustacheRenderTemplate::build('social_share_buttons')
->usingData($data)
->toRenderArray();

// Example 2:
// With #select you can pass a certain subset of nested data to the template.
$nested = ['foo' => ['bar' => ['baz' => 'qux']]];
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $nested,
  '#select' => ['foo', 'bar'], // Would pass ['baz' => 'qux']
];

// Example 3:
// Renders the template via Mustache.php,
// and refreshes the content via Mustache.js one time per second.
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $url,
  '#sync' => [
    'period' => 1000, // Milliseconds.
    'limit' => -1, // Unlimited number of refreshes.
  ],
];

// Example 4:
// Renders a placeholder, which will be replaced by the given
// template via Mustache.js using Json data supplied by $url.
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $url,
  '#placeholder' => ['#markup' => '<span>Loading...</span>'],
  '#sync' => [
    'wrapper_tag' => 'span', // Set wrapper tag to be a span (default is div).
  ],
];

// Example 5:
// Renders the template via Mustache.php using $data, and synchronizes
// the content one time via Mustache.js using Json data supplied by $url.
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $data,
  '#sync' => [
    'data' => $url,
  ],
];

// Example 6:
// Just like example 5, but this time the template would only be synchronized,
// when a click event has been triggered on any element with a .button CSS class.
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $data,
  '#sync' => [
    'data' => $url,
    'trigger' => [['.button', 'click', 3]], // Would run up to 3 times. -1 would be unlimited.
  ],
];
// Equivalent using the helper class:
$render = MustacheRenderTemplate::build('social_share_buttons')
->usingData($data)
->withClientSynchronization()
  ->usingDataFromUrl($url)
  ->startsWhenElementWasTriggered('.button')
    ->atEvent('click')
    ->upToNTimes(3)
->toRenderArray();
```

## Insertion of synchronized content

By default, the synchronization replaces previously generated content.
You can change this behavior by setting the `adjacent` option, which defines
how rendered content should be inserted:

```php
$render = [
  '#type' => 'mustache',
  '#template' => 'my_item_list',
  '#data' => $data,
  '#sync' => [
    'adjacent' => 'beforeend',
  ],
];
// Equivalent using the helper class:
$render = MustacheRenderTemplate::build('my_item_list')
->usingData($data)
->withClientSynchronization()
  ->usingDataFromUrl($url)
  ->insertsAt('beforeend')
->toRenderArray();
```

The position can be either `beforebegin`, `afterbegin`, `beforeend` or
`afterend`. Have a look at the Javascript function `Element.insertAdjacentHTML`
for more information about inserting HTML with the position parameter.

## Script execution

By default - due to possible security implications - scripts inside synchronized
DOM content will not be executed automatically. However, if you want to
let the synchronization process also execute possible included scripts,
you can do so by setting the `eval` parameter to TRUE:

```php
$render = [
  '#type' => 'mustache',
  '#template' => 'social_share_buttons',
  '#data' => $data,
  '#sync' => [
    'data' => $url,
    'eval' => TRUE,
  ],
];
// Equivalent using the helper class:
$render = MustacheRenderTemplate::build('social_share_buttons')
->usingData($data)
->withClientSynchronization()
  ->usingDataFromUrl($url)
  ->executesInnerScripts(TRUE)
->toRenderArray();
```

The synchronization framework uses jQuery for script execution.
If you don't enable script execution at all, the synchronization
framework will not depend on jQuery and will not load it.

## Auto incrementing

The synchronization framework provides the ability to auto increment on
a certain url query param or data key:

```php
$render = MustacheRenderTemplate::build('my_paged_list')
->withClientSynchronization()
  ->usingDataFromUrl($url)
  ->increments()
->toRenderArray();
```

The `increments()` method enables you to further define the auto increment,
including the step size to increment, on which query param to run incrementing,
how many times it should increment, and whether it should loop or not.

## Javascript events

The following events are provided at the specified synchronization targets:

- `mustacheSyncBegin`: Triggered when the synchronization for
  a specific item has just been started.
- `mustacheSyncFinish`: Triggered when the synchronization for
  a specific item has just been finished.

## Internal module development notices

The minified sync.js was generated with the help of uglify-js (https://www.npmjs.com/package/uglify-js)
by the command `uglifyjs --comments --ie8 -o sync.min.js sync.js`
