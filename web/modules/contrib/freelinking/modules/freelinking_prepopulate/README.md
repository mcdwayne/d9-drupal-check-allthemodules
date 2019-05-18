# Freelinking Prepopulate

## CONTENTS OF THIS FILE

* Introduction
* Requirements
* Recommended modules
* Installation
* Maintainers


## INTRODUCTION

This module create a prepopulated link to a create node. It is used primarily as
a failover for the **Node title** plugin.  When this module is enabled, the failure
option “Add a link to create content” is added to the failover option for the
**Node title** plugin.

The following items will be inherited from the the parent node and used to
prepopulate the new node:

* Title (inherited from the *target* of the freelink markup).
* Book outline (requires the **Book** module to be enabled).

For example, the following freelink: `[[nodetitle:Does not exist]]`,  produces:

    <span class="freelink freelink-createnode notfound freelink-internal">
      <a title="node/add/article" href="/node/add/article?edit%5Btitle%5D=Does%20not%20exist&parent=836">Does not exist</a>
    </span>

Here, the `parent=836` identifies the book the new node shall be part of.


## REQUIREMENTS

Requires [Prepopulate][1].


## RECOMMENDED MODULES

* Book:<br>
  When the core Book module is enabled, create node links will be prepopulated
  as being part of the same book as the parent node.

* [Markdown filter][2]:<br>
  When this module is enabled, display of the project's `README.md` will be
  rendered with markdown when you visit `/admin/help/freelinking_prepopulate`.


## INSTALLATION

1. Install as you would normally install a contributed Drupal module. See:
   [Installing modules][3] for further information.
2. To configure the module, navigate to the text format with freelinking enabled
   at Configuration » Content authoring » Text Formats. Then enable the
   **Prepopulate** plugin and/or allow the “Add a link to create content” option
   in the **Node title** plugin.


## MAINTAINERS

* [grayside](https://www.drupal.org/u/grayside)
* [juampy](https://www.drupal.org/u/juampy)
* [gisle](https://www.drupal.org/u/gisle) (current maintainer, Drupal 7)
* [mradcliffe](https://www.drupal.org/u/mradcliffe) (current maintainer, Drupal 8)

Any help with development (patches, reviews, comments) are welcome.

[1]: https://www.drupal.org/project/prepopulate
[2]: https://www.drupal.org/project/markdown
[3]: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
