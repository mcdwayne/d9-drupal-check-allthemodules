YAML FORM MAILCHIMP 8.x - 1.x
=============================

This module allows to send YAML form submissions to MailChimp list.


REQUIREMENTS
------------

- Drupal 8.x
- Yaml Form module (https://www.drupal.org/project/yamlform).
- MailChimp module (https://www.drupal.org/project/mailchimp).
- MailChimp account (http://mailchimp.com) with at least one list.
- MailChimp API PHP library (MailChimp module dependency).


INSTALLATION
------------

1. Unzip the files to the "sites/all/modules" OR "modules" directory and enable the module.

2. Go to YAML forms list page (admin/structure/yamlform) and click "Edit" on desired YAML form.

3. Click Emails/Handlers secondary tab and then click on "Add handler" button.

4. Click on "Add handler" button on "MailChimp" row.

5. Fill in the form. You should have at least one list in your MailChimp account, and at least one Email field in
your YAML form.

6. If you want to map extra fields, create a sign-up form at your MailChimp account (Signup forms => General forms).
Add as many form items as you want, but take into account "Field tag" of each one. Each field you have configured
in your YAML form, will be mapped there if the "key" value in the YAML form matches "Field tag" value in MailChimp.
Don't worry about upper/lowercase.



SUPPORT
-------

Donation is possible by contacting me via grisendo@gmail.com


CREDITS
-------

8.x-1.x Developed and maintained by grisendo (https://www.drupal.org/user/848238)
