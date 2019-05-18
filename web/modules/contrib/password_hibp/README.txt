Password HIBP (Have I Been Pwned)
=========================================================================================

This is a Drupal 8 module that aims to improve password security for your site's users by preventing them from using a password that is known to have been compromised.

In order to do this, the plugin makes use of the "Have I Been Pwned" API, operated by noted security researcher Troy Hunt.

HaveIBeenPwned.com contains an archive of user credentials that have been made public after being hacked, and allows anyone to query the database to find out whether their credentials have been compromised.

For the purposes of validating a new password, the API can be used to determine whether the password being entered has already been compromised. If the requested password already exists in the HaveIBeenPwned database, it should be assumed to be insecure, because many hacking attempts will use existing known credentials when attempting to crack new passwords.

In addition, the API also returns the number of times that the specified password exists in the database. This can also be used to establish the security (or lack thereof) of a given password; if it exists many times in the database, then it is clearly a commonly used password, and thus vulnerable to attack even if it successfully passes the conventional complexity tests.



Version History
----------------

* v1.0.0     2018-03-09: Initial release.


Installation
----------------
This is a standard Drupal 8 module. Installation is via Drupal's "Extend" page.


Usage
----------------

After installation, the module should be enabled via Drupal's "Extend" page.

The module currently has no configuration options available, so the only thing required is to enable it.


Caveats, Limitations, To-dos and Notes
--------------------------------------

* Potential future improvement is to add a configuration option to allow the site admin to specify how many times a password should appear in the HaveIBeenPwned database before it gets rejected as insecure.
* In the event that the API is broken or offline, the plugin will fail silently and allow the password to be used.
* The API is generally very quick to respond, but it it is possible that there may be a delay in response, particularly in the scenario where the system gets a timeout from the API request.
* Potential future improvement is to issue warnings for passwords that are compromised, but are permitted via the 'Max Compromises' option. User would be allowed to have their password but would still be warned that it may be insecure.


References
----------

Documentation for the HaveIBeenPwned API can be found here: https://haveibeenpwned.com/API/v2

The main HIBP site and further information about it can be found at https://haveibeenpwned.com/

The author of HIBP is Troy Hunt. His personal site can be found here: https://www.troyhunt.com/

The author of this module has also written a similar plugin for Joomla, from which the code for this module was derived.


License
----------------
This module is licensed under the GPL, specifically in this case, GPLv3. The full license document should have been included with the source code.

The HaveIBeenPwned API is licensed under the Creative Commons Attribution 4.0 International License.
