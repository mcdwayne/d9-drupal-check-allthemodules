README.txt
==========

Tabbed content for Drupal 8 CKEditor
------------------------
Requires - Drupal 8


Overview:
--------
Adds a new button to Drupal 8's built in CKEditor which allows the user to create tabbed content on their pages.

The styling is very minimal, allowing the developers to overwrite the look easily using CSS.

Ported to Drupal 8 from SebCorbin's "CKEditor Tabber" project (https://www.drupal.org/project/ckeditor_tabber)


INSTALLATION:
--------
1. Install & Enable module
2. Open Administration > Configuration > Content authoring > Text formats and editors (admin/config/content/formats)
3. Edit a text format's settings (for example Basic HTML)
4. Drag n Drop the Add Tab button to the toolbar to show it to the users
5. Scroll down to the bottom to the input "Allowed HTML tags"
6. Find and replace <dl> with <dl class>
   This makes sure CKEditor doesn't remove the class name that Tabber uses



Code structure example:
--------
<section class="ckeditor-tabber-tabs"
  <ul class="tabs-holder">
    <li><a class="tab tab-1 active">Tab 1</a></li>
    <li><a class="tab tab-2">Tab 1</a></li>
  </ul>
  <div class="tabs-content-holder">
    <div class="tab-content tab-content-1 active">
      <p>Tab 1 contents</p>
    </div>
    <div class="tab-content tab-content-2">
      <p>Tab 2 contents</p>
    </div>
  </div>
</section>

