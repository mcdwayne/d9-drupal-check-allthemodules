########################################################################
##                   Views Add Button: Group README                   ##
########################################################################


### INTRODUCTION ###

Views Add Button: Group contains the custom routing information needed
to build Group and Group Content add buttons as a part of the Views Add
Button project. This module also contains the capacity to create
join/leave links for Group Memberships.

### REQUIREMENTS ###

 - Views
 - Group
 - Views Add Button


### INSTALLATION ###

Views Add Button: Group installs like most Drupal modules:

# Composer

 - Go to your project root, and require drupal/spectra_connect
   - composer require drupal/views_add_button_group
 - Go to your modules page (Extend) and enable.

# Download

 - Download the tar or zip file to your modules/contrib directory,
   and extract
 - Go to your modules page (Extend) and enable.


### CONFIGURATION ###

No module configuration is necessary. This will set the correct route
checking for groups and group content in any Entity Add Button in a
view. Groups do not need any special configuration, group content will
need to be configured per the instructions below.

### Creating Group Content/Membership Buttons ###

Create a view, and in any view area create an "Entity Add Button." Next,
select any type of "Group content" from the entity list. You must select
a group content plugin, not the base entity. For join/leave links,
select group memberships.

When you have created the button, you will need to enter the following
in the "Entity context" textfield as comma-separated:

 - A numeric Group ID. Usually this is a contextual filter, but could be
 a fixed value. This must always resolve as numeric.
 - A command word describing the type of button to generate:
   - (blank): Creates a "Create a new group entity" button for most
   group content, but for group memberships a blank command is
   interpreted as the "add" command
   - add: Creates an "Add an Existing Entity" button, and for group
   memberships creates an "Add an Existing User as a Member" button.
   - join: Group membership only - makes a "Join" button
   - leave: Group membership only - makes a "Leave" button
