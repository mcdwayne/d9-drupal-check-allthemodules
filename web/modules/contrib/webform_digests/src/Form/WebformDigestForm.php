<?php

namespace Drupal\webform_digests\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;

/**
 * Class WebformDigestForm.
 */
class WebformDigestForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $webform_digest = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $webform_digest->label(),
      '#description' => $this->t("Label for the Webform digest."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webform_digest->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webform_digests\Entity\WebformDigest::load',
      ],
      '#disabled' => !$webform_digest->isNew(),
    ];

    $form['recipient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Digest recipient'),
      '#maxlength' => 255,
      '#default_value' => $webform_digest->getRecipient(),
      '#description' => $this->t("The email address this digest will go to - you may use tokens here"),
      '#required' => TRUE,
    ];

    $form['originator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Digest from'),
      '#maxlength' => 255,
      '#default_value' => $webform_digest->getOriginator(),
      '#description' => $this->t("The email address this is from - you may use tokens here"),
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Digest subject'),
      '#maxlength' => 255,
      '#default_value' => $webform_digest->getSubject(),
      '#description' => $this->t("The email subject - you may use tokens here"),
      '#required' => TRUE,
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Digest body'),
      '#maxlength' => 255,
      '#default_value' => $webform_digest->getBody(),
      '#description' => $this->t("The email body - you may use tokens here"),
      '#required' => TRUE,
    ];

    $options = array_map(function ($item) {
      return $item->label();
    }, Webform::loadMultiple());

    $form['webform'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform'),
      '#options' => $options,
      '#maxlength' => 255,
      '#default_value' => $webform_digest->getWebform(),
      '#required' => TRUE,
    ];

    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [
        'node',
        'webform_digest',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webform_digest = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Webform digest.', [
          '%label' => $webform_digest->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Webform digest.', [
          '%label' => $webform_digest->label(),
        ]));
    }
    $form_state->setRedirectUrl($webform_digest->toUrl('collection'));
  }

}
