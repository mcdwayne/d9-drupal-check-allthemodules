NO REFERRER
===========

The rel="noreferrer" link type[1] indicates that no referrer information is to
be leaked when following the link. It enhances privacy by allowing users to
avoid leaking referrer information when they click on (or prefetch) links to
external sites. It also enhances security by preventing the linked page from
gaining access to the linking page via the window.opener object.

This module adds the rel="noreferrer" link type to all external links generated
by code. It also provides a filter which, if enabled for a text format, adds the
rel="noreferrer" link type to all external links in user-generated content.

Whitelisted domains can be defined, to which referrer URLs will be sent. If
desired, you may also publish your list of whitelisted domains as a JSON file,
to which other sites can subscribe. Alternately, you may subscribe to another
site's list of whitelisted domains.

This module also adds the rel="noopener" link type to all links with a target.
You can toggle both link types on and off if, for example, you need only the
security protections of rel="noopener" without the privacy protections of
rel="noreferrer".

If metatag module[2] is installed, you may also want to enable the meta referrer
element[3] to set a referrer policy[4] for the page.

[1] https://html.spec.whatwg.org/multipage/semantics.html#link-type-noreferrer
[2] https://www.drupal.org/project/metatag
[3] https://www.w3.org/TR/referrer-policy/#referrer-policy-delivery-meta
[4] https://www.w3.org/TR/referrer-policy/#referrer-policies
