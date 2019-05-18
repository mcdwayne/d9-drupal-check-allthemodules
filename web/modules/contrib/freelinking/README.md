# Freelinking

## CONTENTS OF THIS FILE

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers

## INTRODUCTION

Freelinking implements a filter for the easier creation of HTML links to other
pages in the site or external sites with a wiki style format such as
`[[indicator:identifier]]`.

For example: `[[nodetitle:Page One]]` becomes:

    <a href="/node/1" title="Click to view a local node."
    class="freelink freelink-nodetitle freelink-internal">Page One</a>

Note that there must be no space after the colon after the indicator.

A number of ready-to-use filters are included in the Freelinking framework
(e.g. Nodetitle. Nid, User, Google search, Drupal search, Drupal projects,
 Wikipedia, etc.).

There is a framework that allow developers to add new plugins. See the online
documentation “Implementing a New Freelinking Plugin”[1] for instructions
regarding how to add new plugin.

## INSTALLATION

1. Install as you would normally install a contributed drupal module.
   See: Installing modules[2] for further information.

## CONFIGURATION

Freelinking now allows you to configure each text format in an unique way

1. To configure the module, navigate to the text format you want to enable
   Freelinking on at Administration » Configuration » Text Formats, and enable
   Freelinking plugin.
2. If you plan to use freelinking for External links, deactivate the
   “Convert URLs into links” filter for the same text formats.
3. Enable one or more freelinking plugins for this text format such as “Node” or
   “Node Title”. Note: Builtin plugins such as redacted are always enabled.

The Freelinking filter format provides the following global options:

* Default plugin:
  * Set the default plugin to use when no indicator is specified. The indicator
    is the first argument of within the freelinking markup, which follows “[[”.
* Ignore Unkwon Plugin Indicators (UPI):
  * Choose whether or not to ignore markup that is invalid or not enabled, or 
    display a themable error message.

The rest of the configuration pages are for plugin-specific options.

If you change the configiration, remember to press Save configuration to make
the changes effective.

## MAINTAINERS

* [eafarris](https://www.drupal.org/user/812) (original creator)
* [grayside](https://www.drupal.org/u/grayside)
* [juampy](https://www.drupal.org/u/juampy)
* [gisle](https://www.drupal.org/u/gisle) (current maintainer)
* [mradcliffe](https://www.drupal.org/u/mradcliffe) (current maintainer)

Any help with development (patches, reviews, comments) are welcome.

[1]: https://www.drupal.org/docs/8/modules/freelinking/implementing-a-new-freelinking-plugin
[2]: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
