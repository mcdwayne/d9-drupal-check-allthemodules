# Description

This module provides functionality to create a single page website.
It allows you to automatically create a single page from a menu. 
The module will render all the content from the links 
that are configured in the menu.It will then override the menu links 
so that they refer to an anchor instead of a new page.

# Installation

To install this module, do the following:

1. Extract the tar ball that you downloaded from Drupal.org.

2. Upload the entire directory and all its contents to your modules directory.

# Configuration

The Drupal 8 version of this module includes an optional dependency 
on the Link Attributes module. You can use this module without installing 
Link Attributes unless you want to include items in the menu 
that should not be included in the single page, such as a link to the Contact Us page.

To enable and configure this module do the following:

1. Go to Admin -> Extend, and enable Single Page Site.

2. Go to Configuration -> System -> Single Page Site for the module configuration 
   (/admin/config/system/single-page-site)
   
   * Choose the menu which you want to create a single page for
   * Specify the class/id of the menu wrapper you want to create your single page from. 
      Note that this is theme-specific. 
      For example, if you are going to use the Main Menu, 
      the id for this menu is #block-themename-main-menu.
   * If you have Link Attributes installed, you have the option to define the class(es) 
     of the menu items that will be included in the single page navigation. 
     This can be anything, but must be globally unique. 
     If you leave this field empty, or Link Attributes is not enabled, 
     all menu items will be included.
   * Specify the title of your single page  
   * Specify the class that will be assigned to the Title of each page in your single page navigation. 
     For example, you might assign h2, so that each section heading is rendered as h2.
   * If your single page is intended to be used as your site's homepage, check the Homepage option. 
     This will replace your current home page with your single page.
   * If you have Link Attributes installed, and you want to render content on your single page but don't want 
     the menu item to show up in your menu, navigate to the menu item and give it the class "hide".
   * Go to /single-page-site and enjoy your one-pager.