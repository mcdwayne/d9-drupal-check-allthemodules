### General Information

This module provides a MailInterface to log all outgoing mails to DB.
Also it contains a minimal UI to view logged mails.

### Usage

1. Install the module as usual
2. To make drupal us the MailInterface you can eather use the mailsystem module or put
```php
$config['system.mail']['interface']['default'] = 'devel_mail_log';
```
into something like settings.php.

 3. Goto admin/reports/devel_mail_logger/send to send a test mail
 and to admin/reports/devel_mail_logger to see all logged mails.
