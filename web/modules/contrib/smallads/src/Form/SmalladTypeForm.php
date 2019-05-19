<?php

namespace Drupal\smallads\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form controller for category edit forms.
 */
class SmalladTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $smallad_type = $this->entity;

    $form['label'] = array(
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#maxlength' => 16,
      '#default_value' => $smallad_type->label(),
      '#required' => TRUE,
    );
    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $smallad_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\smallads\Entity\SmalladType::load',
      ),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$smallad_type->isNew(),
    );
    $form['label_plural'] = array(
      '#title' => t('Label pluralised'),
      '#description' => t('Used for the menu item and other titles'),
      '#type' => 'textfield',
      '#maxlength' => 16,
      '#default_value' => $smallad_type->labelPlural(),
      '#required' => TRUE,
    );
    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $smallad_type->getDescription(),
      '#description' => t('Describe this smallad type. The text will be displayed on the <em>Smallad types</em> administration overview page'),
    );

    $form['weight'] = array(
      '#title' => t('Weight'),
      '#type' => 'weight',
      '#default_value' => $smallad_type->getWeight(),
      '#description' => t('The default weight of the menu item'),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $smallad_type = $this->entity;
    $smallad_type->save();

    $form_state->setRedirectUrl($smallad_type->urlInfo('collection'));
    // If the plural has changed we need to rebuild the menu.
    \Drupal::service('router.builder')->rebuild();
  }

}
