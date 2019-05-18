<?php

namespace Drupal\real_estate_property\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PropertyTypeForm.
 *
 * @package Drupal\real_estate_property\Form
 */
class PropertyTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $real_estate_property_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $real_estate_property_type->label(),
      '#description' => $this->t("Label for the Property type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $real_estate_property_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\real_estate_property\Entity\PropertyType::load',
      ],
      '#disabled' => !$real_estate_property_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $real_estate_property_type = $this->entity;
    $status = $real_estate_property_type->save();

    switch ($status) {
      case SAVED_NEW:

        // Add a agencies filed to every property entity.
        real_estate_property_add_agencies_field($real_estate_property_type);

        drupal_set_message($this->t('Created the %label Property type.', [
          '%label' => $real_estate_property_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Property type.', [
          '%label' => $real_estate_property_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($real_estate_property_type->toUrl('collection'));
  }

}
