<?php

use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;

/**
 * Alter the meta tags for pages that are not of content entities.
 */
function champs_blog_metatags_alter(array &$metatags, array $context) {


  $views = [
    'cb_view_author',
    'cb_view_blog',
    'cb_view_topic',
    'cb_view_tag'
  ];

  // Whenever the entity is a view page of (cb_view_author) or (cb_view_topic)

  // Only proceed if an entity exists
  if (array_key_exists('entity', $context)) {
    $entity = $context['entity'];

    // Only proceed if view
    if ($entity instanceof Drupal\views\Entity\View) {

      // Get the current page through the query string - the only way I could get this to work.
      // This magic string should ideally be avoided.
      if (array_key_exists("page", $_GET)) {
        $page_number = $_GET['page'];
      } else {
        $page_number = "0";
      }

      if (array_key_exists("canonical_url", $metatags)) {
        if (!$page_number || $page_number == "0") {
          // Get a preg match from ?page until the next ?
          $metatags['canonical_url'] = preg_replace('/\?page=[^?]*(?)/', '', $metatags['canonical_url']);
        }
      }

      // If the page number is present, we should just carry on as usual. If the page number is a
      // "" value, then we should remove the query string value from the canonical URL metatag.


      // Proceed only if the view is the Champions Blog Author view
      if ($entity->getOriginalId() == 'cb_view_author') {

        // Get the current path to extract the contextual filter
        $current_path = \Drupal::service('path.current')->getPath();

        // Get the final piece of the URL (the contextual filter ID)
        $id = basename($current_path);
        try {
          $author = User::load($id);
          if ($author) {

            // get the metatags field on the user account
            if (!$author->get('field_metatags')->isEmpty()) {
            $authorMetatags = $author->get('field_metatags')->getValue();
            $values = $authorMetatags[0]['value'];
            $deserialized = unserialize($values);

            // Give back to the hook to apply to the view
            if ($deserialized) {
              $metatags['title'] = $deserialized['title'];
              $metatags['description'] = $deserialized['description'];
            }
            }

          }
        }
        catch (Exception $e) {

        }
      }
      // Proceed only of the view is the Champions Blog topic or tag view
      if ($entity->getOriginalId() == 'cb_view_topic' || $entity->getOriginalId() == 'cb_view_tag') {

        // Get the current path to extract the contextual filter
        $current_path = \Drupal::service('path.current')->getPath();

        // Get the final piece of the URL (the contextual filter ID)
        $id = basename($current_path);
        try {
          // Since this is the topic view, load the taxonomy term
          $topic = Term::load($id);
          if ($topic) {

            // get the Metatags field on the taxonomy
            if (!$topic->get('field_metatags')->isEmpty()) {
              $topicMetatags = $topic->get('field_metatags')->getValue();
              $values = $topicMetatags[0]['value'];
              $deserialized = unserialize($values);


              // Give back to the hook to apply to the view
              if ($deserialized) {
                $metatags['title'] = $deserialized['title'];
                $metatags['description'] = $deserialized['description'];

              }
            }
          }
        }
        catch (Exception $e){

        }
      }
    }
  }
}


// Move our metatag override hook to last! THIS IS COOL

function champs_blog_module_implements_alter(&$implementations, $hook) {
  switch($hook) {
    case 'metatags_alter':
      $group = $implementations['champs_blog'];
      unset($implementations['champs_blog']);
      $implementations['champs_blog'] = $group;
      break;
  }
}

function champs_blog_page_attachments(array &$page) {

}