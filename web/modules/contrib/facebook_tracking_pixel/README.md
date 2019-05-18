Facebook Tracking Pixel
================================================================================

This module is meant to offer flexibility in adding Facebook tracking pixels to
your website. The difference in this module and other modules that allow you to
add codes to your page is that this is specifically targeted to Facebook
tracking pixels. You have the ability to add more than one tracking pixel per
page. This module also takes the code from Facebook as it is supplied by them
and optimizes it for loading on pages so it can be aggregated, compressed, and
pushed via a CDN if you wish.

Travis-CI Build status:
[![BuildStatus](https://travis-ci.org/taz77/drupal-facebook_tracking_pixel.svg?branch=7.x-1.x)](https://travis-ci.org/taz77/drupal-facebook_tracking_pixel)
[![ScanStatus](https://img.shields.io/coverity/scan/11182.svg)](https://scan.coverity.com/projects/taz77-drupal-facebook_tracking_pixel)

Comprehensive documentation including screenshots is [published](https://www.drupal.org/node/2697911) on Drupal.org

Basic Setup
================================================================================

First step to the module is adding a base tracking code. The base ID is 
available in your Facebook Account under Manage Ads->Tools->Pixels. On the right
hand column you will see your Account name and underneath that is the ID number.
This is your base code ID number.

Add your code to the UI (admin/config/system/facebook_tracking_pixel/base_codes).

This module allows you to manage multiple base tracking codes on your site. The
arrangment of the codes in the UI is the order in which they are added to the
site. Facebook recommends that you do not use more than three pixels on any
given page. Beyond that, the pixels may not execute.

Define what roles should be tracked. Out of the box, this module tracks no one.
The administrator must make a conscious decision of who to track on the website.
Go to Configuration -> System -> Administer Facebook Tracking Pixel 
(its at url admin/config/system/facebook_tracking_pixel) to configure what users
are tracked.

When adding a base tracking pixel you can choose to have the pixel show over the
entire site or not, this is accomplished via the "global" setting.

Role Tracking Information
--------------------------------------------------------------------------------
Globally, tracking is controlled via roles. However, due to an oversight in
Drupal 7 with the system of user roles, you are able to create a role that has
a machine name containing non-ASCII characters. non-ASCII characters cause a lot
of problems with software when used in a functional way within code. This small
oversight has been corrected in Drupal 8, you cannot create a machine name of a 
role with non-ASCII characters. 

Typically Drupal 7 modules use the role ID number instead of the role name, but 
this causes problems with portability because the role ID is an incremental
field. This problem usually comes to light when you are doing site deployments
or replications of a code base across multiple sites. The role ID issue is
something we have had to deal with in the past and therefore never code a module
that relies on role ID numbers.

In the admin UI, you will not see roles that have non-ASCII characters. If this
is a role you need to track you will have to create a new role that does not
have non-ASCII characters in the name and apply that role to your users. This
can be done pragmatically without writing code  using tools such as Views and
Views Bulk Operations (build a view of people with the old role and use VBO to
add your new role).

Tracking By Path
================================================================================

The main feature of this module that allows it to have ultimate flexibility is
the ability to attach tracking to pages on your site on a path by path basis.
This allows for direct and finely grained tracking of events.

In the path portion of the module (admin/config/system/facebook_tracking_pixel/path),
you can choose events to add to your site, the path they act on, and which base
code to use for the event.

Tracking User Registrations
================================================================================

You may choose to track user registrations on your site. You can enable user
registration tracking by going to the Track User Registration page 
(admin/config/system/facebook_tracking_pixel/user_registration) and enable the
tracking. You can then select what base code to track against. This only tracks
against a single base code. This code works off of special Drupal hooks to fire
when a user is added from any location on the entire site.

Purge
================================================================================

Provided for troubleshooting and "when all else fails" scenarios, there is a
purge function (admin/config/system/facebook_tracking_pixel/purge). By clicking
Purge All every code on the site and every path tracking event will be erased.
All Commerce tracking settings will be removed and all role settings will be
removed. This removes everything from the database and deletes all files that 
have been created. It will return the module to a state of initial installation.

You have been warned!

Commerce Tracking
================================================================================

There are two methods of Commerce tracking: manually and via Drupal Commmerce.
If you are using Ubercart you will need to track eCommerce actions manually via
the path function. If you are using Drupal Commerce you can turn on the Commerce
integration in the module located at: 
admin/config/system/facebook_tracking_pixel/commercetracking

When enabled, the module takes advantages of the hooks provided by Drupal
Commerce to automatically track eCommerce actions.

Detailed Instructions
================================================================================
Additional documentation in greater detail can be found here:
https://www.drupal.org/node/2697911

Developers - Hooks provided
================================================================================

There is one hook alter and one hook provided. The hook allows the ability to
provide additional tracking events that can be incorporated into a site. The 
additional events will then be available in the path admin UI. The name of the
provided hook is HOOK_facebook_tracking_pixel(). You may define additional
events by providing an array of event information. An example:

    mymodule_facebook_tracking_pixel(){
      $events = [];
      $events['pageview'] = [
        'name' => t('Key Page View'),
        'code' => 'fbq(\'track\', \'ViewContent\');',
      ];
      return $events;
    }
The array is keyed with the machine name of the event and each item contains an
array with the human readable name and the actual JS code to be used.

As a function of the path tracking. You may alter the paths being used by the
module by executing a `HOOK_facebook_tracking_pixel_alter()`. See
`facebook_tracking_pixel_facebook_tracking_pixel_alter()` in the file
*facebook_tracking_pixel.module* as an example of how to use this alter. This
module uses it's own alter to match paths.