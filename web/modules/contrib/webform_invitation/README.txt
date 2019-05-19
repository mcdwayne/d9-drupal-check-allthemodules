-- SUMMARY --

The Webform Invitation module allows you to restrict submissions to a webform
by generating codes which may then be distributed e.g. by email to
participants. Upon activating the invitation mode for a webform a new element
will automatically be added to the webform where the code has to be entered.
The code may also be submitted by adding ?code=[CODE] to the webform's URL.
When the participant hits the submit button, the code will be checked for 
validity.


-- REQUIREMENTS --

Webform module version 8.x-5.x installed.


-- INSTALLATION --

Install as usual.


-- CONFIGURATION --

No special configuration necessary. Works out of the box.


-- USAGE --

When installed, this module adds a new tab to webforms called "Invitation"
(next to "View", "Test", "Results" and "Build").
There are four sub-tabs called "Settings" (default action), "List codes",
"Generate", and "Download".

The "Settings" tab currently only contains the option to enable or disable
invitations for the current webform.
"List codes" shows a simple list of all invitation codes that have been
generated for the current webform and whether or not they have been used.
"Generate" provides a form to generate invitation codes. You may choose the
number of generated codes and their type (MD5 or custom).
"Download" tab has no special content but lets you download the list of 
not used generated codes as an Excel sheet including code and full URL to
access the webform with auto-submitting the invitation code.
