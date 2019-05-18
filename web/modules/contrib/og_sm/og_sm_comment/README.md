# Organic Groups : Site Comment
This module provides comment management functionality within a Site context.


## Functionality

### Extra permissions for comment actions
The Site Comment module adds extra permission to manage comments. The following
permissions are added :
* **Delete own comments** : Allows the user to delete his/her own comments.
* **Delete all comments** : Allows the user to delete all comments.
* **Edit all comments** : Allows the user to edit all comments.
* **Override node comment settings** : Allows the user to override the comment 
  settings per node.

These permissions can be used on global level and site level.


### Manage comment settings per site content type
This module allows overriding comment behaviour per content type within a site
context.
Possible statuses are:

* **Closed**: No comments are allowed, but any past comments remain visible.
* **Hidden**: No comments are allowed, and past comments are hidden.
* **Open**: Any content of this type is open to new comments.
* **Open for anonymous users**: Allow anonymous users to comment on this content
  type. This only works when the anonymous role has the 'post comments'
  permission.


### Override site comment settings per content item
By default comment settings are set on the site level (all content items get 
the same comment setting).

This module foresees a setting on Site content item level to allow users to 
override the comment settings per content item.


### Context detection: Site Comment
This module provides context detection for site comments.

The site comment provider checks if a path starts with `comment/CID`. If so it
will load the comment's node and checks if it is a Site or a Site Content. If so
it will return the related Site nid as context.


### Pathauto integration for comments
This module provides integration to create path aliases for comment pages.

To make use of this feature the pathauto module needs to be enabled.


### TIP: Aliases for comment/CID/edit and comment/CID/delete
This module does not provide path aliases for `comment/CID/edit` and
`comment/CID/delete` paths.

Install the [Extended Path Aliases][link-path_alias_xt] module to provide this
functionality.


### Manage comments within a Site
A new Site admin page is provided by this module:
* `[site-path]/admin/comments` : Overview of all comments within the Site.



## Requirements
* Organic Groups Site Manager

## Installation
1. Enable the module.
2. Configure the alias for comments on admin/config/search/path/patterns:
   - Comment paths : `[comment:node:site-path]/...`
3. Delete and regenerate all content aliases.
4. Setup the OG context providers on admin/config/group/context:
  - Enable the "**Site Comment**" detection method.
5. Grant user roles access to edit or delete existing Site comments.
6. Grant organic group roles access to edit or delete existing Site comments.



## API

### Find the site on which this comment was made.
Find the site node that is linked to the node on which the comment was made.

```php
$site = og_sm_comment_get_site($comment);
```


## Override comment administration overview page
This module provides a comment overview pages within a site:

* group/node/[site-nid]/admin/comments
  
The view and display in use for these pages can be altered by setting the
variable containing the setting: The variable value has the following format:

* `view_name:display_name`

The variable is:

* `og_sm_comment_view_admin_overview` : The all Site comment overview page
  (default `og_sm_comment_admin_overview:default`).





[link-path_alias_xt]: https://www.drupal.org/project/path_alias_xt
