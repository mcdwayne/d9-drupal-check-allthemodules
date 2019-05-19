# Social Hub

This module is designed to serve as hub for social follow/share features.  It leverages the power of both the plugin and configuration APIs offering a way to site builders and developers to integrate social networks into a Drupal site in form of *Follow us* block, share links or even followers o likes counters.

## How is it works?

The module is shipped with default plugin implementations and some pre-baked configurations out-of-the-box for most common integration cases.

### Integrations (plugins)

 * **Follow**: Allow to configure a platform to show a *follow* profile/account link. The link target is fully configurable and supports the use of tokens. 
 * **Share**: Allow to configure a platform to send content to be shared. Currently supports two sharing modes: by URL (default) and using embed code (typically an iframe). Almost all settings support tokens. Also has the ability to include a JS script if required; this script can be included inline (ol' script tag), using a pre-defined JS library (those in *.libraries.yml) or by linking a remote script (internally will be attached as a dynamic library).

### Platforms (social media)

 * **Facebook** (follow, share)
 * **Twitter** (follow, share)
 * **Pinterest** (follow, share)
 * **Whatsapp** (share)
 * **Email** (share)
 * **Copy link** (share)

## How to use it

To easy output these platforms with their integrations this module provides both a block and an extra field.  The extra field is provided by integrating with EFS module which provides an API to create configurable extra fields per display implemented as plugins. More information about EFS module can be found [here][efs].

## What's next?

 * Add tests.
 * Add deep-linking capabilities to links.
 * Allow platforms/integrations to be set per content instance using a custom field type.
 * Put some love to the tiny UI elements.
 * Allow icons set to be configurable through UI.
 * Allow pre-baked configs to be installed on-demand.
 * Add more pre-baked configs.
 * Views support.
 * Do more testing.
 * Add some translations.
 * Write the docs.

## How to contribute

The module development is taking place in [GitHub][repo_url] but the module's [issue tracker][issues_url] is used to coordinate the effort. We like to encourage you to check these [guidelines][issue_guideline] on how to create an issue.

## Similar modules

 * [Better Social Sharing Buttons][bssb], this module offers a block/template oriented approach for a predefined list of social media with only share capabilities.
 * [Share Everywhere][se], this module is pretty similar to `Better Social Sharing Buttons` but allows icons to be set through configuration, has Views support and also adds more social medias to the list.
 * [Simple Sharer][ss], this module is quite similar than the above but delegates all settings to the block once is placed.
 * [Social Media][sm], this module offers a block/field approach leveraging the Field API with a predefined list of social media configurations.
 * [Social Media Links][sml], this module offers the best approach in terms of extensibility since it relies on plugins for both the social media and the icons set, but implements only *follow* feature.

[efs]: https://www.drupal.org/project/efs
[repo_url]: https://github.com/d70rr3s/social_hub
[issues_url]: https://www.drupal.org/project/issues/social_hub?categories=All
[issue_guideline]: https://www.drupal.org/issue-summaries
[bssb]: https://www.drupal.org/project/better_social_sharing_buttons
[se]: https://www.drupal.org/project/share_everywhere
[ss]: https://www.drupal.org/project/simplesharer
[sm]: https://www.drupal.org/project/social_media
[sml]: https://www.drupal.org/project/social_media_links
