>>>> Please feel free to suggest improvements and additions to this file! <<<<

Overview
--------

File view access module help to define the permission for the enduser to view
the uploaded files or not. The uploaders can decide whether the uploaded file
need to access by particular set of user(User has privilege to view the
File view access permission) or not.

The privilege is assigned for each file that uploaded by the user. This module
basically developed to overcome the problem with private file system for
specific file uploads rather than all.


Acknowledgements
----------------

This module developed based on the option in Private access in Drupal 6 module.

Installation:
------------

1. Enable module using standard module installation.

2. Enable the permission to roles to access the view access files
  Path: /admin/people/permissions#module-file_view_access
  
3. Enable file view access on required file upload fields.

4. During content creation define the files that have option to "file view access".

5. In display file will render for selected role only.

Troubleshooting
---------------

If there is a file view access problem, Please rise issue in below URL
http://drupal.org/node/add/project-issue/file_view_access


Support/Customizations
----------------------

Support by volunteers is available on

   http://drupal.org/project/issues/acl?status=All&version=8.x

Please consider helping others as a way to give something back to the community
that provides Drupal and the contributed modules to you free of charge.

For paid support and customizations of this module or other Drupal work,
contact the maintainer through his contact form:

  http://drupal.org/u/arunkumark
