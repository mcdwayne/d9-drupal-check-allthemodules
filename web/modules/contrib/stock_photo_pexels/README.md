## About Media Pexels [WIP]

Adds [Pexels.com](http://pexels.com) as a supported media source. This module 
will give you a local copy of an image from Pexels, allowing you to use it as 
any other local media.

> Note: This is a work in progress.

### Roadmap

The plan is to get this up on drupal.org as a contributed module. 

- [ ] Add a Pexels Media source
- [ ] Fetch a Pexels image via a provided ID
- [ ] Add Media Browser Pexels.com search implementation

### Pexels API

This module uses the Pexels API via the unofficial PHP API wrapper, 
[Pexels API Client](https://github.com/glooby/pexels). It will fetch the image 
and all the metadata. 

### Instructions

After enabling the module, you can create a new Media Type choosing "Pexels"
on the source dropdown.

A source field will be automatically created and configured on the Media Type if
this is the first Pexels type on the site. If you need to have additional
types, you can choose to reuse an existing field as source, or create one field
per type. Source fields for the Pexels Media Type need to be plain text or
link fields.

Please refer to the Media documentation for more instructions on how to work
with Media Types.

---

Maintainer(s):
 - James Candan ([@jcandan](http://drupal.org/user/1831444))