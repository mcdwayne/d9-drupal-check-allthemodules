CONTENTS OF THIS FILE
---------------------

 * Introduction
 * The problem
 * The solution
 * Requirements
 * Installation
 * Known problems
 * Road map
 * Maintainers


INTRODUCTION
------------

Symlink is a module that solves a problem that many people are experiencing
when they add more then one menu items pointing to the same internal link.
This will cause the menu trail to act erratically.

Symlink will create a custom content type that will render any node it's
referencing. That way, from the Drupal's point of view, this will be a
completely different node every time even if it is always pointing to the
same link, and the menu trail and breadcrumb will work in a more predictable
fashion.


THE PROBLEM
-----------

Imagine that you have an amazing tool available in your Drupal site at the
address http://your_site/amazing_tool. And imagine that the site have several
sections, and that each section needs to have a link to the amazing tool in
its menu structure. This could result into a structure looking similar to
the following ...

Section 1
├── Item 1.1
│ └── Item 1.1.1
├── Link to the amazing tool <----- First instance
┊
┊
Section 2
├── Item 2.1
│ │
│ ├── Item 2.2.1
│ ├── Item 2.2.2
│ └── Item 2.2.3
├── Link to the amazing tool <----- Second instance
┊
┊
Section 3
├── Item 3.1
│ ├── Item 3.2.1
│ ├── Item 3.2.2
│ └── Item 3.2.3
└── Link to the amazing tool <----- Third instance


Now the big problem here is that only one of the menu items labelled "Link to
the amazing tool" will actually work. What seems to be happening is that
because the target of all these 3 links will be http://your_site/amazing_tool,
if you try to navigate to one of them, Drupal will stop searching as soon as
it finds a match. I think that this will end up being the link that has the
lowest mlid (menu link DB identifier). That's basically the first of the 3 items
that was created. So if someone was already visiting a page under Section 2,
then a click on the second instance of the link to the amazing tool will take
them to the tool alright, but the menu trail and the breadcrumb will show
that they are now in Section 1.


THE SOLUTION
------------

The symlink module fixes this issue by providing the following features:

    1. Symlinks are nodes.
    2. When viewing a node, the Symlink module will display a tab for adding
       new symlink.
    3. When viewing the symlink, the "Add symlink" tab will be visible since
       symlinks are also nodes.
    4. Additional symlinks can be created from other symlink, but they will
       always reference the original target node.
    5. A permission called "create symlink content" let's you control who can
       create a symlink.
    6. The symlink module is available for both Drupal 7 (D7) and Drupal 8 (D8)
    7. Contextual links still work. This let's you edit the original node.


REQUIREMENTS
------------

Contributed modules: none
Drupal core module: Text Field.


INSTALLATION
------------

    1. Install the module as usual and enable it.
    2. Give the permission to the role that should be allowed to create a
       symlink.
    3. On the node view page, click on the "Add symlink" tab.


KNOWN PROBLEMS
--------------

    1. This module is not using any official reference module (node reference or
       entity reference). This choice was intentional for the sake of simplicity
       during this first iteration.

    2. When a node is deleted, the symlinks are not removed. But there are
       all deleted when the module in uninstalled.


ROAD MAP
--------

 * Add a configuration page for setting some default behaviours.
 * Add an option for auto-granting the permission if the user has access to
   creating content for the node type being symlink'ed
 * Considering if symlinks should be entities instead of nodes.
 * Creating an API to make anything symlinkable (Views, Entities, etc.)
 * Considering if the entity reference module should be used for the target
   or not.
 * When deleting a symlink, rename the delete button so that it reads "Delete
   Symlink"
 * For a symlink, rename the default "Edit" tab to make it read "Edit symlink".
 * Provide various reports showing all the symlinks and theirs target nodes.
 * Use hooks to remove a symlink when the target node is deleted.


MAINTAINERS
-----------

Current maintainers:
 * Abdoulaye Siby (asiby) - https://www.drupal.org/u/asiby
 * Soroush Zo (drupalr) - https://www.drupal.org/u/druplr
