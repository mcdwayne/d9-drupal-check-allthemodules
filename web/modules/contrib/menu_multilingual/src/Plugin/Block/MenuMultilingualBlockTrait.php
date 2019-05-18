<?php

namespace Drupal\menu_multilingual\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_multilingual\Helpers;

/**
 * Create admin users.
 */
trait MenuMultilingualBlockTrait {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form = parent::blockForm($form, $form_state);

    $disabled_labels  = !Helpers::checkEntityType('menu_link_content');
    $disabled_content = !Helpers::checkEntityType('node');

    $multilingual = [
      '#type'        => 'details',
      '#open'        => TRUE,
      '#title'       => t('Multilingual options'),
      '#description' => t('Control visibility of menu items depending on their available translations.<br><strong>Notice:</strong> menu items with untranslated parents will also not be displayed.'),
      '#process' => [[get_class(), 'processMenuLevelParents']],
    ];

    $multilingual['only_translated_labels'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide menu items without translated label'),
      '#default_value' => isset($config['only_translated_labels']) ? $config['only_translated_labels'] : 0,
      '#disabled' => $disabled_labels,
    ];
    $multilingual['only_translated_content'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide menu items without translated content'),
      '#default_value' => isset($config['only_translated_content']) ? $config['only_translated_content'] : 0,
      '#disabled' => $disabled_content,
    ];
    $form['multilingual'] = $multilingual;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['only_translated_labels'] = $form_state->getValue('only_translated_labels');
    $this->configuration['only_translated_content'] = $form_state->getValue('only_translated_content');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = parent::defaultConfiguration();
    $default += [
      'only_translated_labels' => 0,
      'only_translated_content' => 0,
    ];
    return $default;
  }

}
