<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Inventory Item edit forms.
 *
 * @ingroup commerce_inventory
 */
class InventoryItemForm extends ContentEntityForm {

  /**
   * Gets the current entity's bundle definition.
   *
   * @return array|null
   *   The bundle definition.
   */
  protected function getBundleDefinition() {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->entity->getEntityTypeId());
    if (array_key_exists($this->entity->bundle(), $bundles)) {
      return $bundles[$this->entity->bundle()];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_inventory\Entity\InventoryItem */
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core_extend/form';
    $form['#theme'] = ['core_extend_entity_form'];

    // Create advanced tabs section.
    $form['advanced'] = [
      '#attributes' => ['class' => ['entity-meta']],
      '#type' => 'container',
      '#weight' => 99,
    ];

    if (!$this->entity->isNew()) {
      $form['meta'] = [
        '#group' => 'advanced',
        '#weight' => -100,
        '#attributes' => ['class' => ['entity-meta__header']],
        '#type' => 'container',
      ];

      $form['meta']['quantity'] = [
        '#type' => 'item',
        '#title' => t('Quantity'),
        '#markup' => $this->entity->getQuantity(),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__title', 'container-inline']],
      ];
      $form['meta']['quantity_available'] = [
        '#type' => 'item',
        '#title' => t('Quantity available'),
        '#markup' => $this->entity->getQuantity(true),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__quantity_available', 'container-inline']],
      ];

      $form['meta']['status'] = [
        '#type' => 'item',
        '#title' => t('Status'),
        '#markup' => $this->entity->isActive() ? $this->t('Active') : $this->t('Inactive'),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__status', 'container-inline']],
      ];

      if ($bundle_definition = $this->getBundleDefinition()) {
        $form['meta']['provider'] = [
          '#type' => 'item',
          '#title' => t('Provider'),
          '#markup' => $bundle_definition['label'],
          '#access' => !$this->entity->isNew(),
          '#wrapper_attributes' => ['class' => ['entity-meta__provider', 'container-inline']],
        ];
      }

      $form['meta']['provider_status'] = [
        '#type' => 'item',
        '#title' => t('Provider status'),
        '#markup' => $this->entity->isValid() ? $this->t('Valid') : $this->t('Invalid'),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__provider_status', 'container-inline']],
      ];

      $purchasable_entity_type = $this->entity->getPurchasableEntityType();
      $form['meta']['purchasable_entity_type'] = [
        '#type' => 'item',
        '#title' => t('Purchasable Type'),
        '#markup' => ($purchasable_entity_type) ? $purchasable_entity_type->getLabel() : t('Missing'),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__purchasable_entity_type', 'container-inline']],
      ];
      $form['meta']['purchasable_entity'] = [
        '#type' => 'item',
        '#title' => t('Purchasable'),
        '#markup' => $this->entity->getPurchasableEntityLabel(),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__purchasable_entity', 'container-inline']],
      ];

      $form['meta']['location'] = [
        '#type' => 'item',
        '#title' => t('Location'),
        '#markup' => $this->entity->getLocationLabel(),
        '#access' => !$this->entity->isNew(),
        '#wrapper_attributes' => ['class' => ['entity-meta__location', 'container-inline']],
      ];

      if (array_key_exists('location_id', $form)) {
        $form['location_id']['#access'] = FALSE;
        $form['location_id']['#disabled'] = TRUE;
        $form['location_id']['#parent'] = 'advanced';
      }

      if (array_key_exists('purchasable_entity', $form)) {
        $form['purchasable_entity']['#access'] = FALSE;
        $form['purchasable_entity']['#disabled'] = TRUE;
        $form['purchasable_entity']['#parent'] = 'advanced';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_inventory\Entity\InventoryItem */
    $entity = $violations->getEntity();

    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    if ($entity->isNew()) {
      $field_names = [
        'location_id',
        'purchasable_entity',
      ];
      foreach ($violations->getByFields($field_names) as $violation) {
        list($field_name) = explode('.', $violation->getPropertyPath(), 2);
        $form_state->setErrorByName($field_name, $violation->getMessage());
      }
    }

    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Inventory Item.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Inventory Item.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity->toUrl());
  }

}
