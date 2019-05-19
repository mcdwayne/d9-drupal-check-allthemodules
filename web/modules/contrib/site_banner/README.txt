
-- SUMMARY --

Site banner allows you to display a banner at the top and bottom of a
Drupal site. It is displayed at a fixed position so content is viewed
within these top and bottom banners much like the administrator's
toolbar. It also adds the banners to printed copies of Drupal site
content.

It's a useful module for:
- internal sites where you hold sensitive information, and want to
make sure that your users are aware of the content's sensitivity.
It would also help if you need that information displayed on the
printed version of the site.
- site admins deploying a test site and want to let all visitors know
that the site is for testing.

It also has integration with the Context module, so you can configure
contexts to alter the banner status, background and text color and
text depending on the context displayed. I found this is particularly
useful if you combine it with the "Taxonomy" condition: so your
banner text changes along with the content being displayed. For
example, if you have sensitive information on a particular Drupal
node that your visitors need to know is sensitive.

This module is compatible with Internet Explorer 8 - where the top
and bottom banners are rendered correctly on screen and in print.
Firefox 22 renders these banners well, except you need to change the
page margins in the print version. Chromium 25 renders the top and
bottom banners on the first page, but  it does obscure some text on
the first page footer area. I haven't found a way to change the
print settings in Chromium. All testing was done on Windows XP
or Windows 7.

I have not been able to test this module in Opera, Safari or other
versions of browsers or operating systems, but I welcome
contributions from anyone!

********************************************************************
The Drupal 8 version is a rather hacky version, just so
there is a compatible version of the Site Baner module available
********************************************************************


-- REQUIREMENTS --

* No formal prerequisites

-- INSTALLATION --

* Install as usual, see http://drupal.org/node/70151 for further
  information.

-- CONFIGURATION --

* Edit the variable "$banner_text = "Blah dee blah blah";" in
  site_banner.module, replacing the text "Blah dee blah blah" with
  your required text.

* Edit the following text in the css/site_banner_screen.css file
  with your required background and foreground colours.

         background-color:#ff0000;
         color:#ffffff;

-- REFERENCES --

The CSS code used to create the top and bottom banners was based on
the article from Dynamic Drive CSS library:

http://www.dynamicdrive.com/style/layouts/category/C11

-- CONTACT --

Current maintainers:
* Anthony Joseph (ajosephau - https://drupal.org/user/2543514)
