# Redirect Node

Provides a drupal Node type that redirects to an external URL when viewed
directly or when displayed in a menu.

# Implementation

Redirect Node Provides a `redirect` node type, that get redirected by during
drupal boot at the Kernal::Request event. This allows for access control to
be checked and the redirect to be preformed before a significant amount of
drupal page boot strapping happens.

# Menu Display.

Redirect Nodes that are displayed in menu's will act the same as external
links, but allow for access control to the node to control the display of the
menu item. In the browser, js is used to add edit links to the node items, to
allow quick access to the edit pages of the nodes.

# About

This module was developed by the [Rutgers University Office of Information
Technology](https://oit.rutgers.edu). For support or questions, please contact
the OIT Webmaster(mailto:webmaster@oit.rutgers.edu).
