Summary
--------------------------------------------------------------------------------

The Discussions module leverages the Group module to create discussion groups.


Requirements
--------------------------------------------------------------------------------

- Group module (https://www.drupal.org/project/group)

  Group module patch for pending memberships:
  https://www.drupal.org/files/issues/request_membership-2752603-23.patch

- Views patch:

  https://www.drupal.org/files/issues/2744069-5-views-in-queries.patch


Installation
--------------------------------------------------------------------------------

Download and extract the discussions module into your site's modules directory
and install via Drupal's UI or by using Drush.

More info on installing Drupal modules: https://www.drupal.org/node/1897420


Quick Start
--------------------------------------------------------------------------------

- Install and enable the Discussions module

- Disable the view with the machine name "group_members":

  /admin/structure/views

  This view is replaced by the Discussions module.

- Go to the Content admin page for the "Public discussion" group:

  http://YOUR_SITE_URL/admin/group/types/manage/public_discussion/content

- Click the "Install" button right of the "Group Discussion (Public)" plugin

- Leaving all settings as default, click the "Install plugin" button

- Add a new Public discussion group:

  http://YOUR_SITE_URL/group/add/public_discussion

- Choose a title for the new group and click the "Save" button

- You should now see a discussion group

- Add a new discussion by clicking "Create Public" in the Group operations block


Configuration
--------------------------------------------------------------------------------

# Discussion Types

The Discussions module includes two default discussion types:
- Private
- Public

You can find discussion types and create your own at:

http://YOUR_SITE_URL/admin/structure/discussions/discussion_type

Discussion types are used as content plugins by the Group module. This allows
a discussion group to contain any number of discussion types.

# Group Types

The Discussions module includes two default group types:
- Private Discussion Group
- Public Discussion Group

You can find group types and create your own at:

http://YOUR_SITE_URL/admin/group/types

# Group Content Plugins

The Group module can use discussion types as content plugins. Content plugins
must be installed before they can be used.

To install a content plugin for the default group type
"Public Discussion Group", you would go to this URL:

http://YOUR_SITE_URL/admin/group/types/manage/public_discussion/content

Click the "Install" button next to the content plugin you want to install.

For example, installing the "Group Discussion (Public)" content plugin will
allow members of a discussion group to create discussions of the type "Public".

# Creating Discussion Groups

Create new discussion groups at this URL:

http://YOUR_SITE_URL/group/add

You'll see that you can choose from any of the existing group types. Click a
group type to create a new discussion group of that type.

# Differences Between Default Discussion Group Types

The default Private and Public discussion group types differ in the permissions
configuration. See the permissions at these URLs:

Private Discussion Group:
http://YOUR_SITE_URL/admin/group/types/manage/private_discussion/permissions

Public Discussion Group:
http://YOUR_SITE_URL/admin/group/types/manage/public_discussion/permissions

When viewing a Private Discussion Group, users must request a membership, while
users can simply join a Public Discussion Group.


Email Integration
--------------------------------------------------------------------------------

The Discussions Email module provides functionality to receive, create and reply
to discussions via email.

# Requirements

- Mandrill module (https://www.drupal.org/project/mandrill)

## Mandrill Module Installation

### Using Composer

Add the following line to the "require" block of your composer.json file:

"drupal/mandrill": "dev-8.x-1.x"

The block should look like this:

"require": {
  "drupal/mandrill": "dev-8.x-1.x"
}

### Manually

Download the Mandrill module with the Mandrill library already included:

https://github.com/thinkshout/mandrill/releases/download/8.x-1.0/mandrill-8.x-1.0-package.zip

Extract the archive to `modules/contrib/mandrill/`


Known Issues
--------------------------------------------------------------------------------

# Discussions view unable to find Comment Statistics fields.

This issue may be seen when viewing a discussion group, preventing the
discussions view appearing. To fix, add a Comment field to any entity.
The field can be removed the view has been confirmed to work.
