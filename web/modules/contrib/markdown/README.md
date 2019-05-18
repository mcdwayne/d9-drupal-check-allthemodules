# Markdown

This module provides Markdown integration for Drupal.

The Markdown syntax is designed to co-exist with HTML, so you can set up input
formats with both HTML and Markdown support. It is also meant to be as
human-readable as possible when left as "source".

There is current an issue open to make CommonMark the "official" [Drupal Coding
Standard].

While there are several types of PHP Markdown parsing libraries out there, this
module requires [thephpleague/commonmark] as the default/fallback parser in a
preemptive acceptance of the [Drupal Coding Standard].

This module also supports additional PHP Markdown parsers for backwards
compatibility reasons and in an effort to open up other options, should you
desire a different solution:

- [erusev/parsedown]
- [michelf/php-markdown]

## Try out a demonstration!

<https://markdown.unicorn.fail>

To see a full list of "long tips" provided by this filter, visit:

<https://markdown.unicorn.fail/filter/tips>

## Requirements

- **PHP >= 5.6.5** - This is a hard requirement due to [thephpleague/commonmark].

## Installation

> @todo Update this section.

If you are comfortable with composer that is the best way to install both PHP
Markdown and CommonMark. They will then be autoloaded just like other parts of
Drupal 8.

The old way of installation in the libraries directory is only supported for PHP
Markdown. The libraries module is then needed to load the library.

1. Download and install the [Libraries](https://www.drupal.org/project/libraries)
2. Download the PHP Markdown library from
   https://github.com/michelf/php-markdown/archive/lib.zip, unpack it and place
   it in the `libraries` directory in Drupal root folder, if it doesn't exist
   you need to create it.

Make sure the path becomes
`/libraries/php-markdown/Michelf/MarkdownExtra.inc.php`.

## Editor.md

If you are interested in a Markdown editor please check out the [Editor.md]
module for Drupal. The demonstration site for this module also uses it if
you want to take a peek!

## CommonMark Extensions

> @todo Update this section.

- **Enhanced Links** - _Built in, enabled by default_
    Extends CommonMark to provide additional enhancements when rendering links.
- **@ Autolinker** - _Built in, disabled by default_
    Automatically link commonly used references that come after an at character
    (@) without having to use the link syntax.
- **# Autolinker** - _Built in, disabled by default_
    Automatically link commonly used references that come after a hash character
    (#) without having to use the link syntax.
- **[CommonMark Attributes Extension]**
    Adds syntax to define attributes on various HTML elements inside a CommonMark
    markdown document. To install, enable the `commonmark_attributes` sub-module.
- **[CommonMark Table Extension]**
    Adds syntax to create tables in a CommonMark markdown document.  To install,
    enable the `commonmark_table` sub-module.

## Programmatic Conversion

In some cases you may need to programmatically convert CommonMark Markdown to
HTML. This is especially true with support legacy procedural/hook-based
functions. An example of how to accomplish this can be found in right here
in this module:

```php
<?php

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\markdown\Markdown;

/**
 * Implements hook_help().
 *
 * {@inheritdoc}
 */
function markdown_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'help.page.markdown':
      return Markdown::loadPath($route_name, __DIR__ . '/README.md');
  }
}
```

If you need to parse Markdown in other services, inject it as a dependency:

```php
<?php

use \Drupal\markdown\MarkdownInterface;

class MyService {

  /**
   * A MarkdownParser instance.
   *
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $markdownParser;

  /**
   * MyService constructor.
   */
  public function __construct(MarkdownInterface $markdown) {
    $this->markdownParser = $markdown->getParser();
  }

  /**
   * MyService renderer.
   */
  public function render(array $items) {
    $build = ['#theme' => 'item_list', '#items' => []];
    foreach ($items as $markdown) {
      $build['#items'][] = $this->markdownParser->parse($markdown);
    }
    return $build;
  }
}
```

Or if using it in classes where modifying the constructor may prove difficult,
use the `MarkdownTrait`:

```php
<?php

use \Drupal\markdown\Traits\MarkdownTrait;

class MyController {

  use MarkdownTrait;

  /**
   * MyService renderer.
   */
  public function render(array $items) {
    $build = ['#theme' => 'item_list', '#items' => []];
    foreach ($items as $markdown) {
      $build['#items'][] = $this->parseMarkdown($markdown);
    }
    return $build;
  }

}
```

## Twig Extensions

This module also provides the following Twig extensions for use in templates:

### Filter/Function

For simple strings or variables, you can use the `markdown` filter or function:

Filter:
```twig
{{ "# Some Markdown"|markdown }}
{{ variableContiningMarkdown|markdown }}
```

Function:
```twig
{{ markdown("# Some Markdown") }}
{{ markdown(variableContiningMarkdown) }}
```

### Tag

If you have more than a single line of Markdown, use the `markdown` tag:

```twig
{% markdown %}
  # Some Markdown

  > This is some _simple_ **markdown** content.
{% endmarkdown %}
```

### Global

For more advanced use cases, you can use the `markdown` global for direct
access to the `MarkdownInterface` instance.

Generally speaking, it is not recommended that you use this. Doing so will
bypass any existing permissions the current user may have in regards to
filters.

However, this is particularly useful if you want to specify a specific parser
to use (if you have multiple installed):

```twig
{{ markdown.getParser('parsedown').parse("# Some Markdown") }}
{{ markdown.getParser('parsedown').parse(variableContiningMarkdown) }}
```

## Notes

> @todo Update this section.

Markdown may conflict with other input filters, depending on the order
in which filters are configured to apply. If using Markdown produces
unexpected markup when configured with other filters, experimenting with
the order of those filters will likely resolve the issue.

Filters that should be run before Markdown filter includes:

- Code Filter
- GeSHI filter for code syntax highlighting

Filters that should be run after Markdown filter includes:

- Typogrify

The "Limit allowed HTML tags" filter is a special case:

For best security, ensure that it is run after the Markdown filter and
that only markup you would like to allow via HTML and/or Markdown is
configured to be allowed.

If you on the other hand want to make sure that all converted Markdown
text is preserved, run it before the Markdown filter. Note that blockquoting
with Markdown doesn't work in this case since "Limit allowed HTML tags" filter
converts the ">" in to "&gt;".

## Smartypants Support
> @todo Update this section.

This module is a continuation of the Markdown with Smartypants module.
It only includes Markdown support and it is now suggested that you use
Typogrify module if you are interested in Smartypants support.

<https://drupal.org/project/typogrify>




[CommonMark]: http://commonmark.org/
[CommonMark Attributes Extension]: https://github.com/webuni/commonmark-attributes-extension
[CommonMark Table Extension]: https://github.com/webuni/commonmark-table-extension
[Drupal Coding Standard]: https://www.drupal.org/project/coding_standards/issues/2952616
[Editor.md]: https://drupal.org/project/editor_md
[erusev/parsedown]: https://github.com/erusev/parsedown
[michelf/php-markdown]: https://github.com/michelf/php-markdown
[thephpleague/commonmark]: https://github.com/thephpleague/commonmark
[The League of Extraordinary Packages]: https://commonmark.thephpleague.com/
