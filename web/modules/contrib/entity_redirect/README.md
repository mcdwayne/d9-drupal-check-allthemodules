<h1>Entity Redirect</h1>
<h2>DESCRIPTION</h2>
Adds a configurable redirect after saving a node or other entity. The redirect is configurable per bundle. Also, given sufficient permissions (and presuming it is enabled for that specific content/bundle), individual users can configure their own redirects (on their <em>profile edit page</em>).

Four different types of redirection are provided:
<ul>
  <li>Default: This will not impact the entity but will just go to the default. </li>
  <li>Add Form: Just redirects to a new add form for whatever content type/entity was just saved.</li>
  <li>Local Url: provide a local url in the form of /about to go to any page on the site.</li>
  <li>External Url: Same as local url but to any external location. <em>Note</em>: this is only available to users with sufficient permission (the set external entity redirects permission to be exact) or the site administrator.</li>
</ul>

You can also control whether this occurs only on saving a new entity or for both creating and editing an entity.

<em>Note:</em> depending on permissions, the redirect will also occur for anonymous users so if using the <em>Local Url</em> option make sure that they have permission to access the destination if they can add/edit the content type/entity. This is a relatively rare site configuration so in most cases you can safely ignore that.

<h2> MOTIVATION/USE CASES</h2>
Sometimes the best workflow is to add a lot of entities in a row so you want to return directly to the add entity form after each one. Another use case is taking users to a thank-you page after contributing an image.

<h2>REQUIREMENTS</h2>

Drupal 8 is required, Drupal 8.2.x or higher is suggested.

<h2>INSTALLATION</h2>
Install as you would normally install a contributed Drupal module. See the <a href='http://drupal.org/documentation/install/modules-themes/modules-8'>Drupal 8 instructions</a> if required in the Drupal documentation for further information.

<h2>CONFIGURATION</h2>
Configuration can be accessed for each supported entity bundle on the edit page for that entity type. For example for the Node type Article that would be at /admin/structure/types/manage/article. With sufficient permissions personalization can done per user on their profile edit page.

<h2>FAQ</h2>
Any questions? Ask away on the issue queue. Alternatively feel free to contact Nick via Twitter (@NickWilde1990), or email (nick@briarmoon.ca).

This project has been sponsored by:
<h3><a href="http://design.briarmoon.ca">BriarMoon Design</a></h3>
   Full service web development and design studio. Specializing in responsive, secure, optimized Drupal sites. BriarMoon Design can help you with all your Drupal needs including installation, module creation or debugging, themeing, customization, and hosting.
