INTRODUCTION
------------

# User Menu Avatar

## Description
This module replaces the target text of a Drupal menu link item with the user_picture and/or username of the current user.
Users may set the link title as the user menu avatar target.
There are a few link instances not covered, but otherwise, users may set the avatar to any menu link.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/user_menu_avatar

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/node/add/project-issue/user_menu_avatar


REQUIREMENTS
------------

This module requires the following modules:

 * User


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
   
1. **Install** this module
2. Adjust
  - shape
  - size
  - custom image field name
  - custom name field name

Settings Page: `/admin/config/tinsel-suite/user-menu-avatar`


AVAILABLE OPTIONS
------------

1. User Menu Avatar Shape
  - Circle
  - Square
2. User Menu Avatar Size
  - Size in pixels (applies to width and height)
3. Option to set custom image field_name for the avatar image
4. Option to set custom name field_name for the user name
5. Options to show both the image and name

CONFIGURATION
------------

 * Customize the menu settings in Administration » Configuration » 
 Tinsel Suite » User Menu Avatar Settings.

## Provides options for
1. User Menu Avatar Shape
   - Circle
   - Square
2. User Menu Avatar Size
   - Size in **pixels** (applies to width and height)
3. Set custom image field name for image
4. Show both the image and name

* Default settings: 50px, Circle
* This module uses CSS, so please make sure to clear cache after install.
* This module only replaces the link title and does not interfere with any core functionality.

EXAMPLE MARKUP - AVATAR AND NAME
-----------

The link title will be replaced as such:

`<a href="/user" data-drupal-link-system-path="user">My account</a>`

Becomes:

`
<a href="/user" data-drupal-link-system-path="user">
  <span class="user-menu-avatar circle" style="background-image: url(path/to/image.png); width: 50px; height: 50px;"></span>
  <span class="show-user-name">Name</span>
</a>
`

This changes only the link title and leaves the link functionality in place.

MODULE CSS
-----------

This module uses minimal CSS, just enough to make it work out of the box in most use cases.

`
span.user-menu-avatar {
  position: relative;
  display: inline-block;
  vertical-align: middle;
  padding: 0;
  margin: 0;
  background-repeat: no-repeat;
  background-clip: border-box;
  background-origin: padding-box;
  background-size: cover;
  background-position: center;
}

span.user-menu-avatar.circle {
  border-radius: 50%;
}

span.show-user-name {
  display: inline-block;
  vertical-align: middle;
  font-size: 100%;
  margin-left: 10px;
}

span.show-user-name-only {
  font-size: 100%;
}
`

MAINTAINERS
-----------

Current maintainers:
 * Preston Schmidt - https://www.drupal.org/user/3594865
