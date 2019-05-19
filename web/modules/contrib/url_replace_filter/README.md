
# URL Replace Filter Drupal module.


## Description

The URL Replace Filter module allows administrators to replace the base URL in
`<img>` and `<a>` elements.

Users tend to create links and images in their content with absolute URLs. This
can be a problem if the site moves to another domain (perhaps between
development and production sites) or is behind a proxy, with a different address
for authenticated users.

### Some replacement examples:

* Link
  * Before: `<a href="http://example.com:8080/somepath">Some link</a>`
  * After: `<a href="/somepath">Some link</a>`
* Image
  * Before: `<img src="http://a.example.com/files/img.jpg" alt="Some image" />`
  * After: `<img src="/files/img.jpg" alt="Some image" />`

You can setup such replacements in the URL Replace Filter settings as follow:

* Link
  * Original: `http://example.com:8080/`
  * Replacement: `%baseurl/`
* Image
  * Original: `http://dev.example.com/`
  * Replacement: `%baseurl/`

`%baseurl` is a token for your site's base URL. The above examples assume a site
located in the domain's root directory (in which case `%baseurl` is actually
empty).

Like any Drupal filter, the original user-entered content is not altered. The
filter is only applied (and its result cached) when the node is viewed.


## Installation

1. Enable the module

2. Go to the `Configuration` > `Content authoring` > `Text formats and editors` 
   page, and click configure next to the text format that shall replace URLs.

3. Enable the URL Replace Filter in the text format's configuration page, and
   save the configuration.

4. Click the `URL Replace filter` vertical tab at the bottom of the page. 
   In the URL Replace Filter box, enter original and replacement URLs in the 
   appropriate fields and save the configuration. More empty replacement fields 
   will automatically be added after saving, in case you need more fields than 
   provided by default.
