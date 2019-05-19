<?php

namespace Drupal\title;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Allow management of the title module config.
 */
class BundleConfigManager {

  /**
   * @param EntityTypeManagerInterface $entityTypeManager
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Set the enabled/disabled status of a bundle.
   *
   * @param string|object $bundle
   *   The bundle name or object to manipulate.
   * @param boolean $value
   *   The value of set.
   */
  public function setEnabled($bundle, $value) {
    // In the #entity_builders function, no values can be saved against a bundle
    // which doesn't originate from the hook's paramaters. Support bundle names
    // or passed in NodeType objects as a result.
    if (is_string($bundle)) {
      $bundle = $this->loadBundle($bundle);
    }
    $bundle->setThirdPartySetting('title', 'display_configurable_title', $value);
    $bundle->save();
  }

  /**
   * Check the enabled/disabled status of a bundle.
   *
   * @param string $bundle_name
   *   The bundle to lookup.
   *
   * @return boolean
   *   The enabled or disabled status of the bundle.
   */
  public function getEnabled($bundle_name) {
    $bundle = $this->loadBundle($bundle_name);
    return $bundle->getThirdPartySetting('title', 'display_configurable_title');
  }

  /**
   * Set the region that a title is displayed in for a bundle and view mode.
   *
   * @param string $bundle
   *   The bundle that will be manipulated.
   * @param string $region
   *   The region to move the title field into.
   */
  public function setTitleRegion($bundle, $view_mode, $region) {
    $display_config = $this->loadDisplay($bundle, $view_mode);
    // Only change the region on display configurations that have been enabled.
    if (empty($display_config)) {
      return;
    }
    if ($region === TITLE_DISPLAY_REGION_DISABLED) {
      $display_config->removeComponent('title');
    }
    elseif ($region === TITLE_DISPLAY_REGION_ENABLED) {
      $display_config->setComponent('title', ['weight' => -5]);
    }
    $display_config->save();
  }

  /**
   * Set the field formatter for a
   *
   * @param string $bundle
   *   The bundle settings will be updated on.
   * @param string $view_mode
   *   The view mode to change the field formatter on.
   * @param string $formatter
   *   The formatter to change to.
   * @param array $settings
   *   The settings for that formatter.
   */
  public function setTitleFieldFormatter($bundle, $view_mode, $formatter, $settings) {
    $display = $this->loadDisplay($bundle, $view_mode);
    if (!$display) {
      return;
    }
    $display->setComponent('title', [
      'type' => $formatter,
      'settings' => $settings,
      'weight' => -5,
    ]);
    $display->save();
  }

  /**
   * Setup the state for a new bundle to match the rendering of core.
   *
   * @param $bundle
   *   The bundle to setup.
   */
  public function setupBundleState($bundle) {
    $this->setTitleFieldFormatter($bundle, 'teaser', 'linked_and_wrapped', [
      'tag' => 'h2',
      'linked' => '1',
    ]);
    $this->setTitleFieldFormatter($bundle, 'full', 'linked_and_wrapped', [
      'tag' => 'h1',
      'linked' => '0',
    ]);
    $this->setTitleFieldFormatter($bundle, 'default', 'linked_and_wrapped', [
      'tag' => 'h1',
      'linked' => '0',
    ]);
  }

  /**
   * Hide the title on all view modes when of a bundle when disabling.
   *
   * @param $bundle
   *   The bundle to disable the title field for.
   */
  public function tearDownBundleState($bundle) {
    foreach ($this->getViewModeIds('node') as $view_mode) {
      // This is the default state of the title field defined in
      // Node::baseFieldDefinitions(). Matching the default state is required
      // for things like the page title.
      $this->setTitleRegion($bundle, $view_mode, TITLE_DISPLAY_REGION_ENABLED);
      $this->setTitleFieldFormatter($bundle, $view_mode, 'string', []);
    }
  }

  /**
   * Get all of the view mode IDs for an entity type.
   *
   * @param string $entity_type
   *   The entity type to load IDs for.
   *
   * @return array
   *   An array of view mode IDs.
   */
  public function getViewModeIds($entity_type) {
    $ids = [];
    $view_modes = $this->entityTypeManager->getViewModes('node');
    foreach ($view_modes as $view_mode) {
      $ids[] = str_replace($entity_type . '.', '', $view_mode['id']);
    }
    $ids[] = 'default';
    return $ids;
  }

  /**
   * Load the config entity which represents a bundle.
   */
  public function loadBundle($bundle) {
    $manager = $this->entityTypeManager;
    return $manager->getStorage('node_type')->load($bundle);
  }

  /**
   * Load a display object.
   *
   * @param string $bundle
   *   The bundle to load.
   * @param string $view_mode
   *   The view mode to load.
   *
   * @return object
   *   The display config object.
   */
  public function loadDisplay($bundle, $view_mode) {
    return $this->entityTypeManager->getStorage('entity_view_display')
      ->load('node.' . $bundle . '.' . $view_mode);
  }
}
