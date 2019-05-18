/**
 * @file
 * README file for Owner or Admin.
 */

Owner or Admin
Adds a views filter to display only content that is owned by the current user,
or display all content to admin users.

----

1.  Introduction

This module provides a views filter handler to display content based on the
ownership of the current user, or all content for users with the "administer
nodes" permission. This filter is based on the existing "published or admin"
filter, and uses the existing "administer nodes" permission to identify admin
users.

----

2. Installatioon

Enable the module and the views filter handler will become available.

----

3. Configuration

Simply add a relationship to the content author (Content: Author) and then
add this views handler using that relationship.
