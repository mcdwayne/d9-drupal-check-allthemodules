<?php

namespace Drupal\acquia_contenthub_publisher\Form;

use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Defines the form to register the webhooks.
 */
class SubscriptionSettingsForm extends ConfigFormBase {

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Content Hub Subscription.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * WebhooksSettingsForm constructor.
   *
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $client_factory
   *   The client factory.
   */
  public function __construct(ClientFactory $client_factory) {
    $this->clientFactory = $client_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_contenthub.client.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acquia_contenthub.admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acquia_contenthub.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $client = $this->clientFactory->getClient();
    $current_name = $client->getSettings()->getName();
    $webhooks = $client->getWebHooks();

    $form['webhook_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Administer Webhooks'),
      '#collapsible' => FALSE,
      '#description' => $this->t('Manage Acquia Content Hub Webhooks. To delete a webhook, simply remove the URL you wish to delete above.'),
    ];

    $form['webhook_settings']['webhooks'] = [
      '#type' => 'table',
      '#header' => [
        'uuid' => $this->t('UUID'),
        'url' => $this->t('URL'),
      ],
    ];

    if (!empty($webhooks)) {
      foreach ($webhooks as $key => $webhook) {
        $form['webhook_settings']['webhooks'][$key]['uuid'] = ['#markup' => $webhook['uuid']];
        $form['webhook_settings']['webhooks'][$key]['url'] = [
          '#type' => 'textfield',
          '#title' => '',
          '#title_display' => 'invisible',
          // It is important to use #name attribute.
          // Instead #default_value need to use #value.
          '#default_value' => $webhook['url'],
        ];
      }
    }
    // Add a new webhook field.
    $form['webhook_settings']['webhooks'][] = [
      'uuid' => ['#markup' => 'Add a new Webhook:'],
      'url' => [
        '#type' => 'textfield',
        '#title' => '',
        '#title_display' => 'invisible',
        '#description' => $this->t('Example: @url/acquia-contenthub/webhook', ['@url' => $GLOBALS['base_url']]),
      ],
    ];

    $clients = $client->getClients();

    $form['client_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Administer Clients'),
      '#collapsible' => FALSE,
      '#description' => $this->t('Manage Acquia Content Hub Clients. To delete a client, simply remove the name you wish to delete above.'),
    ];

    $form['client_settings']['clients'] = [
      '#type' => 'table',
      '#header' => [
        'uuid' => $this->t('UUID'),
        'name' => $this->t('Name'),
      ],
    ];

    if (!empty($clients)) {
      foreach ($clients as $key => $clientname) {
        $form['client_settings']['clients'][$key]['uuid'] = ['#markup' => $clientname['uuid']];
        $form['client_settings']['clients'][$key]['name'] = [
          '#type' => 'textfield',
          '#title' => '',
          '#title_display' => 'invisible',
          // It is important to use #name attribute.
          // Instead #default_value need to use #value.
          '#default_value' => $clientname['name'],
          '#description' => ($clientname['name'] == $current_name) ? $this->t("Note: You can only change your own client name, not delete it.") : '',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Prune the last webhook if it was empty.
    $webhooks = $form_state->getValue('webhooks');
    foreach ($webhooks as $webhook) {
      if (!empty($webhook['url'] && !UrlHelper::isValid($webhook['url'], TRUE))) {
        return $form_state->setErrorByName('webhook_url', $this->t('%webhook is not a valid URL. Please insert it again.', ["%webhook" => $webhook['url']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webhooks = $form_state->getValue('webhooks');
    $new_webhook = array_pop($webhooks);
    $client = $this->clientFactory->getClient();
    $service_webhooks = $client->getWebHooks();

    $response = [];
    $updated = $deleted = $created = $error = $key = 0;

    // Update the webhooks.
    foreach ($webhooks as $key => $webhook) {
      if (empty($webhook['url'])) {
        $response[$key] = $client->deleteWebhook($service_webhooks[$key]['uuid']);
        $deleted++;
      }
      elseif ($webhook['url'] != $service_webhooks[$key]['url']) {
        $response[$key] = $client->updateWebhook($service_webhooks[$key]['uuid'], $webhook['url']);
        // Yay we recieved a response. Is it valid?
        if ($response[$key]) {
          if ($response[$key]->getStatusCode() == '200') {
            $updated++;
          }
          elseif ($response[$key]->getStatusCode() == '403') {
            drupal_set_message($this->t('Unable to update webhook %url, Permission denied', ['%url' => $webhook['url']]), 'error'); // @codingStandardsIgnoreLine.
            $error++;
          }
          else {
            drupal_set_message($this->t('Unable to update webhook %url, Error %error', ['%url' => $webhook['url'], '%error' => $response[$key]->getStatusCode()]), 'error'); // @codingStandardsIgnoreLine.
            $error++;
          }
        }
      }
    }

    // Process the new webhook at the end of the list.
    if (!empty($new_webhook['url'])) {
      $key++;
      $response[$key] = $client->addWebhook($new_webhook['url']);
      if (empty($response[$key]) || (isset($response[$key]['success']) && $response[$key]['success'] == FALSE)) {
        drupal_set_message($this->t('Unable to create URL %url, Error %error: %message', ['%url' => $new_webhook['url'], '%error' => $response[$key]['error']['code'], '%message' => $response[$key]['error']['message']]), 'error'); // @codingStandardsIgnoreLine.
        $error++;
      }
      else {
        $created++;
      }
    }

    if ($error > 0) {
      drupal_set_message($this->t('There was an issue updating or deleting some webhooks, check the error log for failures. %deleted were deleted, %updated were updated, and %created were created.', ['%deleted' => $deleted, '%updated' => $updated, '%created' => $created]), 'warning'); // @codingStandardsIgnoreLine.
    }
    elseif ($updated > 0 || $deleted > 0 || $created > 0) {
      drupal_set_message($this->t('Successfully updated webhooks. %deleted were deleted, %updated were updated, and %created were created.', ['%deleted' => $deleted, '%updated' => $updated, '%created' => $created])); // @codingStandardsIgnoreLine.
    }

    // Update Client Names.
    $clients = $form_state->getValue('clients');
    $client = $this->clientFactory->getClient();
    $service_clients = $client->getClients();

    $response = [];
    $updated = $deleted = $created = $error = $key = 0;

    foreach ($clients as $key => $client_name) {
      if (empty($client_name['name'])) {
        if ($service_clients[$key]['uuid'] == $client->getSettings()->getUuid()) {
          drupal_set_message($this->t('You cannot delete the client used to access the subscription manager.'), 'error');
          $error++;
          continue;
        }
        $response[$key] = $client->deleteClient($service_clients[$key]['uuid']);
        $deleted++;
      }
      elseif ($client_name['name'] != $service_clients[$key]['name']) {
        $response[$key] = $client->updateClient($service_clients[$key]['uuid'], $client_name['name']);
        // Yay we received a response. Is it valid?
        if ($response[$key]) {
          if ($response[$key]->getStatusCode() == '200') {
            $updated++;
          }
          elseif ($response[$key]->getStatusCode() == '403') {
            drupal_set_message($this->t('Unable to update client to %name, Permission denied.', ['%name' => $client_name['name']]), 'error');
            $error++;
          }
          else {
            drupal_set_message($this->t('Unable to update client to %name, Error %error', ['%name' => $client_name['name'], '%error' => $response[$key]->getStatusCode()]), 'error');
            $error++;
          }
        }
      }
    }
    if ($error > 0) {
      drupal_set_message($this->t('Only some client names were updated, check the error log for failures. %deleted were deleted and %updated were updated', ['%deleted' => $deleted, '%updated' => $updated, '%created' => $created]), 'warning'); // @codingStandardsIgnoreLine.
    }
    elseif ($updated > 0 || $deleted > 0 || $created > 0) {
      drupal_set_message($this->t('Successfully updated/removed client names. %deleted were deleted and %updated were updated.', ['%deleted' => $deleted, '%updated' => $updated, '%created' => $created])); // @codingStandardsIgnoreLine.
    }
  }

}
