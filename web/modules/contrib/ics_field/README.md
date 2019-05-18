CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Use of tokens
 * References


INTRODUCTION
------------

The ICS Field module provides a field type, along with a widget and a
formatter that, when added to a node and combined with a Datetime field can be
used for providing *.ics (iCalendar) files that can be used with an
email/calendar client program.


REQUIREMENTS
------------

This module requires the following modules to be installed:

* Datetime: https://www.drupal.org/docs/8/core/modules/datetime/
* Token: https://www.drupal.org/project/token

and the following libraries to be available (autoloaded):

* Html2Text: https://packagist.org/packages/html2text/html2text
* eluceo — iCal: https://packagist.org/packages/eluceo/ical


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
------------

The module provides no configuration. That being said, the following points
are important:

1. It assumes that the process through which Drupal is run has file and folder
create permissions for the public:// filesystem. It will store all ics files
into the public://icsfiles folder if a user specified folder is not given,
and when not found it will try to create it.

2. When adding a Calendar Download field to a content type, you are required to
select a Datetime field from the same content type. That relation will allow
the Calendar Download module to decide when generated events occur.

3. The field makes an ~UNMANAGED~ copy of the file



USE OF TOKENS
------------

The Calendar Download module supports using tokens inside the Summary and the
Description sub-fields. Available tokens include the properties and fields of
the given content type and are available through the UI as a list that can be
click-inserted into the Summary or Description sub-fields.

> While editing a node, by entering e.g. "[node:title]" into the Calendar Download's
Summary sub-field and saving the node, the node's title will be inserted into
the summary sub-field. 


REFERENCES
------------

The following links provide more information about the iCal format:

* https://en.wikipedia.org/wiki/ICalendar
* http://www.ietf.org/rfc/rfc5545.txt


CONTRIBUTING
------------

We use a Github pull request workflow. Each pull request should have a related and cross-linked drupal.org issue.

[github](https://github.com/ibrows/drupal_ics_field)

We welcome contributions in the following areas

* Unit tests - Test coverage is currently good but not great.

develop: [ ![Codeship Status for ibrows/drupal_ics_field](https://app.codeship.com/projects/c9426990-6881-0135-a28b-5ec5668067cc/status?branch=develop)](https://app.codeship.com/projects/241363)
master: [ ![Codeship Status for ibrows/drupal_ics_field](https://app.codeship.com/projects/c9426990-6881-0135-a28b-5ec5668067cc/status?branch=master)](https://app.codeship.com/projects/241363)