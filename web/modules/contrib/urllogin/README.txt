This module provides a way to transparently log users into the web site when
they follow a link. The primary use is for mass email where users are send
individualized URL's which take them to a page on the site, in a "logged in"
state. Every effort has been made to minimize the potential security risks.


USE CASE
========

 * The main use case is for mass emailing where it is unlikely that the
   recipients will go through the hassle of creating accounts and passwords.
   Assuming an account has been created for them, email to them contains a
   customized link which will automatically log them in and take them to a
   target page.
 * This permission would only be given to accounts with "low value" privileges
   (e.g. no admin, financial or access to confidential data).
 * The reason for logging users into the site would typically be so that they
   can do activities such as:
    o sign up for events
    o comment, rate, "like", or otherwise interact with the site
    o unsubscribe from email or change email preferences
 * Usage tracking
    o Widely used mass email tools such as MailChimp and Campaign Monitor allow
      the sender to track which links in the email recipients have clicked on.
    o Using this module, Drupal's own tracking system can be used to accomplish
      this, providing a far more integrated solution than provided by third-
      party mailers.

FEATURES
========

 * Security
    o The login access link is encoded with a high level of security based on
      sha256
    o All encryption/decryption functions are encapsulated in a separate file
      so that an alternative can easily be dropped in as a replacement
    o All currently issued access links may be instantly "expired" by simply
      changing an administration setting, giving full control over the active
      lifetime of the links.
    o All access failures are logged together with the reason for the failure
    o The main security weakness would be from emails going astray or being
      intercepted. This can be mitigated by:
       - giving access links a short lifetime
       - limiting the permissions of users who are allowed this mode of access.

 * Design Features
    o The module is designed to scale to over 100,000 users, (although only
      tested with 15,000).
    o Both users and spam detectors are suspicious of long URL's, so every
      effort has been make to keep the login link as short as possible
    o For this reason, the embedding login string is only 12 characters long.
       - e.g. http://example.com/l/zjIR0AeO_zef/blog/myarticle
    o base64URL encoding has been used to avoid problems
    o The link can take the user directly to any page on the site for which
      they have permission
    o The module is integrated with persistent login

DIFFERENCES FROM OTHER SIMILAR MODULES
======================================

The most similar modules are Easy Login and Token Authentication although the
similarities are only superficial because they both have entirely different use
cases, as described below.

 _____________________________________________________________________________
|            |URL LOGIN           |EASY LOGIN           |TOKEN AUTHENTICATION |
|____________|____________________|_____________________|_____________________|
|            |                    |allowing a small     |                     |
|            |                    |number of individual |providing access to  |
|Use case    |mass emailing       |users to manage their|RSS feeds            |
|            |                    |access using a URL   |                     |
|            |                    |string               |                     |
|____________|____________________|_____________________|_____________________|
|            |                    |All users have an    |                     |
|            |                    |extra "password"     |                     |
|            |All encryption/     |added to the         |                     |
|Architecture|decryption done on  |database. User can   |Same as Easy Login   |
|            |the fly             |log in by putting    |                     |
|            |                    |this "password" in a |                     |
|            |                    |URL                  |                     |
|____________|____________________|_____________________|_____________________|
|            | * Highly secure    |                     |                     |
|            | * Large number of  |                     |                     |
|            |   users can be     |                     |                     |
|            |   managed easily   |                     |Optimized for RSS    |
|Architecture| * No database      |Detailed individual- |feeds or a restricted|
|strengths   |   tables need to be|level control        |set of pages (See    |
|            |   created/         |possible             |link below)          |
|            |   maintained       |                     |                     |
|            | * mass download of |                     |                     |
|            |   access strings to|                     |                     |
|            |   csv file         |                     |                     |
|____________|____________________|_____________________|_____________________|
|            |                    | * Security: access  |                     |
|            |                    |   strings are stored|                     |
|            |                    |   unencrypted in the|                     |
|            |                    |   database          |                     |
|            | * No individual    | * No way of making  |                     |
|            |   control (except  |   access strings    |                     |
|Architecture|   by permission)   |   have an expiry    |Logs in and out on   |
|weaknesses  | * no way of re-    |   date              |every page access.   |
|            |   setting an       | * methodology does  |(See link below)     |
|            |   individual access|   not scale to a    |                     |
|            |   string           |   large number of   |                     |
|            |                    |   users             |                     |
|            |                    | * no mass download  |                     |
|            |                    |   of access strings |                     |
|            |                    |   possible          |                     |
|____________|____________________|_____________________|_____________________|
For a more detailed evaluation of Token Authentication see the issue queue on
the subject ( http://drupal.org/node/1110002#comment-4287076 ).

Another similar module is One-time login links which is a very minimal utility
module that simply re-creates the link that a user would get had they forgotten
their password and needed to re-create it.

 * This module has a very limited number of use cases because the landing page
   is (of necessity) the user's account where they need to create a password.
 * This limits the use of the module to those cases where users are willing to
   create themselves a password as the first step to viewing the site.
 * There is no way of expiring the links if the user has not logged in before.
   They last indefinitely--a security risk.
 * It is not suitable for sending in mass email because experience shows that
   email recipients will often go back to the original email to re-gain access
   to the site. Drupal's one-time link mechanism that this module utilizes will
   not allow this behaviour.

WHICH MODULE TO USE?
 * If one or more of the following is true, use Token Authentication:
   a. For accessing RSS feeds.
   b. There are only a few specific pages or kinds of nodes that the user needs
      to see, and all menus and links that would take them to other parts of
      the site are hidden.
 * If one or more of the following is true, use URL Login
   a. The user should have the same experience as if they were logged in
      normally, and can explore the site fully.
   b. The user may enter data on the site.
   c. The links come from email and it is required to track *which* email
      message the user clicked on.
 * Security considerations
   a. If the site contains highly sensitive material or e-commerce, then
      probably neither module should be used, but a careful use of Token
      Authentication for a limited part of the site might be possible
   b. The main security risk is email being intercepted or old email being
      carelessly backed up or broken into. The main way to reduce this risk is
      to have an expiry date on the authentication data. This is much easier to
      achieve in URL Login.
   c. If the Drupal site were compromised, Token Authentication and Easy Login
      keep unencrypted passwords in the database.

CONFIGURATION
=============

1. Set a passphrase and validation numbers on the urllogin administration page
   (/admin/settings/urllogin)
   An alternative (and more secure) solution is to add the following line in
   settings.php:

   $GLOBALS['urllogin_passphrase'] = 'Change this to your own passphrase';

   This will override the administration page entry and is more secure since it
   is not stored in the database.

2. Give the "login via URL" permission to users who are allowed to log in with
   this module
3. Generate login strings (can be downloaded as a CSV file). Login strings are
   in the form:
    o http://example.com/l/12CHARSTRING/my_blog_page where the '/l/' and the 12
      character access string have been inserted into the URL.
4. Before sending the URL to real people, it can be tested by using
   '/l_test/' instead of '/l/' in the URL.

POSSIBLE FUTURE DEVELOPMENT
===========================

 * Integration into simplemail

SUPPORT
=======

If you experience a problem with urllogin or have a problem, file a request or
issue on the urllogin queue at http://drupal.org/project/issues/1076736. DO NOT
POST IN THE FORUMS. Posting in the issue queues is a direct line of
communication with the module authors.

No guarantee is provided with this software, no matter how critical your
information, module authors are not responsible for damage caused by this
software or obligated in any way to correct problems you may experience.


SPONSORS
========

The urllogin module is sponsored by Corporate Finance Associates
( http://cfaw.ca ).

Licensed under the GPL 2.0.
http://www.gnu.org/licenses/gpl-2.0.txt

