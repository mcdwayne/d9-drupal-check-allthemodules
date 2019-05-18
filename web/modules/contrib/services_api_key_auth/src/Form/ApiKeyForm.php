<?php

namespace Drupal\services_api_key_auth\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Crypt;

/**
 * Class ApiKeyForm.
 *
 * @package Drupal\api_key_auth\Form
 */
class ApiKeyForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $api_key = $this->entity;
    $hex = isset($api_key->key) ? $api_key->key : substr(hash('sha256', Crypt::randomBytes(16)), 0, 32);

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Machine Name'),
      '#maxlength' => 255,
      '#default_value' => $api_key->label(),
      '#description' => $this->t("Machine Name for the API Key."),
      '#required' => TRUE,
    );

    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#maxlength' => 42,
      '#default_value' => $hex,
      '#description' => $this->t("The generated API Key for an user."),
      '#required' => TRUE,
    );

    $form['user_uuid'] = array(
      '#type' => 'select',
      '#multiple' => FALSE,
      '#options' => self::getUser(),
      '#description' => $this->t("Please select the user who gets authenticated with that API Key."),
      '#default_value' => $api_key->user_uuid,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $api_key->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\services_api_key_auth\Entity\ApiKey::load',
      ),
      '#disabled' => !$api_key->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $api_key = $this->entity;
    $status = $api_key->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label API Key.', [
          '%label' => $api_key->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label API Key.', [
          '%label' => $api_key->label(),
        ]));
    }
    $form_state->setRedirectUrl($api_key->urlInfo('collection'));
  }

  /**
   * Helper function to get taxonomy term options for select widget.
   *
   * @parameter String $machine_name
   *   taxonomy machine name
   *
   * @return array
   *   Select options for form
   */
  public function getUser() {
    $options = array();

    // $vid = taxonomy_vocabulary_machine_name_load($machine_name)->vid;
    $options_source = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();

    foreach ($options_source as $item) {
      $key = $item->uuid->value;
      $value = $item->name->value;
      $options[$key] = $value;
    }
    return $options;
  }

}
