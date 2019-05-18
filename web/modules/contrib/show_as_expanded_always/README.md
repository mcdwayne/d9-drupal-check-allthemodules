# Show as expanded - always

Depending on the implementation of the menu it is necessary to check the
checkbox *"Show as expanded"* all the time - but the default implementation of 
the Drupal *"Add menu item"*-form has false as default value.

## INTRODUCTION
To improve the workflow of the editor, the checkbox *"Show as expanded"* should
be set to *true* if the user creates a new menu item. This is what the module
**Show as expanded - always** does.

## REQUIREMENTS
There are no special requirements. A clean Drupal 8 instance is just perfect
start for **Show as expanded - always**!

## INSTALLATION
1. Install the **Show as expanded - always** module
2. Enjoy!
3. Optional: Configure the module (see CONFIGURATION)

## CONFIGURATION
By default, the *"Show as expanded"*-checkbox will be checked always when
adding a new menu item and independent of the selected menu.

The configuration-page of **Show as expanded - always** allows to enable or
disable this feature for certain menus. This page can be accessed here:

/admin/config/show-as-expanded-always/configuration

The configuration can be exported. If this feature should be used in every
menu - no  configuration is necessary.
