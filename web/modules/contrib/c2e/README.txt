---SUMMARY---

This module processes the e-mails collected by Inmail Collect. An incoming part
of a customer service can be created with this module. It is important to note
that this module only generates the content, does not perform any permission
management with the generated content. Recommended and related modules:

Nodeaccess
https://www.drupal.org/project/nodeaccess

Field Permissions
https://www.drupal.org/project/field_permissions

Media Private Access (?)
https://www.drupal.org/project/media_private_access
  
For a full description visit project page:
https://www.drupal.org/project/c2e

Bug reports, feature suggestions and latest developments:
http://drupal.org/project/issues/c2e


---INTRODUCTION---


This module creates a content type, a comment type and a media type to process
the collected data. A new content is generated from an incoming mail that
generates its own ID. If this identifier is found in the subject of the next
email, that will not be a new content, but a new comment for that content.
The module saves the text and html versions of the email in a separate field.
Attachments will be saved as media for possible reuse. You can set to 
automatically create users based on the email senders. You can also set up new
content for exactly the same emails, or just increase the number of counter in
the existing content.


---REQUIREMENTS---


The Inmail Collect module.


---INSTALLATION---


Install as usual. Place the entirety of this directory in the /modules 
folder of your Drupal installation. 


---CONFIGURATION---


Set up the right behavior for you on admin/config/system/c2e.


---CONTACT---

Current Maintainers:
*Balogh Zoltán (zlyware) - https://www.drupal.org/u/u/zoltán-balogh
