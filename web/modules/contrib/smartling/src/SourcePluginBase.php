<?php

/**
 * @file
 * Contains \Drupal\smartling\SourcePluginBase.
 */

namespace Drupal\smartling;

use Drupal\Component\Plugin\PluginBase;


/**
 * Default controller class for smartling source plugins.
 */
abstract class SourcePluginBase extends PluginBase implements SourcePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(SmartlingSubmissionInterface $smartling_item) {
    return t('@plugin item unavailable (@item)', array('@plugin' => $this->pluginDefinition['label'], '@item' => $smartling_item->getRelatedEntityTypeId() . ':' . $smartling_item->getRelatedEntityBundleId() . ':' . $smartling_item->getRelatedEntityId()));
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(SmartlingSubmissionInterface $smartling_item) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemTypes() {
    return isset($this->pluginDefinition['item types']) ? $this->pluginDefinition['item types'] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemTypeLabel($type) {
    $types = $this->getItemTypes();
    if (isset($types[$type])) {
      return $types[$type];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getType(SmartlingSubmissionInterface $smartling_item) {
    return ucfirst($smartling_item->getItemType());
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingLangCodes(SmartlingSubmissionInterface $smartling_item) {
    return array();
  }

}

