## INTRODUCTION

The MarkJS search module allows a site administer to create a block that exposes a way for the site user to search for keywords within the page context. Similar to how the Find (Ctr+F) feature works in the browser.

## INSTALLATION

 * Install as usual, see
   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8 for further
   information.
 
 * Navigate to `/admin/config/search/markjs-search`.

 * Create a MarkJS profile with your desired configurations.
 
 * Navigate to `/admin/structure/block`.
 
 * Place a `MarkJS Search` block in any layout region. Configure the block to use the MarkJS profile that you've created in the previous step. Input a search context selector, which allows you to narrow down the scope on what is searched.
 
 ## DEVELOPERS
 Check out the [MarkJS](https://markjs.io/) documentation for instructions/examples on the available API.
