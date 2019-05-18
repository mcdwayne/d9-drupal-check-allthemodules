# Parallax Background

This a simple module that allows to set a vertical [Parallax effect](https://ianlunn.co.uk/plugins/jquery-parallax/) on the background of any element on the DOM.

### Installation

- Download [jquery.parallax](https://ianlunn.co.uk/plugins/jquery-parallax/scripts/jquery.parallax-1.1.3.js) library to **/libraries/jquery.parallax/** folder
- Download [jquery.localScroll](https://github.com/flesler/jquery.localScroll/releases/tag/1.4.0) library and unzip files to **/libraries/jquery.localScroll/** folder
- Download [jquery.scrollTo](https://github.com/flesler/jquery.scrollTo/releases/tag/2.1.2) library and unzip files to **/libraries/jquery.scrollTo/** folder
- Finally, install **parallax_bg** module

**Downloaded libraries should look like this:**

 - /libraries/jquery.parallax/jquery.parallax.js
 - /libraries/jquery.localScroll/jquery.localScroll.min.js
 - /libraries/jquery.scrollTo/jquery.scrollTo.min.js

### Configuration

- Goto **Admin / Structure / Parallax elements**
- Add new element you want to apply the Parallax effect using any valid jQuery selector. The selector should point to the element that holds the background, for example: `#top-content`, `body.one-page #super-banner`

### CSS Note

Depending on the position of your element, you need to apply some top-padding to align the background when entering viewport.
