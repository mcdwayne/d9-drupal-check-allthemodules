User login persistent destination
=================================
This tiny module ensures a persistent destination parameter on user login page.

Given the following use case:

You redirect anonymous users to the login page, when they try to access a page
they are not authorized to view and add the "destination" query argument in
order to send them back to that page after successful login,
e.g. www.example.com/user/login?destination=my-secret-page.

That only works well, as long the user only really immediately logs in. If
he/she needs to register first and follows the register link in the local tasks,
the destination parameter gets lost (www.example.com/user/register).

This module will alter the local task links on the user login and register pages
(that are usually login, register and password reset) and preserves the
"destination" parameter, if present.

## Requirements

No special requirements.

## Credits

Commerce Quantity Increments module was originally developed and is currently
maintained by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
[agoraDesign KG](http://www.agoradesign.at/).
