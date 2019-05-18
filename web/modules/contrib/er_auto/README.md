# Entity Refrence Automation

A drupal module to automate the selection of content in forms. Using Entity
Refrence fields on a entity, this module will be able to update the values of
Entity Refrence fields on the page, when that entity is (de)selected.

## About

This module is developed by Rich Gerdes and Anton Sarukhanov for the [Rutgers
University Office of Information Technology](https://oit.rutgers.edu). For more
information, please contact the [OIT Webmasters]
(mailto:webmaster@oit.rutgers.edu).

## Example

1) On a blog, you want content tagged with "Party" to also be also have the
category "Event" (two seperate fields, that work in defferent ways).

2) Access control to a site is handled by roles, but default access for a node
should be generated based off the category that the content is tagged with.
Access can then be controlled more granularly, by removing roles, but the user
is provided with defaults when creating the content.
