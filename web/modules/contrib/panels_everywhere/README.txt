Panels Everywhere
-----------------
Takes over the page.html.twig and places layout configurations in layouts.


Requirements
--------------------------------------------------------------------------------
* Drupal core 8.3.x releases only
  This release of the module is incompatible with core 8.2.x or older. Also,
  because 8.3.x is no longer officially supported, incompatibilities with it may
  not be resolved.
* Panels 8.x-4.x releases only
  https://www.drupal.org/project/panels
* Page Manager 8.x-4.x releases only
  https://www.drupal.org/project/page_manager
* CTools 8.x-3.x releases only
  https://www.drupal.org/project/ctools


Installation
--------------------------------------------------------------------------------
Visit "/admin/modules" and install Panels Everywhere through the UI. This will
turn on the dependencies listed above.

Turning on the "Page Manager UI" module will allow users to see the new site
template.


Usage
--------------------------------------------------------------------------------
After the module is enabled, a new page will show on the page manager edit page
that is disabled. "/admin/structure/page_manager"

Users can now edit that page and add "Main Page Content" to the layout, as well
as other pieces of the site required for the site's page template.


What is working
--------------------------------------------------------------------------------
So far the site template gets taken over and elements can be moved to different
regions through the dropdown in page manager.


Credits / Contact
--------------------------------------------------------------------------------
Currently maintained by Damien McKenna [1]. Drupal 8 port by David Snopek [2]
and Evan Jenkins [3]. Originally written by Earl Miles [4] with many
contributions by Claes Gyllensvärd [5].

Ongoing development is sponsored by Mediacurrent [6].

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  http://drupal.org/project/issues/panels_everywhere


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/u/damienmckenna
2: https://www.drupal.org/u/dsnopek
3: https://www.drupal.org/u/evanjenkins
4: https://www.drupal.org/u/merlinofchaos
5: https://www.drupal.org/u/letharion
6: http://www.mediacurrent.com/
