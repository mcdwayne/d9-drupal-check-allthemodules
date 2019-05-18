EMAIL CONFIRMER
===============

CONTENTS OF THIS FILE
---------------------

 * Summary
 * Installation
 * Configuration
 * Usage
 * Contact


SUMMARY
-------

Email confirmer is a suite to confirm email addresses on Drupal. It provides:

 * an API and a service as a central method for email confirmation that other
   modules can use
 * a content entity type to store and control the confirmations
 * essential functionalities featured by included sub-modules

Confirmation of the user's email address (included sub-module)

Drupal core provides a mechanism to confirm the user's email when registering,
but that's all. The email confirmer users sub-module provides:

 * confirmation request when a user changes their email address
 * update the database of confirmed emails when a users logs in for the first
   time or by a one-time log in link

What does it mean to confirm an email address?

An email address is confirmed by sending a message to this address with a
unique link. This link points to a confirmation form where the recipient can
accept or cancel the confirmation.

Database of confirmed email addresses

By design, email confirmer stores each confirmation in a content entity.
Currently there is no UI to list or work directly with them becouse that's out
of the purpose of it, but any module can work with it. The information
available for each confirmation is:

  * the email address to confirm
  * uid of the user who initiates the confirmation
  * the IP address from which it was launched
  * the creation timestamp
  * a map of arbitrary properties (key -> value)
  * realm: the scope or module that creates the confirmation
  * status flags: pending, cancelled, confirmed, sent
  * specific URLs to go after confirmation, cancellation or on error
  * private flag: confirmation can be attended by the same user who initiates

A basic advantage of having a database is that the users do not need to
re-confirm again and again the same email address for different usages, like
email subscriptions or by updating the address in their user profile.

Email confirmer automatically deletes old records. The time to live can be
stablish in the module settings, or disabled at all for a permanent database.


INSTALLATION
------------

Install as usual, see https://www.drupal.org/node/1897420 for further
information.

Email confirmer defines two permissions that you must review after
install it:

  * "access email confirmation" a user-level permission, required to confirm,
    cancel or resend an email confirmation. It is disabled by default, so
    you must to enable for that roles you want to use the email confirmation
    service.
  * "administer email confirmations" an admin-level permission needed to manage
    the settings and administer confirmations.

Uninstallation requires the deletion of any email confirmation entities. You
can massively delete any entity instance using the "Delete all" module. See
https://www.drupal.org/project/delete_all for further information.


CONFIGURATION
-------------

Module configuration is available under Manage -> Configuration -> System
(/admin/config/system/email-confirmer).


USAGE
-----

The base module does not have an UI and does nothing by itself. The intended
audience is module developers or web site programmers. Only the configuration
may be relevant for webmasters and website builders.

The included submodules provide some end-user functionalities. See README.txt
from each of them.

The two key pieces working with email confirmer are:

 * the "email_confirmer" service
 * the "email_confirmer_confirmation" entity type

Typically, your module will initiate an email confirmation by calling the
"confirm" method of the email_confirmer service. There are some other methods
for a finer controlled way.

Confirmation processes are stored in confirmation content entities. A
collection of specific methods is available, to check the confirmation status
and operate on it.

The confirmation entity type does not have bundle support, and any additional
field must be added or altered programmatically by your module.

See the PHPDoc for further details.


CONTACT
-------

Current maintainers:
* Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420
