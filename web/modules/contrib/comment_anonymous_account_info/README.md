Provides anonymous user info on comments.

Copyright (C) 2018 Daniel Phin (@dpi)

When a comment is authored by a user, the users 'compact' view mode will be
rendered. By default this view mode includes the user picture/avatar.

For comments posted anonymously, there is no built-incapability to show info
related to the account, because these anonymous accounts are not user entities.

This module provides a way to show user info for anonymous users. You have the
option of using a comment display mode in combination with a Twig template.

# License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

# Installation

 1. Install module.
 
## Create view mode

 1. Enable _Field UI_ if not enabled.
 2. Go to Structure » Display modes » View modes
 3. Click _Add new Comment view mode_ and create a new view mode.
 4. Name your new view mode, take note of the generated **machine name**.

## Configure comment type

 1. Go to Structure » Comment types
 2. Click _Manage display_ next to your comment type.
 3. Go to _Custom display settings_, click the check box next to your new view
    mode, then click _Save_.
 4. Click the secondary tab containing the label of your previously created view
    mode.
 5. Configure the fields you wish to show, in some cases you may want to drag
    **all** fields into the _Disabled_ section. Then click _Save_.
 6. Click the _Edit_ tab (for your comment type).
 7. In the _Anonymous comment account info_ section, select your new view mode
    from the drop down options. Click _Save_.

## Copy Twig templates

 1. Copy the Twig files from the `dist/` directory to the `templates/` directory in
    your theme.
    **If you are not using a custom theme, you will need to create your sub-
    theme**. Such as if you are using a core or contrib theme, you must create
    your own sub-theme which inherits your original themes resources. See 
    [Creating a Drupal 8 sub-theme, or sub-theme of sub-theme][1] for more
    information.
 2. Rename the `comment--DISPLAYID.html.twig` file so the _DISPLAYID_ portion
    contains the **machine name** of your new view mode.

**Note:** modifying the contents of the Twig template is not required.

# Customisation

Making use of the new view mode with fields is completely optional. The strategy
of creating a new view mode is useful as it makes for sensible theme suggestions
/Twig overrides.

## Twig variables

A list of available Twig variables can be found in `comment.html.twig` within the 
_comment_ module.
To make extra variables available to the comment template, create an
implementation of `hook_preprocess_comment()` in your theme.

# Related issues

## Anonymous users cannot comment

The _Post comments_ permission must be granted to _Anonymous_ role.

To collect email addresses of anonymous users, edit the comment field attached to your
entity type (usually node). For example: 

 1. Go to Structure » Content types » Node type » Manage fields.
 2. Click _Edit_ on your comment field.
 3. Configure options in _Anonymous commenting_ section.

# Links

[1]: https://www.drupal.org/docs/8/theming-drupal-8/creating-a-drupal-8-sub-theme-or-sub-theme-of-sub-theme
