<?php

namespace Drupal\advertising_products\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdvertisingProductTypeForm.
 *
 * @package Drupal\advertising_products\Form
 */
class AdvertisingProductTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $advertising_product_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $advertising_product_type->label(),
      '#description' => $this->t("Label for the Advertising Product type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $advertising_product_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\advertising_products\Entity\AdvertisingProductType::load',
      ),
      '#disabled' => !$advertising_product_type->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $advertising_product_type = $this->entity;
    $status = $advertising_product_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Advertising Product type.', [
          '%label' => $advertising_product_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Advertising Product type.', [
          '%label' => $advertising_product_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($advertising_product_type->urlInfo('collection'));
  }

}
