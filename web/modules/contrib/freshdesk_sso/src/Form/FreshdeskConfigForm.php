<?php

/**
 * @file
 * Contains \Drupal\freshdesk_sso\Form\FreshdeskConfigForm.
 */

namespace Drupal\freshdesk_sso\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FreshdeskConfigForm.
 *
 * @package Drupal\freshdesk_sso\Form
 */
class FreshdeskConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $freshdesk_config = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $freshdesk_config->label(),
      '#description' => $this->t("Label for the Freshdesk Configuration."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $freshdesk_config->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\freshdesk_sso\Entity\FreshdeskConfig::load',
      ),
      '#disabled' => !$freshdesk_config->isNew(),
    );

    $form['domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#maxlength' => 255,
      '#default_value' => $freshdesk_config->domain(),
      '#description' => $this->t('The base URL to your Freshdesk application.'),
      '#required' => TRUE,
    );

    $form['secret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Shared Secret'),
      '#maxlength' => 32,
      '#default_value' => $freshdesk_config->secret(),
      '#description' => $this->t('Shared secret for your Freshdesk application.'),
      '#required' => TRUE,
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $freshdesk_config = $this->entity;
    $status = $freshdesk_config->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Freshdesk Configuration.', [
          '%label' => $freshdesk_config->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Freshdesk Configuration.', [
          '%label' => $freshdesk_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($freshdesk_config->urlInfo('collection'));
  }

}
