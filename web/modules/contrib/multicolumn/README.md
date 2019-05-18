## SUMMARY

The Multicolumn module provides a text filter that makes it easy to format a
list into several columns.  For example, if you enter

```
        <multicolumn cols="3" type="ol">
        one
        two
        three
        four
        five
        six
        seven
        </multicolumn>
```

then it will be displayed as

```
        1. one                  4. four                 6. six
        2. two                  5. five                 7. seven
        3. three
```

You can also set the type to "ul" (the default) or "plain".

For a full description of the module, visit the project page:
  https://drupal.org/sandbox/benji/1181988

To submit bug reports and feature suggestions, or to track changes:
  https://drupal.org/project/issues/1181988


## REQUIREMENTS

None.


## INSTALLATION

* Install as usual, see
        https://drupal.org/documentation/install/modules-themes/modules-8
  for further information.

* Enable the filter for one or more text formats at
        http://example.com/admin/config/content/formats
  (replacing `example.com` with your site name).  Follow the "configure" link for
  the format you want to use.

### OTHER FILTERS

* If you use the HTML filter after this one, make sure that it allows the tags
  that this filter produces.

* If you use the Line Break filter, do not use it before this one: it changes
  line breaks, which this filter uses to separate list items.


## INSTALLED HELP

* After installation, you can see general instructions at
        http://example.com/admin/help/multicolumn
  and (if the filter has been added to any text format) usage information at
        http://example.com/filter/tips


## CUSTOMIZATION

* You can override the markup that the filter produces by writing your own
  theme functions.  Copy one or more of the Twig templates

```
        multicolumn-list.html.twig
        multicolumn-row.html.twig
        multicolumn-comment.html.twig
```

  from multicolumn/templates/ to your theme and modify it to suit your needs.
  For example, you can replace `multicolumn-comment.html.twig` with an empty
  template if you do not want the HTML comments at the start and end of each
  multicolumn element.

## CONTACT

Current maintainer:
* Benji Fisher (benjifisher) - https://drupal.org/user/683300
