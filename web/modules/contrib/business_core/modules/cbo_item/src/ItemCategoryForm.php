<?php

namespace Drupal\cbo_item;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for item category forms.
 */
class ItemCategoryForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add item category');
    }
    else {
      $form['#title'] = $this->t('Edit %label item category', ['%label' => $entity->label()]);
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
      '#machine_name' => [
        'exists' => ['Drupal\cbo_item\Entity\ItemCategory', 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this item category. It must only contain lowercase letters, numbers, and underscores. This name will be used for constructing the URL of the %item-add page, in which underscores will be converted into hyphens.', [
        '%item-add' => $this->t('Add item'),
      ]),
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $id = trim($form_state->getValue('id'));
    // '0' is invalid, since elsewhere we check it using empty().
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t("Invalid machine-readable name. Enter a name other than %invalid.", ['%invalid' => $id]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->set('id', trim($entity->id()));
    $entity->set('label', trim($entity->label()));

    $status = $entity->save();

    $t_args = ['%name' => $entity->label()];

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The item category %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The item category %name has been added.', $t_args));
      $context = array_merge($t_args, ['link' => $entity->link($this->t('View'), 'collection')]);
      $this->logger('item')->notice('Added item category %name.', $context);
    }
  }

}
