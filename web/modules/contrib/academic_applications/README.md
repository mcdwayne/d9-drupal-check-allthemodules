# Academic Applications Module

This module provides a simple system for applicants to apply to academic
programs.

## Features

 * Contacts academic references inviting them to a secure upload form for
   letters of recommendation.
 * All of the powerful [Webform](https://www.drupal.org/project/webform)
   features are supported.
 * "Bundles" application form answers and PDFs with letters of recommendation
   into a single PDF.


## Requirements

 * Webform >= 8.x-5.0
 * [GhostScript](http://www.ghostscript.com/), specifically `gs`, must be
    installed and executable by PHP.

## Setup

 1. Configure the
    [private file system](https://www.drupal.org/docs/8/core/modules/file/overview).
 2. Enable the module.
 3. Configure the module at admin/config/academic_applications/settings.
 4. Create a webform for the program application.
 5. Create a second webform for the letters of recommendation that accepts
    PDFs only in a file field.
 6. In the form settings, check the box next to __Allow elements to be
    populated using query string parameters__.
 7. Create a hidden form element with key set to 'wt'.
 8. Create a workflow connecting the application webform to the letters of
    recommendation webform at
    /admin/structure/academic-applications-workflows.
 9. In the program application webform, have an email sent to recommenders
    containing a link to the second webform with the query parameter 'wt' set
    to `[webform_submission:uuid]`. Example:
    `[site:url]form/ar?wt=[webform_submission:uuid]`

Those are the base requirements. All other field types are supported, but all
files to be "bundled" must be PDFs. You can optionally pass additional values
to the second webform as query parameters.

## Optional Features

* The module will list recommender names on the submission list if there is a
   field with machine name 'name' on the letters of recommendation webform.
 * The module will add the applicant's name to the bundle PDF filename if there
   is a field with machine name 'name' on the application webform.

## Examples

 * Enable the optional Academic Applications Example module to try a working
  example.
