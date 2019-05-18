# CiviMail for Drupal 8

Integration with CiviCRM CiviMail.

### Features

Send a node from a CiviCRM Contact to CiviCRM Groups.
From Contact and destination Groups selection is per node 
and CiviMail campaign.

**CiviMail Digest submodule**

Send to configured Groups a digest, at a weekly interval, of nodes
that were previously sent via the CiviMail module,
with an option to include content updates.

### Configuration

- Per content type configuration: enable via the CiviMail tab. 
Set then the view mode for the mail, and the Groups
that are eligible to send and receive the mail.
- The CiviMail feature is then available as a node local task 
(while viewing or editing a node from a CiviMail enabled content type).

For the setup of CiviCRM with Drupal 8, refer to this post: 
[Install CiviCRM 5 with Drupal 8 using Lando](https://colorfield.be/blog/install-civicrm-5-with-drupal-8-using-lando) 

Make sure that the _"Send Scheduled Mailings"_ Job is configured as active in _/civicrm/admin/job?reset=1_.


**CiviMail Digest submodule**

- Configure via _/admin/config/civicrm/civimail_digest/settings_
- Preview and prepare digests via _/civimail_digest/digests_.

### Dependencies

- [Drupal 8](https://github.com/drupal/drupal)
- [CiviCRM Core](https://github.com/civicrm/civicrm-core)
- [CiviCRM Drupal](https://github.com/civicrm/civicrm-drupal-8)
- [CiviCRM Tools](https://drupal.org/project/civicrm_tools)

## Roadmap

- Send test mails via CiviCRM.
- Write documentation for Layout integration.
- Improve documentation and admin UI of CiviMail Digest.
- Automation of the digest creation via cron.
