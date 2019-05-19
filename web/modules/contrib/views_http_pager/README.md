# Views HTTP Pager

This module adds HTTP Link headers for the "pagers" of a Views Rest Export.

Simply enable it and you will see Link headers along the lines of this:

```
Link: <http://www.example.com/my/rest/export?page=1>; rel="self", <http://www.example.com/my/rest/export?page=0>; rel="first",
<http://www.example.com/my/rest/export?page=2>; rel="next",
<http://www.example.com/my/rest/export?page=2>; rel="last"
```

This can be used to facilitate client-side paging of your Views-based REST
resource.

## URLs

* [Homepage](https://www.drupal.org/project/views_http_pager)
* [Issues](https://www.drupal.org/project/issues/views_http_pager)
* [Drupal.org security reporting procedures](https://www.drupal.org/node/101494)

## Maintainers

Adam Ross a.k.a. Grayside

## Contributors

The most significant piece of this code came from the hard work in
[Issue #2100637: Add special handling for collections in REST](https://www.drupal.org/node/2100637)

## Compatibility

To use this module with Drupal 8.1.x, you will need to apply the patch from
[Issue #2779807: Bring RestExport::buildResponse into line with Feed::buildResponse](https://www.drupal.org/node/2779807)

Otherwise this should work fine with Drupal >= 8.2.

## F.A.Q.

### Will there be a Drupal 7 backport?

If someone else wants to create and maintain one.

### I don't see settings for this in my REST Export display settings.

There is not currently any specific support for REST export pagers in the Views
UI or architecture. This module adds HTTP metadata that client integrations can
use.

### Should I use this module?

This module is very lightweight and very simply improves the API around your
list resources. You may not have an immediate need, but turn it on, it may
really help one day.

Eventually core may have this built-in:
[Issue #2803413: Add HTTP Link pager to Rest export views](https://www.drupal.org/node/2803413)

### Would I use this as a dependency of other modules?

Probably not. But it's a good bit of documentation on adding HTTP headers to
your Rest Export displays.
