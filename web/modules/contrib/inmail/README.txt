CONTENTS OF THIS FILE
---------------------

* Introduction
* Features
* Requirements
* Configuration
  - Postfix & Drush
  - IMAP
  - Bounce processing
  - Collect


INTRODUCTION
------------

The Inmail module for Drupal 8 allows processing of incoming mail.


FEATURES
--------

Inmail is designed as a framework for various kinds of mail processing.
Essentially it provides a few plugin types and a generic processor services that
uses the plugins of those types. Because it was created with certain goals in
mind, the module includes a few readily usable components for use with the
framework. These goals include:
- Handling bounce messages resulting from sent bulk mail, such as newsletters
- Storing large quantities of raw messages for arbitrary post-processing


REQUIREMENTS
------------

The main Inmail module requires nothing besides Drupal 8. Submodules may require
further modules as specified by .info.yml files.


CONFIGURATION
-------------

How to configure Imail depends on what you need it for. In general, you need to
configure a Deliverer to specify where mail is coming from, a handler that
performs something with each message, and analyzers if you need those actions to
be conditional on certain aspects of each message. All these can be added in the
user interface on admin/config/system/inmail.

The following chapters describe a few use cases and significant configuration
options related to each of them.


POSTFIX & DRUSH

If you have root access to your web server, and also use it as a Postfix mail
server, you can configure Postfix to pass incoming mail directly to Inmail,
using Drush.

The "inmail-process" Drush command can read a single message from standard input
and pass it directly to the Inmail processing mechanism. The postfix-filter.sh
script included in this module is a simple wrapper around "drush
inmail-process", providing shell access to Inmail.

To use the Drush command, first create a Deliverer configuration using the
"Drush inmail-process" plugin, and match the "deliverer_id" argument with the
machine name of the created configuration. The examples assume a configuration
created with the ID "postfix-drush".

Consider as an example a web/mail server at example.com, with a Drupal root at
/var/www/drupal. All mail sent to site@example.com is to be processed by Inmail.
Simply add the following line to /etc/aliases:

  site: "| /var/www/drupal/modules/inmail/postfix-filter.sh -o --uri=example.com postfix-drush"

In this second example, Postfix is used to test a new component of Inmail, and
all test messages sent from the developer's machine to any address are looped
back to the developer's account "admin" on the local Postfix server.

  1. Add the following to /etc/postfix/main.cf:

       virtual_alias_domains =
       virtual_alias_maps = pcre:/etc/postfix/virtual_forwarding.pcre
       virtual_mailbox_domains = pcre:/etc/postfix/virtual_domains.pcre

  2. Create the following three files and add the corresponding lines:

     /etc/postfix/virtual_forwarding.pcre:
       /@.*/ admin

     /etc/postfix/virtual_domains.pcre:
       /^.*/ OK

     ~admin/.forward:
       "| /var/www/drupal/modules/inmail/postfix-filter.sh -o --uri=example.com postfix-drush"

The script and these examples assumes a Unix-based environment. The examples
should only be read as guidelines, as the exact configuration is likely to vary
between systems. You may for example need to provide the path to the Drush
executable:

  "| PATH=/usr/local/bin:$PATH /path/to/postfix-filter.sh postfix-drush"


IMAP

To fetch messages from an IMAP server, use the "Add deliverer" button on the
"Mail deliverers" page. Choose "IMAP" from the "Plugin" list, enter server
details in the form according to your mail provider, and click "Save". This
requires the IMAP extension to be enabled in your PHP build.

A deliverer that can fetch mail "on its own" (as opposed to the Drush deliverer
described above) is called a Fetcher. Fetchers are executed during cron runs.


BOUNCE PROCESSING

When your Drupal website sends bulk mail such as newsletters, delivery to some
recipients may fail. For every unreachable address, a bounce message is
generated and sent back to the website. Unless you attend to the bounces and
stop mailing the unreachable mailboxes, you run a risk of having mail servers
decline to transport future mail from you, due to your high bounce rate.

To classify incoming bounce messages, Inmail comes with a few bounce-oriented
message analyzers. Three of them are enabled by default; they are simple and
focus on standard-compliant bounces. Additionally, wrappers for two external
analyzer libraries (called Cfortune and PHPMailer-BMH) exist as separate
submodules. Those can optionally (and with varying results) be enabled to cover
non-standard bounces.

In the case where the site uses Simplenews to send newsletters, or many of the
bulk mail recipients are in some other way connected to site users, Inmail can
integrate with the Mailmute module to manage user send states. Install Mailmute,
and then Enable the Inmail Mailmute submodule. Now bounce messages from a user
or Simplenews subscriber may trigger changes in the send state of that
user/subscriber.

See the Mailmute project at https://drupal.org/project/mailmute


COLLECT

The Collect module provides storage for data of any kind, to allow for
subsequent arbitrary processing. To store all incoming email as Collect
Containers, simply enable the Inmail Collect submodule.

See the Collect project at https://drupal.org/project/collect
