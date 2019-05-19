REQUIREMENTS
------------

- Webform module
- Cron jobs up and running (more information: https://drupal.org/cron).


ABOUT
------------

Webform Mass Email provides a functionality to send mass email for the
subscribers of a webform. You can select which email field from the result you
would like to use as the recipient's address.

This module uses Drupal's 'queue' table and cron jobs to send the emails
for users.


INSTALLATION
------------

- Enable the Webform Mass Email module at admin/modules.

- Configure Webform Mass Email permissions at admin/people/permissions and
  assign permission to send mass email for the roles you want.

  Administer Webform Mass Email
    - Allow user to administer all of the module configurations at
      admin/structure/webform/config/mass-email.

  Send Webform Mass Email
    - Allow user to access the Webform result mass emailing form at
      admin/structure/webform/manage/ID/results/mass-email (Note: To view this
      page, you will also need to set the Webform permissions to view the
      results.

- Configure the module settings at admin/structure/webform/config/mass-email.

  Cron time
    - How much time is being spent per cron run (in seconds).
      Cron execution must not exceed the PHP maximum execution time.

  Allow sending as HTML
    - If checked, all emails are processed as HTML formatted.
      You need to manually install some module handling HTML emails.

  Log emails
    - Should the emails be logged to the system log at admin/reports/dblog.
      Note that sending many (hundreds of) messages will fill up your log
      pretty badly. This is good for debugging.


SENDING MASS EMAIL
------------

- To send mass email, navigate to any of your Webform at
  admin/structure/webform.
- Go to 'Results' tab and then to the 'Mass Email' sub-tab.
- You'll see the Webform Mass Email form with some fields in it.

  Email field
    - The field that is going to be used as the recipient's email address.
      Auto-populates from the Webform's email elements.

  Subject
    - Subject for your email.

  Body
    - Body for your email.
