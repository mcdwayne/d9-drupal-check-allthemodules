-- How to use Ajax links API --

- METHOD 1 -

In module config page, specify the Classes/Ids to trigger Ajax. 

Target DIV will be default CSS selector defined, you can change default CSS 
Selector from module config page.

Example : 
<div class="tabs"><a href="node/add/page">Add page</a></div> . You can ajaxify 
this link by adding .tabs a in module config page.


- METHOD 2 -

in your module ,

<?php
$service = \Drupal::service('ajax_links_api.ajax_link');
$link = $service->lAjax($title, $path, $target, $link_options);
?>

* $title: Title.
* $path : Drupal path.
* $target (optional): ID/CLASS of DIV to be replaced. This will override 
  Default CSS Selector defined in module config page.
* $link_options (optional): Provide attributes to the link in an array format.


Example :
<?php
$service = \Drupal::service('ajax_links_api.ajax_link');
$link = $service->lAjax("add page", "/node/add/page", "#content", array('#attributes' => 'class' => array('ajax-links-api')));
?>


- METHOD 3 -

Add class="ajax-link" to any link. Target div will be default CSS 
selector defined. You can change default CSS Selector from module config page 
or override target by specifying rel="".

Example : 
<a class="ajax-link" href="/node/add/page" rel="#content">Add page</a>


-- Developer Notes --

Override tpl : 
Developer can add/remove any variables by copying html--ajax.html.twig and 
page--ajax.html.twig to their theme. All variables available to
html--ajax.html.twig or page--ajax.html.twig can be used. 
In case you want to override page--ajax.html.twig, for eg: for path /user/, you 
can create page--user--ajax.html.twig.
Same applicable for html--user--ajax.html.twig.

Upgrade : 
You can upgrade/degrade module anytime by simply overwriting whole folder.

-- DEMO --

Goto YOUR_SITE/ajax-links-api/test
