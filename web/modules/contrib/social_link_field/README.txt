
Social Link Field
-----------------
-- SUMMARY --

Provides a social link field type. The module has possible to customize form
widget and form formatter.


-- INSTALLATION --

* Install as usual, see https://drupal.org/node/1897420 for further information.


-- CONFIGURATION --

* Configure user permissions in Administration » People » Permissions:
  - Configure social link field type
    Allows or denies access to so Social Link Field Settings page.

* Customize the menu settings in Administration » Configuration »
  Web services » Social Link Field Settings.
  - Attach external Font Awesome library
    Attach external Font Awesome (FA) library if you do not attach FA in your
    theme

-- USAGE --

* In entity type manages fields create a new field and select Social Links.

* Enter allowed number of values.

* Set default field values. In limited number of values, you can forbid to
  change social networks in entity create/edit form and can forbid to change
  items order.

* Set settings to form widget.

* Choose formatter and set it settings. There are 2 formatters:
  1) FontAwesome icons (Common/Square).
  2) Network name.

* Create entity.


-- CUSTOMIZATION --

* To override the default FontAwesome icons just override in CSS.

* To add your custom social network, create your custom module and in path
  src/Plugin/SocialLinkField/Platform create empty php class with annotation,
  like this:

      /**
       * Provides 'PLATFORM NAME' platform.
       *
       * @SocialLinkFieldPlatform(
       *   id = "PLATFORM_ID",
       *   name = @Translation("PLATFORM NAME"),
       *   icon = "FONT_AWESOME_ICON_CLASS",
       *   iconSquare = "FONT_AWESOME_SQUARE_ICON_CLASS",
       *   urlPrefix = "PLATFORM_URL_PREFIX",
       * )
       */
      class CLASS_NAME extends PlatformBase {}


-- CONTACT --

Current maintainers:
* Roman Hryshkanych (romixua) - http://drupal.org/user/3516201
