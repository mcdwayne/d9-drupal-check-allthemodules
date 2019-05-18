<?php

namespace Drupal\cielo\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CieloProfileForm.
 */
class CieloProfileForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\cielo\Entity\CieloProfile $cielo_profile */
    $cielo_profile = $this->entity;
    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#maxlength'     => 255,
      '#default_value' => $cielo_profile->label(),
      '#description'   => $this->t("Label for the Cielo profile."),
      '#required'      => TRUE,
    ];

    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $cielo_profile->id(),
      '#machine_name'  => [
        'exists' => '\Drupal\cielo\Entity\CieloProfile::load',
      ],
      '#disabled'      => !$cielo_profile->isNew(),
    ];

    $form['merchant_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Merchant Id'),
      '#default_value' => $cielo_profile->get('merchant_id'),
    ];

    $form['merchant_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Merchant Key'),
      '#default_value' => $cielo_profile->get('merchant_key'),
    ];

    $form['environment'] = [
      '#title'         => $this->t('Environment type'),
      '#type'          => 'radios',
      '#options'       => [
        'production' => $this->t('Production'),
        'sandbox'    => $this->t('Sandbox'),
      ],
      '#required'      => TRUE,
      '#default_value' => $cielo_profile->get('environment'),
    ];

    $form['save_transaction_log'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Save transaction log.'),
      '#description'   => $this->t('This option will save the data transaction with cielo on reports. Credit/Debit card number will be not saved for safe reasons.'),
      '#default_value' => $cielo_profile->get('save_transaction_log'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $cielo_profile = $this->entity;
    $status        = $cielo_profile->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Cielo profile.', [
          '%label' => $cielo_profile->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Cielo profile.', [
          '%label' => $cielo_profile->label(),
        ]));
    }
    $form_state->setRedirectUrl($cielo_profile->toUrl('collection'));
  }

}
