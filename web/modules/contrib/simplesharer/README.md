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

Social SimpleSharer is a module that creates a page sharing block.

Also, it's important to note that unlike most other social modules, this one
does not use any Javascript which is increasingly important due to more
blocking at the browser level.

IF this module doesn't meet your needs, you should check out the
"ShareThis" Module
https://www.drupal.org/project/sharethis

That module has more sharing options, and due to its usage of Javascript can
dynamically show click counters.

For a full description of this module, visit the project page:
https://www.drupal.org/sandbox/elliotc/

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/

##REQUIREMENTS##

Right now, this module really works best with an icon library. The two supported
are FontAwesome and

- Foundation Icon Fonts 3 http://zurb.com/playground/foundation-icon-fonts-3
- Font Awesome http://fortawesome.github.com/Font-Awesome/

Simply download either icon set and place them in 'libraries' directory
in your Drupal 8 root directory. Make certain that Font Awesome is
called 'fontawesome' and Foundation is called 'foundation'

##RECOMMENDED MODULES##

Currently, no other modules are recommended.

##INSTALLATION##

Install as you would normally install a contributed Drupal module. See:

   https://drupal.org/documentation/install/modules-themes/modules-7
   for Drupal 7 further information.

   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8
   for Drupal 8 further information

##CONFIGURATION##

The module is configurable from the Blocks Admin interface. From there, you
can add the block to a region. Within each of the blocks, you can
choose which of Facebook, Twitter, Tumblr, Pinterest, LinkedIN, or E-mail you
want users to be able to share as well as the style and icon set.

##TROUBLESHOOTING##

If things look wrong, make certain to provide some styles in your theme or use
the built-in styles. The class names should be specific enough, but if there
is a conflict with something else, please let me know!

##FAQ##

Q: Why would I use this instead of another share module?
A: It's simpler, and it doesn't require any Javascript - especially
   oft-blocked 3rd party Javascript. There is also a user-privacy aspect. If
   you aren't collecting user information, there's no reason for you to use
   modules that do either. If you aren't afraid of functionality being
   diminished if 3rd party Javascript is blocked, then there are lots of great
   choices.

Q: Why did you choose the share options that are available?
A: Those are the ones I needed for the current project. If you have others,
   please make some recommendations to me! Thanks!

##MAINTAINERS##

Current maintainers:

- Elliot Christenson (elliotc) - https://www.drupal.org/u/elliotc
