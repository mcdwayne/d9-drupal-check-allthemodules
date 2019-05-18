<?php

/**
 * @file
 * Drush integration for dRealty.
 */

/**
 * Implements hook_drush_command().
 */
function drealty_drush_command() {
  $items = array();

  $items['rets-import'] = array(
    'aliases' => array('ri'),
    'description' => 'Import RETS Listings.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );

  $items['rets-flush'] = array(
    'aliases' => array('rfa'),
    'description' => 'Flush ALL RETS items.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );

  $items['rets-reprocess-images'] = array(
    'description' => "Force the (re)downloading of images.",
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  $items['rets-process-images'] = array(
    'description' => "Process any images that are marked for download.",
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  $items['rets-purge-inactive'] = array(
    'description' => "Purge listings older than two weeks from the system.",
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  $items['rets-force-update'] = array(
    'aliases' => array('rfu'),
    'description' => 'Force the update of the selected drealty entity.',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_LOGIN,
  );
  return $items;
}

/**
 * Implements hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-command>'
 *
 * @param string $section
 * @return string
 */
function drealty_drush_help($section) {
  switch ($section) {
    case 'drush:rets-import':
      return dt('');
    case 'drush:rets-flush':
      return dt('');
    case 'drush:rets-list':
      return dt('');
    case 'drush:rets-status':
      return dt('');
    case 'drush:rets-purge-inactive':
      return dt('');
    case 'drush:rets-reprocess-images':
      return dt('');
  }
}

function drush_drealty_rets_import() {
  // set the time out to infinite
  set_time_limit(0);

  module_load_include('php', 'drealty', 'drealtyDaemon');
  $daemon = new drealtyDaemon();
  $daemon->run();
}

function drush_drealty_rets_process_images() {
  set_time_limit(0);

  module_load_include('php', 'drealty', 'drealtyDaemon');
  $daemon = new drealtyDaemon();
  $daemon->import_images();
}

function drush_drealty_rets_reprocess_images() {
  set_time_limit(0);

  //query for the entities
  $query = new EntityFieldQuery();
  $result = $query
    ->entityCondition('entity_type', 'drealty_listing')
    ->execute();

  if(!empty($result['drealty_listing'])) {
    $listings = array_keys($result['drealty_listing']);
    foreach($listings as $listing_id) {
      if($listing = reset(entity_load('drealty_listing', array($listing_id)))) {
        $listing->process_images = TRUE;
        $listing->save();
        drush_log(dt("Set @label to reprocess images.", array("@label" => $listing->label)));
      } else {
        drush_log(dt("Something went wrong with @id", array("@id" => $listing_id)), "error");
      }
    }
  }
  
  module_load_include('php', 'drealty', 'drealtyDaemon');
  $daemon = new drealtyDaemon();
  $daemon->import_images();
}

function drush_drealty_rets_flush() {
  set_time_limit(0);

  $options = array('drealty_listing' => dt('Drealty Listings'));

  if (module_exists('drealty_office')) {
    $options['drealty_office'] = dt('Drealty Office');
  }
  if (module_exists('drealty_agent')) {
    $options['drealty_agent'] = dt('Drealty Agent');
  }

  $selection = drush_choice($options, dt('Select an entity type to flush'));

  if (!$selection) {
    return;
  }

  $info = entity_get_info($selection);
  
  $bundles = array();
  foreach($info['bundles'] as $key => $bundle) {
    $bundles[$key] = $bundle['label'];
  }
  $bundles['all'] = "All";
  
  $bundle_selection = drush_choice($bundles, dt('Select a class to flush'));
  
  if(!$bundle_selection) {
    return;
  }
  
  
  $query = db_select($selection, "t")
    ->fields("t", array("id"));
  
  if($bundle_selection != 'all') {
    $query->condition('type', $bundle_selection);
  }
  
  $existing_items = $query->execute()->fetchAll();
  
  $confirm = drush_confirm(dt("You are about to flush @count @entity(s) of type @type from the system. This cannot be undone. Are you Sure?", array('@count' => count($existing_items), '@entity' => $selection, '@type' => $bundle_selection)));
  
  if(!$confirm) {
    return;
  }

  foreach ($existing_items as $item) {
    $entity = reset(entity_load($selection, array($item->id)));
    drush_log(dt('Deleting @type - @id', array('@id' => $item->id, '@type' => $selection)), 'success');
    $entity->delete();
    unset($entity);
  }
  unset($existing_items);
}

function drush_drealty_rets_force_update() {
  
  
  $options = array('drealty_listing' => dt('Drealty Listings'));

  if (module_exists('drealty_office')) {
    $options['drealty_office'] = dt('Drealty Office');
  }
  if (module_exists('drealty_agent')) {
    $options['drealty_agent'] = dt('Drealty Agent');
  }

  $selection = drush_choice($options, dt('Select an entity type to flush'));

  if (!$selection) {
    return;
  }
  
  $num_updated = db_update($selection)
    ->fields(array('hash' => ''))
    ->execute();
  drush_log(dt("Forcing update on @num items.", array('@num' => $num_updated)), 'success');
  
}

/**
 * Purge inactive listings from the system.
 */
function drush_drealty_rets_purge_inactive() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'drealty_listing')
      ->propertyCondition('active', 0)
      ->propertyCondition('inactive_date', strtotime('-2 weeks'), '<');

  $result = $query->execute();

  if (count($result)) {
    $ids = array_keys($result['drealty_listing']);

    foreach ($ids as $id) {
      entity_delete('drealty_listing', $id);
    }
  }
}