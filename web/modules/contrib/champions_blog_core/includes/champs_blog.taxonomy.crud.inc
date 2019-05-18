<?php

/**
 * We need to add a new path alias programmatically whenever a new topic, tag, or
 * user is added so that our view filters work correctly. It is better to filter
 * items in a view based on their ID rather than based on their name - there is
 * a ton of issues concerning the replacement of dashes, spaces and uppercase
 * / lowercase letters when using contextual filters.
 * Super annoying. - the solution is to use only the ID to filter by.
 * This is THE CORRECT pattern to employ due to current issues with Drupal views.
 */

// TODO: you also need a 'when a new user is added?' - should user be separated out,
// QUESTION: if there are lots of them? - how to deal with this?

/**
 * !CREATION hook
 */
function champs_blog_taxonomy_term_insert(Drupal\Core\Entity\EntityInterface $entity) {

  // TODO: there should be a check here for if the bundle of the taxonomy is one of the ones
  // we wish to create path aliases for.

  // Get the taxonomy vocabulary
  $taxonomyBundle = $entity->bundle();

  // Get the taxonomy name
  $taxonomyName = $entity->getName();

  // Before giving the name to pathauto, clean the string
  $cleanedTaxonomyName = \Drupal::service('pathauto.alias_cleaner')->cleanString($taxonomyName);

  // Get the ID of the taxonomy
  $taxonomyId = $entity->id();

  // Set the default langcode
  $langcode = 'English';

  // If the vocabulary is 'cb_topic', create URL's of /blog/%
  if ($taxonomyBundle == 'cb_topic') {
    $system_path = "/blog/${taxonomyId}";
    $path_alias = "/blog/${cleanedTaxonomyName}";
    $path = \Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
  }

  // If the vocabulary is 'cb_tag', create URL's of /blog/tag/%
  if ($taxonomyBundle == 'cb_tag') {
    $system_path = "/blog/tag/${taxonomyId}";
    $path_alias = "/blog/tag/${cleanedTaxonomyName}";
    $path = \Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
  }
}

/**
 * !PRESAVE HOOK
 */


function champs_blog_taxonomy_term_presave(Drupal\Core\Entity\EntityInterface $entity) {

  // Get the taxonomy vocabulary
  $taxonomyBundle = $entity->bundle();

  // Get the taxonomy name
  $taxonomyName = $entity->getName();

  // Before giving the name to pathauto, clean the string
  $cleanedTaxonomyName = \Drupal::service('pathauto.alias_cleaner')->cleanString($taxonomyName);

  // Get the ID of the taxonomy
  $taxonomyId = $entity->id();

  // Set the default langcode
  $langcode = 'English';

  // check if the path alias already exists for that taxonomy term
  if ($taxonomyBundle == 'cb_topic') {
    $system_path = "/blog/${taxonomyId}";
    $path_alias = "/blog/${cleanedTaxonomyName}";
    // $path = \Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
    $path = \Drupal::service('path.alias_storage')->aliasExists($path_alias, $langcode);
    if ($path) {
      $deleted = \Drupal::service('path.alias_storage')->delete($path_alias);
    }
  }
}


/**
 * !UPDATE HOOK
 */


function champs_blog_taxonomy_term_update(Drupal\Core\Entity\EntityInterface $entity) {

  // Get the taxonomy vocabulary
  $taxonomyBundle = $entity->bundle();

  // Get the taxonomy name
  $taxonomyName = $entity->getName();

  // Before giving the name to pathauto, clean the string
  $cleanedTaxonomyName = \Drupal::service('pathauto.alias_cleaner')->cleanString($taxonomyName);

  // Get the ID of the taxonomy
  $taxonomyId = $entity->id();

  // Set the default langcode
  $langcode = 'English';

  // check if the path alias already exists for that taxonomy term
  if ($taxonomyBundle == 'cb_topic') {
    $system_path = "/blog/${taxonomyId}";
    $path_alias = "/blog/${cleanedTaxonomyName}";
    // $path = \Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
    $path = \Drupal::service('path.alias_storage')->aliasExists($path_alias, $langcode);
    if (!$path) {
      $path = \Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
    }
  }
}

function champs_blog_taxonomy_term_delete(Drupal\Core\Entity\EntityInterface $entity) {

  // Get the taxonomy vocabulary
  $taxonomyBundle = $entity->bundle();

  // Get the taxonomy name
  $taxonomyName = $entity->getName();

  // Before giving the name to pathauto, clean the string
  $cleanedTaxonomyName = \Drupal::service('pathauto.alias_cleaner')->cleanString($taxonomyName);

  // Get the ID of the taxonomy
  $taxonomyId = $entity->id();

  // Set the default langcode
  $langcode = 'English';

  // check if the path alias already exists for that taxonomy term
  if ($taxonomyBundle == 'cb_topic') {
    $system_path = "/blog/${taxonomyId}";
    $path_alias = "/blog/${cleanedTaxonomyName}";
    // $path = \Drupal::service('path.alias_storage')->save($system_path, $path_alias, $langcode);
    $path = \Drupal::service('path.alias_storage')->aliasExists($path_alias, $langcode);
    if ($path) {
      $deleted = \Drupal::service('path.alias_storage')->delete($path_alias);
    }
  }
}