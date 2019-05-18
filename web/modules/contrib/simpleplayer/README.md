#CONTENTS OF THIS FILE#

- Introduction
- Requirements
- Recommended modules
- Installation
- Configuration
- Troubleshooting
- FAQ
- Maintainers

##INTRODUCTION##

HTML5 SimplePlayer is just a simple player to support the HTML5 Audio Tag

**and now Video Tag**

I recommend adding the Font Awesome Module in order to have the built-in styles
work properly.

This is super-limited right now because that's what I wanted. I want to
retain the ability to keep the usage, styling, and code super-simple.

This is my first actual semi-public module. Please be gentle with criticism. :)

For a full description of the module, visit the project page:
https://www.drupal.org/project/simpleplayer

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/2571105

##REQUIREMENTS##

This will not work on old browsers.

**Any Internet Explore prior to IE9 will not work properly with this.**

If you need compatibility with those, this is not the module for you.

You should use something like this:

Video 7.x-2.x
Video 8.x-1.x
https://www.drupal.org/project/video


##RECOMMENDED MODULES##

- Font Awesome Icons https://www.drupal.org/project/fontawesome

Font Awesome Requires:

- Libraries https://drupal.org/project/libraries
- Font Awesome http://fortawesome.github.com/Font-Awesome/

##INSTALLATION##

Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

##CONFIGURATION##

The module has no menu or modifiable settings. There is no configuration. When
enabled, the field SimplePlayer will be enabled on file fields. Each file
field has the ability to enable and disable
player buttons.

##TROUBLESHOOTING##

If things look wrong, make certain to provide some styles in your theme.
Obviously, since this is an HTML5 player,
This will only work on modern browsers supporting the HTML5 audio tag.

##FAQ##

Q: Why would I use this instead of another player?
A: It's simpler, and it easily allows you to create and style audio/video
players with various buttons. The other options are great if you are looking
to just drop something in-place with default styles, Flash replacement for old
browsers, etc. The downside of the other options is more complexity and more
code. I hate coding, so I want to keep this as simple as possible.

Plus, it is super-easy to style with just a few things to drop into your theme
CSS files!

##MAINTAINERS##

Current maintainers:

- Elliot Christenson (elliotc) - https://www.drupal.org/u/elliotc
