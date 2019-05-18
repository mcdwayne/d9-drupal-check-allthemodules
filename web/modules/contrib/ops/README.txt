-- SUMMARY --

On page search module provide functionality to search on single page. It is
replacement of CTRL+F of browser. Some time user want to search on current opened
page then this module is very useful. In mobile devices (webview) apps this 
module can help you a lot.

-- REQUIREMENTS --
   Not any required

-- INSTALLATION --

* Install as usual, see https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further information.

-- CONFIGURATION --

* Configure user permissions in Administration » People » Permissions:

  - on page search

    Users in roles with the "on page search" permission will see
    the admin settings of ops module.

* Module create a block of search textbox , assign that block in your desired
region.

-- CUSTOMIZATION --

* Access menu from here : admin/config/search/ops/settings

  If you want to change Background/Text color of the searched text then you can
  change it from setting form. It takes valid hex color value of 6 digit.

* If you want to override css style of search text , then create style of
highlight class.

* If you don't want to use block by this module then you can create a textbox
with below source code:

    <input type="textarea" placeholder="Search Here" 
    class="form-control ops-text-search" 
    id="ops-text-search"
    >

    Note*: Please don't change Id.

-- CONTACT --

Current maintainers:
* Rajveer singh https://www.drupal.org/u/rajveergang
  email : rajveer.gang@gmail.com
