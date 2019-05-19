Webform DropzoneJS 8.x-1.x
---------------

### About this Module

This modules creates a new DropzoneJS element that you can add to webforms. It
provides a user friendly way for users to upload multiple files in a form field.


### Installing the Webform DropzoneJS Module

1. Copy/upload the dropzonejs module to the modules directory of your Drupal
   installation.

2. Enable the Webform and DropzoneJS modules if they are not already installed.
   You will need to make sure to install the DropzoneJS library to get that
   module installed. Reference that module docs for info.

3. Enable the 'Webform DropzoneJS' module and desired sub-modules in 'Extend'.
   (/admin/modules)

4. Set up user permissions. If you want to add the dropzonejs field to a webform
   that anonymous users use, make sure to update the permissions for
   "dropzonejs". (/admin/people/permissions#module-webform)

5. Add the "DropzoneJS" element to a webform. This is listed under the category
   "FILE UPLOAD ELEMENTS".

5. The DropzoneJS field should show up on the frontend as you would expect.
