Drupal module: Accommodation Bookings

INTRODUCTION
==============================
A small suite of modules for Drupal 8 websites that provide a booking system for guest-houses and other accommodation with rooms.

Accommodation Bookings does not support online payments; payments must be done through a separate system.



FEATURES
==============================

 * _Bookings_ that save the number of guests, an arrival (check-in) date, a departure (check-out) date, and other fields. 
 * _Bookable units_ that have a capacity (max guests), and check-in and check-out times.
 * _Seasons_ that determine the prices of the bookable units between specified dates.
 * _Promotions_ that allow discounts for bookings where the guest provides a promo code.
 * _Statistics_ such as bookings per month, revenue per month, and occupancy per month.
 * _Site settings_ such as number of hours that bookings are valid for until they expire.



REQUIREMENTS
==============================
Accommodation Bookings can only be installed in a Drupal 8 website.

The following Drupal core modules need to be installed and enabled:

 * Node
 * Field
 * Views
 * Path
 * Automated Cron
 * Datetime
 * Options
 * Text

Additionally, the following non-core modules need to be installed and enabled:

 * RESTful Web Services
 * Serialization



FAQS
==============================
 Q: Can Accommodation Bookings be added to an existing site with content?
 A: Yes, this system can be added to a site at any time, regardless of the content it already has.

 Q: Is this a replacement for the Rooms module?
 A: While this sytem can be used in a similar way, it is much simpler than Rooms, and doesn't support the management of many bookable units (rooms) as easily, and doesn't support e-commerce such as Drupal commerce.

 Q: Can I contribute to improve Accommodation Bookings?
 A: Yes please! We'd love your help. Get in touch with us to discuss working on the modules.



INSTALLATION
==============================

This module suite contains several modules that should be used together. It can be installed the manual way (download, unzip, copy, paste), through drush, but not though composer (yet).

Accommodation Bookings was designed to be used on either just one site for all services, or multiple sites.

Installation: One site
----------------------

When you will use one site that will do everything (accept bookings and store booking information), simply install all the modules in the 'Abookings' group, and you'll be ready to get started.

Installation: Multiple sites
----------------------------

When you want one or more sites to show a booking form (the front-end sites), and one site to store and manage all booking information (the back-end site), the installation process is only a little more complicated.

On the backend-site, install all "Abookings" modules.

On the font-end sites, install just the "Accommodation Bookings" and "Booking" modules.



USAGE
==============================

1. The first thing you need to do is visit the "Booking Settings" page (there is a link to it in the administration menu), and choose your settings according to the instructions on that page.
* If you're using multiple sites, you need to do this on each site.

2. You need to then go to the "Seasons" and "Bookable Units" pages (also have links in the admin menu) and make at least one of each.
* If you're using multiple sites, do this only on the back-end site.

3. Then go to the "Booking Email Templates" page and write the email templates you'd like to use for emails that get sent to the guests.
* If you're using multiple sites, do this only on the back-end site.

4. Then guests can visit the "Make a Booking" page (/book) to place bookings.
* If you're using multiple sites, this should only be done on the front-end site.

5. You can then manage the bookings on the "Bookings" page, and see booking statistics on the "Booking Statistics" page.
* If you're using multiple sites, do this only on the back-end site.



TROUBLESHOOTING
==============================
If you have any troubles, you may contact the maintainers directly.



TECHNICAL NOTES
==============================
Content types are created by the following modules (through install configs):

* abookings: addon, line_item, promo
* bookable_unit: bookable_unit
* booking: booking
* season: season


MAINTAINERS
==============================
 * Dane Rossenrode, founder and owner of Touchdreams (https://touchdreams.co.za)