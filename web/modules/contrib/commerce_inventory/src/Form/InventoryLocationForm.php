<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Inventory Location edit forms.
 *
 * @ingroup commerce_inventory
 */
class InventoryLocationForm extends ContentEntityForm {

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
    /* @var $entity \Drupal\commerce_inventory\Entity\InventoryLocation */
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'core_extend/form';
    $form['#theme'] = ['core_extend_entity_form'];

    // Create advanced tabs section.
    $form['advanced'] = [
      '#attributes' => ['class' => ['entity-meta']],
      '#type' => 'container',
      '#weight' => 99,
    ];

    // Entity author information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['entity-form-author'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    if (isset($form['user_id'])) {
      $form['user_id']['#group'] = 'author';
    }

    if (!$this->entity->isNew()) {
      $form['meta'] = [
        '#group' => 'advanced',
        '#weight' => -100,
        '#attributes' => ['class' => ['entity-meta__header']],
        '#type' => 'container',
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

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Inventory Location.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Inventory Location.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity->toUrl('inventory'));
  }

}
