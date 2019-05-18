Database Logging Mailer (Watchdog mailer)
====

**Send log entries** from Core Database Logging module *(dblog, formerly: watchdog)* **by email at cron run**.

Introduction
----

The **DBLog Mailer** module is part of the 
[OSCE](https://www.drupal.org/osce-organization-for-security-and-co-operation-in-europe) suite of admin tools. It is 
especially usefull on production environment, in combination with the following module: 

* DBLog Conditions - https://drupal.org/project/dblog_conditions
* Ultimate Cron - https://www.drupal.org/project/ultimate_cron

Use Case
----

On a production system, certain errors can occur that needs to be notified to some of your colleagues/staff. For 
 technical teams, this is usually done via syslog, log processing tools and alert systems. For non technical teams, the 
 use of a good old email can still be the best way to go.

Using Core's **Database Logging** module and [**DBLog Conditions**](https://drupal.org/project/dblog_conditions) module 
(highly recommended to maintain performances), the **DBLog Mailer** module allows you to deliver log entries by email at
**cron run** for certain logs channels by declaring configuration in a very simple format:

 ```
module1|Error with module1|email1@domain.com;email2@domain.com
module2|Error with module2|email3@domain.com;email4@domain.com
```

Requirements
---

* **Database Logging** (dblog) from Drupal Core

Installing the DBLog Mailer Module
---

1. Copy/upload the dblog_mailer module to the modules directory of your Drupal
   installation, or run: *composer require drupal/dblog_mailer*

2. Enable the dblog_mailer module in 'Administration > Extend' 
   (/admin/modules)
   
3. Configure the module in 'Administration > Configuration > Development > Logging and errors > DbLog Mailer'
    (/admin/config/development/logging/dblog_mailer)

4. (recommended) Install companion [DBLog Conditions](https://drupal.org/project/dblog_conditions) module and configure 
it to log errors to the database for certain modules only.  

5. (recommended) Install [Ultimate Cron](https://www.drupal.org/project/ultimate_cron), then configure at which
 frequency DBLog Mailer should run in 'Administration > Configuration > System > Cron > Cron jobs' for module 
 'Database Logs Mailer (Watchdog mailer)'
 (/admin/config/system/cron/jobs/manage/dblog_mailer_cron)
 
6. (optional) Install [SMTP Authentication Support](https://www.drupal.org/project/smtp) module and use an 
SMTP-as-a-service provider if your site is hosted on a server that is not whitelisted for your domain. This will avoid
 your emails to be marked as spams. Example of providers: [SendGrid](http://sendgrid.com), [Mandrill](http://www.mandrill.com)

Issues, contributions
---

Feel very welcome to report issues and contribute patches in the issue queue:

https://www.drupal.org/project/issues/dblog_mailer

Credits
---

DBLog Mailer is developed by the **OSCE: Organization for Security and Co-operation in Europe.**

* see our [organisation profile on Drupal.org](https://www.drupal.org/osce-organization-for-security-and-co-operation-in-europe)
* learn more about the [OSCE](http://www.osce.org)

If you are a developer or an agency interested to work with us, we encourage you to contribute to the modules we 
develop and support (see the list [here](https://www.drupal.org/osce-organization-for-security-and-co-operation-in-europe)).
This is a great way for us to evaluate your skills and can be the base of future collaboration.