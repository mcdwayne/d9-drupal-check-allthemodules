#ATTRIBUTIONS README.md

##CONTENTS OF THIS FILE
  
* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Specifying attributions
* Troubleshooting
* Maintainers

## INTRODUCTION

The **Attributions** module helps maintainers of Drupal modules and
themes create an attribution block and an attribution page that can be
used to display the attribution information about third party
materials (such as code libraries, fonts, icons and images) they use
in projects. The attributions is pulled from all the enabled projects
on a Drupal site and renders them all in one place.

The attribution information is pulled from the .info-files present on
the site.

There is also an **Attributions Demo** module to demonstrate the use
of this module.

## REQUIREMENTS

None


## RECOMMENDED MODULES

* [Advanced Help][1]:<br>
  When this module is enabled, display of the project's `README.md`
  will be rendered when you visit `help/attributions/README.md`.
* [Markdown filter][2]:<br>
  When this module is enabled, display of the project's `README.md`
  will be rendered with the markdown filter.

## INSTALLATION

1. Install as you would normally install a contributed drupal
   module. See: [Installing modules][4] for further information.


## CONFIGURATION

After installing and enabling **Attributions** there will be an
attribution block named *Attributions block*, and an attribution page
when you vist `/attributions`.

There is no adminsitrative GUI.  Use *Drush* to toggle the variable
`attributions_all` to turn on and off display of attributions from all
modules and themes.


## SPECIFYING ATTRIBUTIONS

Attributions should be placed in the project's .info-file, using the
standard [.ini][5] file format.

To be recognized by this module the array of attrubition elements must
be named "attributions".  The first array index is a resource
identifier.  All elements belonging to the same resource must share
the same resource identifier.  The second array index identifies some
element.  Below is a list of valid elements and their usage:

The first five elements (`type`, `exception`, `template`, `weight` and
`hide`) are used to assign attributes to a resource.

The `type` element must always be presents.  It indicates the type of
the resource that is attributed.  Use `code` for a code library, and
`asset` for non-code (e.g. fonts, icons, images). Use `comment` if the
element is just a comment.  This element is currently only used for
statistics.

The `exception` element should always be presents, unless the element
is of type `comment`.  It is used to keep track of exception requests,
and will usually refer to the LWG issue tracker were an exception
request for the resource has been made (e.g. `#123456`).  In the
future, its value may also be set to `allowed license`, which means
the asset is made available under a license that is listed as
*explictly* allowed for third party content.  (No such list exists
yet, but maybe one day, the policy may change to make, for instance,
“MIT 2.0” an `allowed license`.)

The optional `template` element can be used to provide a template for
your own attribution text or comment instead of the default.

The optional `weight` element can be used to indicate the placement of
the resource relative to other resources. The default weight is 0, so
use a negative weight to place the resource on top.

The optional `hide` element can be used to hide the resource. If this
element is set, the resource will not be rendered.  You can use this
to hide third party materials available under terms without an
attribution requirement (e.g. <em>public domain</em> and
<em>CC zero</em>).</p>

<p>Summary of elements to assign attributes (i.e. will not be used for
substitution):</p>

- `type`: Must be set to 'code', 'asset' or 'comment'.
- `exception`: Must be set to 'own work', 'FLOSS', or link to issue.
- `template`: A string that may be used as the attribution text.
- `weight`: an integer that determines the weight of the resource.
- `hide`: set to TRUE to hide the resouce.

The remaining elements will be substituted in the template for the
attribution text.

- `title`: A string identifying the licensed work .
- `work_url`: = URL to the source of the licensed work.
- `author`: Author (person or organization) to receive attribution.
- `author_url`: URL to author's profile or home page.
- `license`: Name of license or policy the work of the work.
- `license_url`: URL to this license or policy document.
- `org_title`: A string identifying the original of the adapted work.
- `org_work_url`: URL to the original of the adapted work .
- `org_author`:  The name of the author of original that is adapted
- `org_author_url`: URL to profile or home page of author of original.
- `org_license`: Name of license giving permission to adapt the work.
- `org_license_url`: URL to this license or policy document.

The default template for the attribution text is generated from the
following construct:

    <a href="!work_url" title="Link to licensed work">!title</a>
    by
    <a href="!author_url" title="Link to author">!author</a>,
    available under
    <a href="!license_url" title="Link to license">!license</a>
    (adapted from
    <a href="!org_work_url" title="Link to original work">!org_title</a>
    by
    <a href="!org_author_url" title="Link to author of original work">!org_author</a>,
    available under
    <href="!org_license_url" title="Link to license original work">!org_license</a>).

The module will supress empty elements in the default template. If the
`licence` element is “public domain” (or a translation of this term),
the phrase “available under” will be supressed.

Here is an example of how to specify attributions:

    attributions[item_id][type] = 'asset';
    attributions[item_id][exception] = 'FLOSS'
    attributions[item_id][title] = 'Sun';
    attributions[item_id][weight] = 1;
    attributions[item_id][work_url] = 'http://pix.com/sun.jpg';
    attributions[item_id][author] = 'John Smith';
    attributions[item_id][license] = 'CC BY 2.0';
    attributions[item_id][license_url] = '';

With the default template, this will produce the following markup

    <a href="http://pix.com/sun.jpg" title="Link to licensed work">Sun</a>
    by John Smith, available under
    <a href="http://creativecommons.org/licenses/by/2.0/deed.no"
    title="Link to license">CC BY 2.0</a>

For a more extensive example, see the `attributions_demo.info`.

By default, only attributions specified by enabled modules and themes
will be rendered.  To render attributions from all modules and
themes. set the boolean variable `attributions_all` TRUE.  For
example:

    drush vset attributions_all TRUE


## TROUBLESHOOTING

No trouble reported yet.


## MAINTAINERS

* [gisle](https://www.drupal.org/u/gisle) (current maintainer)

Any help with development (patches, reviews, comments) are welcome.


[1]: https://www.drupal.org/project/advanced_help
[2]: https://www.drupal.org/project/markdown
[3]: https://www.drupal.org/project/attributions
[4]: https://drupal.org/documentation/install/modules-themes/modules-7
[5]: http://en.wikipedia.org/wiki/INI_file
