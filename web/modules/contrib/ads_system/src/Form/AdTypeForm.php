<?php

namespace Drupal\ads_system\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdTypeForm.
 *
 * @package Drupal\ads_system\Form
 */
class AdTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ad_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ad_type->label(),
      '#description' => $this->t("Label for the Ad type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $ad_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ads_system\Entity\AdType::load',
      ],
      '#disabled' => !$ad_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ad_type = $this->entity;
    $status = $ad_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Ad type.', [
          '%label' => $ad_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Ad type.', [
          '%label' => $ad_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($ad_type->urlInfo('collection'));

    drupal_flush_all_caches();
  }

}
