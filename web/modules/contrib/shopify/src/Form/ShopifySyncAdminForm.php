<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shopify\Batch\ShopifyProductBatch;
use Drupal\shopify\Batch\ShopifyCollectionBatch;

/**
 * Class ShopifySyncAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifySyncAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shopify.sync',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_sync_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shopify.sync');

    $products_last_sync_time = \Drupal::state()
      ->get('shopify.sync.products_last_sync_time');
    $collections_last_sync_time = \Drupal::state()
      ->get('shopify.sync.collections_last_sync_time');

    if (empty($products_last_sync_time)) {
      $products_last_sync_time_formatted = t('Never');
    }
    else {
      $products_last_sync_time_formatted = \Drupal::service('date.formatter')
        ->format($products_last_sync_time, 'medium');
    }

    if (empty($collections_last_sync_time)) {
      $collections_last_sync_time_formatted = t('Never');
    }
    else {
      $collections_last_sync_time_formatted = \Drupal::service('date.formatter')
        ->format($collections_last_sync_time, 'medium');
    }

    $form['products'] = [
      '#type' => 'details',
      '#title' => t('Sync Products'),
      '#description' => t('Last sync time: @time', [
        '@time' => $products_last_sync_time_formatted,
      ]),
    ];
    $form['products']['num_per_batch'] = [
      '#type' => 'select',
      '#title' => 'Choose how many products to sync per batch operation (not per batch).',
      '#options' => [
        '1' => t('1 at a time'),
        '10' => t('10 at a time'),
        '50' => t('50 at a time'),
        '100' => t('100 at a time'),
        '250' => t('250 (Max API limit)'),
      ],
      '#default_value' => 250,
    ];
    $form['products']['delete_products_first'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete all products then re-import fresh.') . '<br /><strong>' . t('CAUTION: Product entities will be completely deleted then re-imported. Custom field data will be erased, comments deleted, etc.') . '</strong>',
    ];
    $form['products']['force_update'] = [
      '#type' => 'checkbox',
      '#title' => t('Update all products regardless of last sync time. Product entities will be updated, not deleted.'),
    ];
    $form['products']['sync'] = [
      '#type' => 'submit',
      '#value' => t('Sync Products'),
      '#name' => 'sync_products',
    ];

    $form['collections'] = [
      '#type' => 'details',
      '#title' => t('Sync Collections'),
      '#description' => t('Last sync time: @time', [
        '@time' => $collections_last_sync_time_formatted,
      ]),
    ];
    $form['collections']['delete_collections_first'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete all collections then re-import fresh.') . '<br /><strong>' . t('CAUTION: Collection terms will be completely deleted then re-imported. Custom field data will be erased.') . '</strong>',
    ];
    $form['collections']['sync'] = [
      '#type' => 'submit',
      '#value' => t('Sync Collections'),
      '#name' => 'sync_collections',
    ];

    $form['cron'] = [
      '#type' => 'details',
      '#title' => t('Cron'),
      '#description' => t('Settings for automatically syncing products/collections on cron run.<br /><strong>Only newly updated products/collections will be synced.</strong><br /><br />'),
      '#tree' => TRUE,
    ];
    $form['cron']['sync_products'] = [
      '#type' => 'checkbox',
      '#title' => t('Sync products on cron run.'),
      '#default_value' => $config->get('cron_sync_products'),
    ];
    $form['cron']['sync_collections'] = [
      '#type' => 'checkbox',
      '#title' => t('Sync collections on cron run.'),
      '#default_value' => $config->get('cron_sync_collections'),
    ];
    $form['cron']['sync_time'] = [
      '#type' => 'textfield',
      '#title' => t('How often to sync'),
      '#description' => t('Enter the number of seconds to wait to sync between cron runs.<br />To sync once per day, enter "86400". To sync once per hour, enter "3600".<br />Leave empty or "0" to sync on every cron run.'),
      '#default_value' => $config->get('cron_sync_time') ?: 0,
    ];
    $form['cron']['save_cron'] = [
      '#type' => 'submit',
      '#value' => t('Save cron settings'),
      '#name' => 'save_cron_settings',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'sync_products':
        $this->batchSyncProducts($form, $form_state);
        break;

      case 'sync_collections':
        $this->batchSyncCollections($form, $form_state);
        break;

      case 'save_cron_settings':
        $this->saveCronSettings($form, $form_state);
        break;

      default:
        parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  private function saveCronSettings(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('shopify.sync');
    $config
      ->set('cron_sync_products', $form_state->getValue([
        'cron',
        'sync_products',
      ]))
      ->set('cron_sync_collections', $form_state->getValue([
        'cron',
        'sync_collections',
      ]))
      ->set('cron_sync_time', $form_state->getValue([
        'cron',
        'sync_time',
      ]))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  private function batchSyncCollections(array &$form, FormStateInterface $form_state) {
    $batch = new ShopifyCollectionBatch();
    $batch->prepare([
      'delete_collections_first' => $form_state->getValue('delete_collections_first'),
    ])->set();
  }

  /**
   * {@inheritdoc}
   */
  private function batchSyncProducts(array &$form, FormStateInterface $form_state) {
    $batch = new ShopifyProductBatch();

    $last_sync_time = \Drupal::state()
      ->get('shopify.sync.products_last_sync_time');

    $batch->prepare([
      'num_per_batch' => $form_state->getValue('num_per_batch'),
      'delete_products_first' => $form_state->getValue('delete_products_first'),
      'force_update' => $form_state->getValue('force_update'),
      'updated_at_min' => $last_sync_time,
    ])->set();
  }

}
