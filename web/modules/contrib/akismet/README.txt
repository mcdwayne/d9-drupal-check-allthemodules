
-- SUMMARY --

Integrates with the Akismet service: https://akismet.com

For a full description of the module, visit the project page:
  http://drupal.org/project/akismet

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/akismet

For issues pertaining to the Akismet service, contact Akismet Support:
  https://akismet.com/contact
  - e.g., inappropriately blocked posts, spam posts getting through, etc.
  - Ensure to include the Akismet session/content IDs of affected posts; find
    them in Drupal's Recent log messages by filtering by the "akismet" category.

-- INSTALLATION --

* Install as usual:
  https://www.drupal.org/documentation/install/modules-themes/modules-8

* Go to https://akismet.com,

  - sign up or log in with your account
  - go to your Site manager ("Manage sites" link in the upper right)
  - create a site (API keys) for this Drupal installation.

* Enter your API keys on Administration » Configuration » Content authoring
  » Akismet » Settings.

* If your site runs behind a reverse proxy or load balancer:

  - Open sites/default/settings.php in a text editor.
  - Ensure that the "reverse_proxy" settings are enabled and configured
    correctly.

  Your site MUST send the actual/proper IP address for every site visitor to
  Akismet.  You can confirm that your configuration is correct by going to
  Reports » "Recent log messages".  In the details of each log entry, you should
  see a different IP address for each site visitor in the "Hostname" field.
  If you see the same IP address for different visitors, then your reverse proxy
  configuration is not correct.

-- CONFIGURATION --

The Akismet protection needs to be enabled and configured separately for each
form that you want to protect with Akismet:

* Go to Administration » Configuration » Content authoring » Akismet.

* Add a form to protect and configure the options as desired.

Note the "bypass permissions" for each protected form:  If the currently
logged-in user has any of the listed permissions, then Akismet is NOT involved
in the form submission (at all).


-- TESTING --

Do NOT test Akismet without enabling the testing mode.  Doing so would negatively
affect your own author reputation across all sites in the Akismet network.

To test Akismet:

* Go to Administration » Configuration » Content authoring » Akismet » Settings.

* Enable the "Testing mode" option.
  Note: Ensure to read the difference in behavior.

* Log out or switch to a different user, and perform your tests.

* Disable the testing mode once you're done with testing.


-- FAQ --

Q: Akismet does not stop any spam on my form?

A: Do you see Akismet's privacy policy link on the protected form?  If not, you
   most likely did not protect the form (but a different one instead).

   Note: The privacy policy link can be toggled in the global module settings.


Q: Can I protect other forms that are not listed?
Q: Can I protect a custom form?
Q: The form I want to protect is not offered as an option?

A: Out of the box, the Akismet module allows to protect Drupal core forms only.
   However, the Akismet module provides an API for other modules.  Other modules
   need to integrate with the Akismet module API to expose their forms.  The API
   is extensively documented in akismet.api.php in this directory.

   To protect a custom form, you need to integrate with the Akismet module API.
   However, if you have a completely custom form (not even using Drupal's Form
   API), you may also protect that, by following Akismet's general guide and
   example for PHP client implementations:

   - https://github.com/Akismet/guide
   - https://github.com/Akismet/guide/tree/master/examples/php52


-- CONTACT --

For questions pertaining to the Akismet service go to https://akismet.com/support

Current maintainers:
* Lisa Backer (eshta) - http://drupal.org/u/eshta
* Nick Veenhof (Nick_vh) - https://www.drupal.org/u/Nick_vh
