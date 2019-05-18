<?php

namespace Drupal\cision_feed\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * Defines a cision fetcher.
 */
class CisionFetcherForm extends ExternalPluginFormBase {

  /**
   * Constructs a CisionFetcherForm object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $languages = \Drupal::languageManager()->getLanguages();
    $options = ['' => t('Select language')];
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }
    $form['language'] = [
      '#type' => 'select',
      '#title' => t('Language'),
      '#description' => t('Language code of the default language in the feed.'),
      '#options' => $options,
      '#default_value' => $this->plugin->getConfiguration('language'),
    ];
    $form['types'] = [
      '#type' => 'select',
      '#title' => t('Select type of messages'),
      '#description' => t('The type of releases to collect from cision.'),
      '#options' => $this->plugin->getTypes(),
      '#multiple' => TRUE,
      '#default_value' => $this->plugin->getConfiguration('types'),
    ];
    $form['page_size'] = [
      '#type' => 'number',
      '#title' => t('Page size'),
      '#description' => t('The number of releases returned from cision. The default is 50 and the max is 100.'),
      '#default_value' => $this->plugin->getConfiguration('page_size'),
      '#min' => 1,
      '#max' => 100,
    ];
    $form['page_index'] = [
      '#type' => 'number',
      '#title' => t('Page index'),
      '#description' => t('The index the release list starts on. The default is 1.'),
      '#default_value' => $this->plugin->getConfiguration('page_index'),
      '#min' => 1,
    ];
    $form['start_date'] = [
      '#type' => 'date',
      '#title' => t('Start date'),
      '#description' => t('Start date in UTC format.'),
      '#default_value' => $this->plugin->getConfiguration('start_date'),
    ];
    $form['end_date'] = [
      '#type' => 'date',
      '#title' => t('End date'),
      '#description' => t('End date in UTC format.'),
      '#default_value' => $this->plugin->getConfiguration('end_date'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

}
