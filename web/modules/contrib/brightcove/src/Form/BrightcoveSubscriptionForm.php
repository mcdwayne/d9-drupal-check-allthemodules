<?php

namespace Drupal\brightcove\Form;

use Drupal\brightcove\Entity\BrightcoveAPIClient;
use Drupal\brightcove\Entity\BrightcoveSubscription;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form for Brightcove Subscription add, edit.
 */
class BrightcoveSubscriptionForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'brightcove_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\brightcove\Entity\BrightcoveAPIClient[] $api_clients */
    $api_clients = BrightcoveAPIClient::loadMultiple();
    $api_client_options = [];
    foreach ($api_clients as $api_client) {
      $api_client_options[$api_client->id()] = $api_client->label();
    }

    $form['api_client_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Client'),
      '#options' => $api_client_options,
      '#required' => TRUE,
    ];

    if (empty($api_client_options)) {
      $form['api_client_id']['#empty_option'] = $this->t('No API clients available');
    }
    elseif (empty($form['api_client_id']['#default'])) {
      $api_client_ids = array_keys($api_client_options);
      $default = reset($api_client_ids);
      $form['api_client_id']['#default_value'] = $default;
    }

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t('The notifications endpoint.'),
      '#required' => TRUE,
    ];

    // Hard-code "video-change" event since it's the only one.
    $form['events'] = [
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#title' => 'Events',
      '#options' => [
        'video-change' => $this->t('Video change'),
      ],
      '#default_value' => ['video-change'],
      '#disabled' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate endpoint, it should be unique.
    if (!empty(BrightcoveSubscription::loadByEndpoint($form_state->getValue('endpoint')))) {
      $form_state->setErrorByName('endpoint', $this->t('A subscription with the %endpoint endpoint already exists.', ['%endpoint' => $form_state->getValue('endpoint')]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $brightcove_subscription = BrightcoveSubscription::createFromArray([
        'api_client_id' => $form_state->getValue('api_client_id'),
        'endpoint' => $form_state->getValue('endpoint'),
        'events' => array_values($form_state->getValue('events')),
      ]);
      $brightcove_subscription->save(TRUE);

      drupal_set_message($this->t('Created Brightcove Subscription with %endpoint endpoint.', [
        '%endpoint' => $brightcove_subscription->getEndpoint(),
      ]));
    }
    catch (\Exception $e) {
      // In case of an exception, show an error message and rebuild the form.
      if ($e->getMessage()) {
        drupal_set_message($this->t('Failed to create subscription: %error', ['%error' => $e->getMessage()]), 'error');
      }
      else {
        drupal_set_message($this->t('Failed to create subscription.'), 'error');
      }

      $form_state->setRebuild(TRUE);
    }

    // Redirect back to the Subscriptions list.
    $form_state->setRedirect('entity.brightcove_subscription.list');
  }

}
