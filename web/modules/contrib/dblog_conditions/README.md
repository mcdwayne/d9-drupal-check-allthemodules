Database Logging Conditions (Watchdog conditions)
====

**Extends Core** Database Logging module (dblog, formerly: watchdog) to allow **conditional logging** to the database.

Introduction
----

The **DBLog Conditions** module is part of the 
[OSCE](https://www.drupal.org/osce-organization-for-security-and-co-operation-in-europe) suite of admin tools. It is 
especially useful on production environment, in combination with the following module: 

* DBLog Mailer - http://drupal.org/project/dblog_mailer

Use Case
----

On a production system, you most probably want to enable Core's **Syslog** module to rely as less as possible on your
database resources for logging operations. This is absolutely fine for logging technical errors, but it is not very 
handy for those who need an easy way to log and review **certain** errors in a more accessible way, directly from the 
 Drupal backend. You could use both Syslog and Core's **Database Logging** module (dblog, formerly know as Watchdog) but 
 this would mean logging all errors to the database...
 
This module serves exactly this use case, by allowing you to enable Core's dblog module and define which log **channel**
should be logged, and which can be dropped so that performances are maintained.

Requirements
---

* **Database Logging** (dblog) from Drupal Core

Installing the DBLog Conditions Module
---

1. Copy/upload the dblog_conditions module to the modules directory of your Drupal
   installation, or run: *composer require drupal/dblog_conditions*

2. Enable the dblog_conditions module in 'Administration > Extend' 
   (/admin/modules)
   
3. Configure the module in 'Administration > Configuration > Development > Logging and errors > DbLog Conditions'
    (/admin/config/development/logging/dblog_conditions)

4. (recommended) Install companion [DBLog Mailer](https://drupal.org/project/dblog_mailer) module and configure some
email notifications for the log channels you want to keep an eye on. 


Issues, contributions
---

Feel very welcome to report issues and contribute patches in the issue queue:

https://www.drupal.org/project/issues/dblog_conditions

Credits
---

DBLog Conditions is developed by the **OSCE: Organization for Security and Co-operation in Europe.**

* see our [organisation profile on Drupal.org](https://www.drupal.org/osce-organization-for-security-and-co-operation-in-europe)
* learn more about the [OSCE](http://www.osce.org)

If you are a developer or an agency interested to work with us, we encourage you to contribute to the modules we 
develop and support (see the list [here](https://www.drupal.org/osce-organization-for-security-and-co-operation-in-europe)).
This is a great way for us to evaluate your skills and can be the base of future collaboration.