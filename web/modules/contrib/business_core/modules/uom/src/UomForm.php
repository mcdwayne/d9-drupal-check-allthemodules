<?php

namespace Drupal\uom;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the uom edit forms.
 */
class UomForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add UOM');
    }
    else {
      $form['#title'] = $this->t('Edit %label UOM', ['%label' => $entity->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $entity->label(),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => $entity->isLocked(),
      '#machine_name' => [
        'exists' => ['Drupal\uom\Entity\Uom', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this UOM. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $classes = $this->entityTypeManager->getStorage('uom_class')
      ->loadMultiple();
    $options = array_map(function ($entity) {
      return $entity->label();
    }, $classes);
    $form['class'] = [
      '#title' => $this->t('UOM Class'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $entity->getClass(),
    ];

    $form['conversion_factor'] = [
      '#title' => $this->t('Conversion factor'),
      '#type' => 'number',
      '#default_value' => $entity->getConversionFactor(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $entity->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $uom = $this->entity;
    $insert = $uom->isNew();
    $uom->save();
    $uom_link = $uom->link($this->t('View'));
    $context = ['%title' => $uom->label(), 'link' => $uom_link];
    $t_args = ['%title' => $uom->link($uom->label())];

    if ($insert) {
      $this->logger('uom')->notice('Uom: added %title.', $context);
      drupal_set_message($this->t('Uom %title has been created.', $t_args));
    }
    else {
      $this->logger('uom')->notice('Uom: updated %title.', $context);
      drupal_set_message($this->t('Uom %title has been updated.', $t_args));
    }
  }

}
