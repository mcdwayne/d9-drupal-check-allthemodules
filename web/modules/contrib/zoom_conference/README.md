Zoom Conference
===============
Provides basic functionality for creating meetings and registrations for Zoom teleconferences, via their API (v2).

This is the Drupal 8 heir to the Zoom API (https://www.drupal.org/project/zoomapi) module, which implemented version 1 of the API, and did not create any further functionality to utilize it.

This module doesn't currently implement a particularly complex workflow for meetings. It is designed for a scenario where administrators create meetings, and any users (with sufficient permissions) can register for free. After meetings conclude, any recordings that exist can automatically be retrieved via a Web Hooks endpoint (/zoom_conference/webhooks).

Development of this module is sponsored by Cheeky Monkey Media (https://cheekymonkeymedia.ca/) and First Nations Financial Management Board (https://fnfmb.com/).

Installation
-------------
This module needs to be installed via Composer, which will download the required libraries. Notable requirements include JWT. There's a complete listing of required Drupal modules in the info yml file.

Configuration
-------------
After installation, navigate to (/admin/config/zoom_conference/adminsettings) to set up the API.

If you intend to use Web Hooks, there's additional configuration at Zoom that is required. @see: https://developer.zoom.us/docs/webhooks/

Set up the permissions for CRUD operations on the module's content types according to your requirements.

Usage
-----
This module created two content types, Zoom Meeting and Zoom Registrant.

Once the API is configured, a user with sufficient permissions can create a new meeting via (/node/add/zoom_meeting). A corresponding meeting should automatically be created at Zoom via the API. The meeting display will include a registration button to allow users to register.

Editing meetings will update them at Zoom. Deletion of either meetings or registrations will cancel them at Zoom.

There is an administrative listing of meetings at (/admin/content/zoom_meetings).

A separate listing, intended for users, is available at (/zoom_meetings).

Both of the above are fairly bare-bones at this point.

TODO
----

There's a long list of potential enhancements for this module.

1. Move content types into separate modules. This will allow people to build out their own work flows as needed.
2. Support for more of Zoom's functionality. Examples include recurring meetings.
3. Integration with commerce, to permit registration fees.
4. Enhancements to the display of the content types (particularly vis-a-vis cloud recordings).
5. Many items documented inline in code with TODO statements.
