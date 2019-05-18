Error pages such as 404 are most of the time poorly eloquent
 and strongly anxiogenic to the end-users.

This module aims to bring such pages reassuringness and peacefulness.

Generate a customizable image and a message for http status code error 
such as 404 and 403.
The module modifies the content region 
without affecting the other regions of that pages.

This is particularely relevant when working on multisites mode and wanting to keep each sites 
their own style identities in the header and footer.

This version provide:
- the logic to hook 404 and 403 error page content
- the possibly to change error messages from the back office: /admin/config/statuscode/config
- a default displayed image, showing a robot with its error code.

what should be done: 
- the responsiveness is perfectible

what could be done:
- make it working for maintenance pages and other possible error codes
- allowing full html messages to be able to redirect end-users to other pages of the site.
- display log messages to the admins to pinpoint potential problems 
- move the configuration at the /admin/config/system/site-information existing route, through route subscriber
- the possiblility to choose between a set of images, theme or tiles to display , way of displaying things.
-front-end developper or integrator could implement fancy CSS or JS animations