<?php

namespace Drupal\shopify\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ShopifyWebhooksAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifyWebhooksAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify.webhooks',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_webhooks_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $client = shopify_api_client();
      $webhooks = $client->getResources('webhooks');
    }
    catch (\Exception $e) {
      // Error connecting to the store.
      drupal_set_message(t('Could not connect to the Shopify store.'), 'error');
      return [];
    }

    $config = $this->config('shopify.webhooks');

    $form['#tree'] = TRUE;

    $form['help'] = [
      '#markup' => t("<p>It is <strong>highly recommended</strong> that you at least have webhooks registered for product and collection events to keep your store automatically in sync with Shopify. Make sure you've registered all development, staging and production environment URLs.</p>"),
    ];

    // TODO: Can be changed to array of string.
    $message = '<p>Pro Tip: If you\'re developing locally and need to test webhooks ';
    $message .= 'download and install <a href="https://ngrok.com">ngrok</a> for a tunnel to your localhost. ';
    $message .= 'The service is free. Here is a command-line example: <br /><code>';
    $message .= 'ngrok http -host-header=YOUR_LOCALHOST_NAME_HERE.COM 80</code>. ';
    $message .= 'Place the "Forwarding" address into the hostname field above and ';
    $message .= 'register the hooks you want to test. The forwarding address ';
    $message .= 'address will look something like <code>http://0e1ff1cb.ngrok.io</code></p>';

    $form['tips'] = [
      '#type' => 'details',
      '#title' => t('Tips'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['tips']['UI'] = [
      '#markup' => t('<p>Webhooks created via the Shopify website will not appear here AND events will not trigger these hooks.</p>'),
    ];

    $form['tips']['standard_hooks'] = [
      '#markup' => t('<p>Be sure to add the product/collection webhooks to automatically keep your products/collections in sync with Shopify. Other hooks do nothing unless you write code implementing the hook yourself.</p>'),
    ];

    $form['tips']['ngrok'] = [
      '#markup' => t($message),
    ];

    $form['registered'] = [
      '#type' => 'details',
      '#title' => t('Webhooks Registered'),
    ];

    if (empty($webhooks)) {
      $form['registered']['none'] = [
        '#markup' => t('<p>No webhooks registered on Shopify for this App.</p><p>It is highly recommended that you at least have webhooks registered for product events to keep your store automatically in sync with Shopify.</p><p>Hooks created via the Shopify website will not be displayed here but will still work with the hook system. Caution: Internal paths are different for each hook.</p>'),
      ];
    }

    foreach ($webhooks as $hook) {
      $matches = [];
      preg_match('/:\/\/(.+)\/shopify\/webhook/', $hook->address, $matches);
      if (!isset($form['registered'][$hook->address])) {
        $form['registered'][$hook->address] = [
          '#type' => 'details',
          '#title' => Html::escape($matches[1]),
        ];
      }
      $form['registered'][$hook->address][$hook->id] = [
        '#type' => 'checkbox',
        '#title' => $hook->topic,
      ];
    }

    if (count($webhooks)) {
      $form['registered']['remove_submit'] = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => 'remove',
      ];
    }

    $form['register'] = [
      '#type' => 'details',
      '#title' => t('Register New Webhooks'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $possible_hooks = [
      'app' => [
        'uninstalled' => t('App Uninstalled'),
      ],
      'carts' => [
        'create' => t('Cart Created'),
        'update' => t('Cart Updated'),
      ],
      'checkouts' => [
        'create' => t('Checkout Created'),
        'update' => t('Checkout Updated'),
        'delete' => t('Checkout Deleted'),
      ],
      'collections' => [
        'create' => t('Collection Created - Highly Recommended'),
        'update' => t('Collection Updated - Highly Recommended. Bug on Shopify side, see <a target="_blank" target="_blank" href="https://www.drupal.org/node/2481105">Drupal.org issue</a>.'),
        'delete' => t('Collection Deleted - Highly Recommended'),
      ],
      'customers' => [
        'create' => t('Customer Created'),
        'enable' => t('Customer Enabled'),
        'disable' => t('Customer Disabled'),
        'update' => t('Customer Updated'),
        'delete' => t('Customer Deleted'),
      ],
      'customer_groups' => [
        'create' => t('Customer Group Created'),
        'update' => t('Customer Group Updated'),
        'delete' => t('Customer Group Deleted'),
      ],
      'fulfillments' => [
        'create' => t('Fulfillment Created'),
        'update' => t('Fullfillment Updated'),
      ],
      'orders' => [
        'create' => t('Order Created'),
        'delete' => t('Order Deleted'),
        'updated' => t('Order Updated'),
        'paid' => t('Order Paid'),
        'cancelled' => t('Order Cancelled'),
        'fulfilled' => t('Order Fulfilled'),
        'partially_fulfilled' => t('Order Partially Fulfilled'),
      ],
      'order_transactions' => [
        'create' => t('Order Transaction Created'),
      ],
      'products' => [
        'create' => t('Product Created - Highly Recommended'),
        'update' => t('Product Updated - Highly Recommended'),
        'delete' => t('Product Deleted - Highly Recommended'),
      ],
      'refunds' => [
        'create' => t('Refund Created'),
      ],
      'shop' => [
        'update' => t('Shop Updated'),
      ],
    ];

    foreach ($possible_hooks as $group_name => $group_options) {
      $form['register'][$group_name] = [
        '#type' => 'details',
        '#title' => t(str_replace('_', ' ', ucwords($group_name))),
        '#open' => ($group_name == 'products' || $group_name == 'collections') ? TRUE : FALSE,
      ];

      foreach ($group_options as $topic => $description) {
        $form['register'][$group_name][$topic] = [
          '#type' => 'checkbox',
          '#title' => $description,
        ];
      }
    }

    $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === TRUE ? 'https://' : 'http://';
    $current = $protocol . "$_SERVER[HTTP_HOST]";

    $form['register']['hostname'] = [
      '#type' => 'textfield',
      '#title' => t('Hostname'),
      '#default_value' => $config->get('last_registered_host') ?: $current,
      '#size' => 60,
      '#required' => TRUE,
      '#description' => t('Do not include a trailing slash.'),
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => t('Log incoming webhooks'),
      '#default_value' => $config->get('log_webhooks') ?: FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Removing webhooks form submit handler.
   */
  public function removeWebhooksSubmit(array &$form, FormStateInterface $form_state) {
    $client = shopify_api_client();
    $values = $form_state->getValues();
    unset($values['registered']['remove_submit']);
    foreach ((array) $values['registered'] as $host => $values) {
      foreach ((array) $values as $id => $remove) {
        if ($remove) {
          $client->deleteResource('webhooks', $id);
        }
      }
    }
  }

  /**
   * Create webhooks for a given host.
   */
  public function createWebhooksSubmit(array &$form, FormStateInterface $form_state) {
    $client = shopify_api_client();
    $values = $form_state->getValues();
    $hostname = $values['register']['hostname'];
    unset($values['register']['hostname']);

    foreach ($values['register'] as $group => $topics) {
      foreach ($topics as $topic => $selection) {
        if ($selection != 1) {
          continue;
        }
        $hook = [
          'webhook' => [
            'topic' => "$group/$topic",
            'address' => $hostname . '/shopify/webhook',
            'format' => "json",
          ],
        ];
        try {
          $client->createResource('webhooks', $hook);
        }
        catch (\Exception $e) {
          drupal_set_message(t('Could not create webhook. Error: @error.', [
            '@error' => $e->getMessage(),
          ]), 'warning');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'op':
        // Save standard settings.
        $config = $this->config('shopify.webhooks');
        $config
          ->set('log_webhooks', $form_state->getValue('debug'))
          ->set('last_registered_host', $form_state->getValue([
            'register',
            'hostname',
          ]))
          ->save();
        // Create webhooks if we need to.
        $this->createWebhooksSubmit($form, $form_state);
        break;

      case 'remove':
        // Removing webhooks.
        $this->removeWebhooksSubmit($form, $form_state);
        break;
    }
    parent::submitForm($form, $form_state);
  }

}
