<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\commerce_inventory\Entity\InventoryLocation;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\core_extend\Form\EntityEditMultipleForm;

/**
 * Provides a inventory creation confirmation form.
 */
class InventoryItemCreateConfirmForm extends EntityEditMultipleForm {

  /**
   * The current location.
   *
   * @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface
   */
  protected $location;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_inventory_item_create_multiple';
  }

  /**
   * Get location entity.
   *
   * @return \Drupal\commerce_inventory\Entity\InventoryLocationInterface
   *   The Inventory Location entity.
   */
  protected function getLocation() {
    if (is_null($this->location)) {
      if ($this->tempStoreData['location_id']) {
        if ($this->tempStoreData['location_id'] instanceof InventoryLocationInterface) {
          $this->location = $this->tempStoreData['location_id'];
        }
        elseif (is_int($this->tempStoreData['location_id']) || is_string($this->tempStoreData['location_id'])) {
          $this->location = InventoryLocation::load($this->tempStoreData['location_id']);
        }
      }
      elseif ($location = $this->routeMatch->getParameter('commerce_inventory_location')) {
        $this->location = $location;
      }
    }
    return $this->location;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId() {
    return 'commerce_inventory_item';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntities() {
    if ($entity_ids = $this->getEntityIds()) {
      /** @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface $inventory_item_storage */
      $inventory_item_storage = $this->entityTypeManager->getStorage('commerce_inventory_item');
      // Create inventory items from purchasable entities.
      return $inventory_item_storage->createMultiple($this->getLocation(), parent::getEntityTypeId(), $entity_ids);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getInlineEntityFormMode() {
    return 'create_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($location = $this->getLocation()) {
      return $location->toUrl('inventory');
    }
    return $this->getLocation();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Add inventory to %location', ['%location' => $this->getLocation()->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->formatPlural(count($this->getEntityIds()), 'Add this purchasable item to the inventory?', 'Add these purchasable items to the inventory?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Add to inventory');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSubmitMessage($successful_count) {
    $params = [
      '@entity_label' => $this->getEntityType()->getCountLabel($successful_count),
      '@location' => $this->getLocation()->label(),
    ];
    return $this->t('Added @entity_label to @location.', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();

    // Create inventory items.
    if ($form_state->getValue('confirm') && $element['#id'] == 'edit-submit') {
      parent::submitForm($form, $form_state);
    }

  }

}
