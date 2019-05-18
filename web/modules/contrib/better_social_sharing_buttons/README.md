INTRODUCTION
------------
Allows you to add social sharing buttons to your website.

This module generates a block, a node field and a paragraph field so you can
choose how and where you add the social sharing buttons on your website.

Why yet another module for social sharing buttons ? Because most or even all
existing modules seem to make a lot of external calls, have tracker scripts or
even connect to ad servers. I needed a clean solution without any of that
bloatware.

All social platforms have a sharing link you can use to share content. The
buttons of this module directly open those links in a new tab without having to
call any other api, service, website or script.
This provides for a very clean and fast bloatware free solution.

If any of those services make changes to their url's then yes, this module will
need to be updated as well. Simply create an issue if you find a sharing button
no longer working and I will update as soon as possible.

There is a settings form where you can set which services you would like to use:
- Facebook
- Twitter
- Whatsapp
- Facebook Messenger (requires a Facebook App ID)
- E-mail
- Pinterest
- Linkedin
- Google+
- Digg
- Delicious
- Slashdot
- Tumblr
- Reddit
- Evernote


You can also adjust these settings for the icons:
- width
- height
- border-radius

And there are 2 icon sets to choose from:
- Colored square icons (you can adjust the border radius, even making them
rounded by setting border radius to 100%)
- Flat icons no color or background, (you can give these any color you want by
using the fill property via css)

I have rewritten this module so all svgs are minified, and the module now uses
an svg sprite so there is only a one time
resource load needed to further decrease the (already small) resource footprint
of this module.

REQUIREMENTS
------------

This module has no module requirements to work, but:
- It shares node title and url, so use it on node entities.
- If you want to add the buttons via a field, then your display mode needs a
layout in order for the field to show
- You can easily place the block in any node twig file using twig_tweak module.
See instructions below.

INSTALLATION
-----------
- require the repository:
```
composer require drupal/better_social_sharing_buttons --prefer-dist
```
- enable the module:
```
drush en better_social_sharing_buttons -y
```

CONFIGURATION
--------------
- modify settings at admin/config/services/better_social_sharing_buttons/config
- place the buttons where you want using the block, node field, paragraph field
or directly in a twig
  file (see description below)

Add social sharing buttons via twig (Twig Tweak module v1.9 or lower)
---
If you use a version of Twig Tweak below 2.0 (like 1.9) then you cannot print a
block that is not instantiated. The block must be enabled somewhere in
structure/block.

You can for example create a region 'hidden' in your theme which you render
nowhere and place the block in there. Once there is an instance of the block you
can place it anywhere in any twig file using:

```{{ drupal_block("bettersocialsharingbuttons") }}```

Add social sharing buttons via twig (Twig Tweak module v2.0 or higher)
---

Twig Tweak version 2.0 and above can print blocks that are not instantiated by
using the block id:

```{{ drupal_block("better_social_sharing_buttons_block") }}```


*NOTE: This module was initially meant to be used on node detail pages because
it gets the title and url for sharing from the current node.*

*But it is possible to add sharing buttons on teasers. A separate twig file was
created for this so you can include this and pass the necessaray parameters to
it (title, url, description). On your teaser twig file, you can use this as
follows:*
```
{# -- Social sharing buttons -- #}
{% set services = ['facebook', 'twitter', 'email', 'linkedin'] %}
{% include '/modules/contrib/better_social_sharing_buttons/theme/better-social-sharing-buttons.html.twig' with {
  'title': item.title,
  'url': item.url,
  'description': item.description|raw,
  'services': services
} %}
```

*As you can see, this way you can set which fields of your node contain the
necessary info and you can set the serices you want displayed.*

Add social sharing buttons via a field
---

This module also provides a field (Better Social Sharing Buttons field) for
nodes and paragraphs which you can place on any node or paragraph type via the
manage display tab. To see this field your display mode
must use a layout.

Add social sharing buttons via a block
--

In admin/block you can add a block (Better Social Sharing Buttons block)
