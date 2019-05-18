<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType;

use Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface;
use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Inventory Adjustment type plugins.
 */
abstract class InventoryAdjustmentTypeBase extends PluginBase implements InventoryAdjustmentTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPrepositionLabel() {
    return $this->pluginDefinition['label_preposition'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedPrepositionLabel() {
    return $this->pluginDefinition['label_related_preposition'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSentenceLabelTemplate() {
    return $this->pluginDefinition['label_sentence_template'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSentenceLabelReplacements(InventoryAdjustmentInterface $adjustment, $link_entities = FALSE) {
    $replacements = [
      '@item' => t('(Missing item)'),
      '@location' => t('(Missing location)'),
      '@purchasable_entity' => t('(Missing purchasable)'),
      '@related_location' => t('(Missing location)'),
      '@adjustment_verb' => $this->getVerbLabel(),
      '@adjustment_preposition' => $this->getPrepositionLabel(),
      '@related_preposition' => $this->getRelatedPrepositionLabel(),
    ];

    if ($item = $adjustment->getItem()) {
      $replacements['@item'] = ($link_entities) ? $item->toLink()->toString() : $item->label();
      $replacements['@location'] = $item->getLocationLabel($link_entities);
      $replacements['@purchasable_entity'] = $item->getPurchasableEntityLabel($link_entities);
    }

    if ($this->hasRelatedAdjustmentType() && $adjustment->hasRelatedAdjustment() && $adjustment->getRelatedAdjustment()->getItem() instanceof InventoryItemInterface) {
      if ($link_entities) {
        $replacements['@related_location'] = $adjustment->getRelatedAdjustment()->getItem()->getLocation()->toLink()->toString();
      }
      else {
        $replacements['@related_location'] = $adjustment->getRelatedAdjustment()->getItem()->getLocation()->label();
      }
    }

    return $replacements;
  }

  /**
   * {@inheritdoc}
   */
  public function getSentenceLabel(InventoryAdjustmentInterface $adjustment, $link_entities = FALSE) {
    $replacements = self::getSentenceLabelReplacements($adjustment, $link_entities);
    return $this->t(self::getSentenceLabelTemplate(), $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public function getVerbLabel() {
    return $this->pluginDefinition['label_verb'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasRelatedAdjustmentType() {
    return (isset($this->pluginDefinition['related_adjustment_type']) && !empty($this->pluginDefinition['related_adjustment_type']));
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedAdjustmentTypeId() {
    return $this->pluginDefinition['related_adjustment_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedAdjustmentType() {
    if ($this->hasRelatedAdjustmentType()) {
      // @todo set service to a variable on the adjustment type.
      return \Drupal::service('plugin.manager.commerce_inventory_adjustment_type')->createInstance($this->pluginDefinition['related_adjustment_type']);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function adjustQuantity($quantity);

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
