<?php


/**
* Implements hook_preprocess_taxonomy_term();
* Sets up the URL's to be used within the blog
*/
function champs_blog_preprocess_taxonomy_term(&$variables) {

  // Preprocess the topic / tag taxonomies to point to the right URL's.
  $term = $variables['elements']['#taxonomy_term'];
  if ($term->bundle() == 'cb_topic') {
    $id = $term->id();
    $url = '/blog/' . $id;
    $variables['url'] = $url;
  }
  if ($term->bundle() == 'cb_tags') {
    $id = $term->id();
    $url = '/blog/tag/' . $id;
    $variables['url'] = $url;
  }
}


/**
* Implements hook_preprocess_paragraph().
* Takes the field value of Classes inside the CP Element Paragraph and passes
* it down to the twig template immediately.
*/
function champs_blog_preprocess_paragraph(&$variables) {
 if ($variables['paragraph']->field_classes) {
     $custom_classes = $variables['paragraph']->field_classes->value;
     $exploded_classes = explode(" ", $custom_classes);
     $variables['champions41_custom_classes'] = $exploded_classes;
 }
}


/**
* Implements theme_preprocess_block().
* Disable the title block on the Blog Content type only. This is due to the
* clash between the URL of individual blog post '/blog/%blog_name' and the blog topic
* view, which has URL of '/blog/%topic_name'. Couldn't find another way to
* solve this. This shouldn't be needed in most other sites? How does this work?
* Too many edge cases where this shouldn't be enforced?
*/
function champs_blog_preprocess_block(&$variables) {
  // Remove page title block on 'article' content type.
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->getType() == 'cb_blog') {
    if ($variables['elements']['#id'] == 'pagetitle') {
      $variables['content'] = [];
    }
  }
}

