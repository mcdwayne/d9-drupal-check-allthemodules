# Domain Menu
##About
This module provides ability to administer menus per domain, in a similar way to what domain_access does to nodes.

## Provided Permission compared to menu permissions
The Provided permission is largely inspired by domain_access and what it makes possible for nodes (except for that menus
 don't have bundles, so mapping isn't exactly 1:1 with nodes).

### Administer Menus (from core menu)
Allows user to edit, add, delete any menu site-wide regardless of their assigned domain(s) and regardless of the menus
domain(s). in an sense it's like "edit any content" permission

###Administer menus and menu items on assigned domains:
Allows a user to edit menus assigned to their relevant domains, and items belonging to those menus (full crud on menu items) they cannot however delete those menus or create new menus. this is in a sense like "edit any content on assigned domains" from domain access module.
