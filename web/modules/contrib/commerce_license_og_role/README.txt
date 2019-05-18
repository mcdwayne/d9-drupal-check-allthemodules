INTRODUCTION
------------

The Commerce License OG Role module allows Commerce Licenses to grant roles in
OG Groups.

Each license product can grant a role in a specific group, both of which are
configured on the product variation.

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce License
 * OG

TO-DO
-----

The following issues affect this module's functionality:

 * Add a global permission to allow admins to create a license for any group and
   role.
 * Fix the AJAX for the roles element in the license configuration form.
 * Users who are not initially a member of a group, and are granted a role other
   than 'member' remain a member of the group when their license is revoked.
