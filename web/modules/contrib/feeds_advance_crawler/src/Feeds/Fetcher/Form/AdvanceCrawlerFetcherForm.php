<?php

namespace Drupal\feeds_advance_crawler\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for AdvanceCrawlerFetcher.
 */
class AdvanceCrawlerFetcherForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['fetcher_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Fetcher Type'),
      '#options' => [
        'static_fetcher' => 'Static (To fetch static website content)',
        'dynamic_fetcher' => 'Dynamic (To fetch dynamic website content)',
      ],
      '#empty_option' => 'Select',
      '#default_value' => $this->plugin->getConfiguration('fetcher_type'),
      '#required' => TRUE,
    ];

    return $form;
  }

}
