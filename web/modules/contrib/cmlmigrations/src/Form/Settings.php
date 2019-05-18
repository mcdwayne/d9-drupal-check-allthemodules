<?php

namespace Drupal\cmlmigrations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\cmlmigrations\Controller\Service;
use Symfony\Component\Yaml\Yaml;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * AJAX Responce.
   */
  public static function ajax($otvet) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#exec-results", "<pre>{$otvet}</pre>"));
    return $response;
  }

  /**
   * AJAX ProdUuidFill.
   */
  public static function ajaxProdUuidFill(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxProdUuidFill\n";
    $otvet .= Service::uuid1cFill();
    return self::ajax($otvet);
  }

  /**
   * AJAX ProdUuidRemove.
   */
  public static function ajaxProdUuidClear(array $form, FormStateInterface $form_state) {
    $otvet = "ajaxProdUuidClear\n";
    $otvet .= Service::uuid1cClear();
    return self::ajax($otvet);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmlmigrations_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cmlmigrations.settings',
      'migrate_plus.migration.cml_taxonomy_catalog',
      'migrate_plus.migration.cml_product_variation',
      'migrate_plus.migration.cml_product',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cmlmigrations.settings');
    $catalog = $this->config('migrate_plus.migration.cml_taxonomy_catalog');
    $product = $this->config('migrate_plus.migration.cml_product');
    $variation = $this->config('migrate_plus.migration.cml_product_variation');
    $source_plugins = $this->getMigrationPlugins();
    $form['mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Mapping'),
      '#open' => TRUE,
    ];
    if ($catalog->get('process')) {
      $form['migrations-catalog'] = [
        '#type' => 'details',
        '#title' => $this->t('Migrations Catalog'),
        '#open' => TRUE,
        'catalog-source' => [
          '#type' => 'select',
          '#title' => 'Catalog Source Plugin',
          '#options' => $source_plugins,
          '#default_value' => $catalog->get('source')['plugin'],
        ],
        'catalog-process' => [
          '#title' => 'process',
          '#type' => 'textarea',
          '#attributes' => ['data-yaml-editor' => 'true'],
          '#default_value' => Yaml::dump($catalog->get('process'), 4),
        ],
      ];
    }
    if ($variation->get('process')) {
      $form['migrations-variations'] = [
        '#type' => 'details',
        '#title' => $this->t('Migrations Product Variations'),
        '#open' => TRUE,
        'variations-source' => [
          '#type' => 'select',
          '#title' => 'Product Variations Source Plugin',
          '#options' => $source_plugins,
          '#default_value' => $variation->get('source')['plugin'],
        ],
        'variations-process' => [
          '#title' => 'process',
          '#type' => 'textarea',
          '#attributes' => ['data-yaml-editor' => 'true'],
          '#default_value' => Yaml::dump($variation->get('process'), 4),
        ],
      ];
    }
    if ($product->get('process')) {
      $form['migrations-product'] = [
        '#type' => 'details',
        '#title' => $this->t('Migrations Product'),
        '#open' => TRUE,
        'product-source' => [
          '#type' => 'select',
          '#title' => 'Product Source Plugin',
          '#options' => $source_plugins,
          '#default_value' => $product->get('source')['plugin'],
        ],
        'product-process' => [
          '#title' => 'process',
          '#type' => 'textarea',
          '#attributes' => ['data-yaml-editor' => 'true'],
          '#default_value' => Yaml::dump($product->get('process'), 4),
        ],
      ];
    }
    $form['pipeline']['timeout'] = [
      '#title' => $this->t('Timeout'),
      '#type' => 'textfield',
      '#default_value' => $config->get('timeout'),
      '#description' => $this->t('Max migration time before failure (minute)'),
    ];
    $vocabulares = [];
    if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      $vocabulares = taxonomy_vocabulary_get_names();
    }
    $form['mapping']['vocabulary'] = [
      '#title' => $this->t('Catalog Vocabulary'),
      '#type' => 'select',
      '#options' => $vocabulares,
      '#default_value' => $config->get('vocabulary'),
    ];
    $form['pipeline'] = [
      '#type' => 'details',
      '#title' => $this->t('Pipeline'),
    ];
    $form['pipeline']['timeout'] = [
      '#title' => $this->t('Timeout, minutes'),
      '#type' => 'textfield',
      '#default_value' => $config->get('timeout'),
      '#description' => $this->t('Max migration time before failure (minute)'),
    ];
    $form['pipeline']['timeout-quick-run'] = [
      '#title' => $this->t('Quick Run Timeout, seconds'),
      '#type' => 'textfield',
      '#default_value' => $config->get('timeout-quick-run'),
      '#description' => $this->t('Protect from `migration busy` fails'),
    ];
    $drush_descr = $this->t("Leave blank to use default /usr/local/bin/drush");
    $form['drush_details'] = [
      '#type' => 'details',
      '#title' => $this->t('Drush'),
      'drush' => [
        '#title' => $this->t('Drush'),
        '#type' => 'textfield',
        '#default_value' => $config->get('drush'),
        '#description' => $drush_descr,
      ],
    ];
    $form['dev'] = [
      '#type' => 'details',
      '#title' => $this->t('Dev'),
      '#open' => FALSE,
      'bttns' => [
        '#type' => 'actions',
        'fill' => [
          '#type' => 'submit',
          '#value' => 'Fill 1000 product_uuid field',
          '#ajax'   => [
            'callback' => '::ajaxProdUuidFill',
            'effect'   => 'fade',
            'progress' => ['type' => 'throbber', 'message' => ""],
          ],
        ],
        'remove' => [
          '#type' => 'submit',
          '#value' => 'Clear 1000 product_uuid field',
          '#ajax'   => [
            'callback' => '::ajaxProdUuidClear',
            'effect'   => 'fade',
            'progress' => ['type' => 'throbber', 'message' => ""],
          ],
        ],
        '#suffix' => '<div id="exec-results"></div>',
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  protected function getMigrationPlugins() {
    $manager = FALSE;
    $plugins = [];
    try {
      $manager = \Drupal::service('plugin.manager.migrate.source');
    }
    catch (\Exception $e) {
      return FALSE;
    }
    if ($manager) {
      foreach ($manager->getDefinitions() as $key => $source) {
        $plugins[$key] = "$key ({$source['provider'][0]})";
      }
    }
    return $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cmlmigrations.settings');
    $timeout = trim($form_state->getValue('timeout'));
    if (!is_numeric($timeout)) {
      $timeout = 60;
    }
    $timeout2 = trim($form_state->getValue('timeout-quick-run'));
    if (!is_numeric($timeout2)) {
      $timeout2 = 60;
    }
    $config
      ->set('vocabulary', $form_state->getValue('vocabulary'))
      ->set('timeout', $timeout)
      ->set('timeout-quick-run', $timeout2)
      ->set('drush', $form_state->getValue('drush'))
      ->save();

    $catalog = $this->config('migrate_plus.migration.cml_taxonomy_catalog');
    if ($catalog->get('process')) {
      $catalog
        ->set('source', ['plugin' => $form_state->getValue('catalog-source')])
        ->set('process', Yaml::parse($form_state->getValue('catalog-process')))
        ->save();
    }
    $product = $this->config('migrate_plus.migration.cml_product');
    if ($product->get('process')) {
      $product
        ->set('source', ['plugin' => $form_state->getValue('product-source')])
        ->set('process', Yaml::parse($form_state->getValue('product-process')))
        ->save();
    }
    $variation = $this->config('migrate_plus.migration.cml_product_variation');
    if ($variation->get('process')) {
      $variation
        ->set('source', ['plugin' => $form_state->getValue('variations-source')])
        ->set('process', Yaml::parse($form_state->getValue('variations-process')))
        ->save();
    }
  }

}
