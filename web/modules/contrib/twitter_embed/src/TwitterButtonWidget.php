<?php

namespace Drupal\twitter_embed;

/**
 * Class TwitterButtonWidget.
 */
class TwitterButtonWidget extends TwitterWidget implements TwitterWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function getAvailableSettings() {
    return [
      'username' => '',
      'display_style' => 'follow-button',
      'display_options' => [
        'hide_username' => FALSE,
        'hide_followers_count' => FALSE,
        'size' => NULL,
        'theme' => 'light',
        'link_color' => '#2b7bb9',
        'border_color' => '#000000',
        'width' => 0,
        'height' => 600,
        'language' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    // @todo flatten from getButtonAvailableSettings()
    return [
      'username' => '',
      'display_style' => 'follow-button',
      'hide_username' => FALSE,
      'hide_followers_count' => FALSE,
      'size' => NULL,
      'theme' => 'light',
      'link_color' => '#2b7bb9',
      'border_color' => '#000000',
      'width' => 0,
      'height' => 600,
      'language' => '',
      // Settings are shared amongst formatters
      // so even if their values are not used,
      // they have to be declared.
      'type' => 'profile',
      'type_value' => '',
      'chrome' => NULL,
      'tweet_limit' => 0,
      'show_replies' => FALSE,
      'aria_polite' => 'polite',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTypes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableDisplayStyles() {
    return [
      'follow-button' => t('Follow Button'),
      'mention-button' => t('Mention Button'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $configuration) {
    $form = [];
    // @todo handle mention-button extra options like text.
    $form['display_style'] = [
      '#type' => 'radios',
      '#title' => t('Display style'),
      '#options' => $this->getAvailableDisplayStyles(),
      '#default_value' => $configuration['display_style'],
      '#required' => TRUE,
    ];

    $form['display_options'] = [
      '#type' => 'details',
      '#title' => t('Display options'),
      '#open' => FALSE,
    ];
    $form['display_options']['hide_username'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide username'),
      '#default_value' => $configuration['hide_username'],
    ];
    $form['display_options']['hide_followers_count'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide followers count'),
      '#default_value' => $configuration['hide_followers_count'],
    ];
    $form['display_options']['size'] = [
      '#type' => 'checkbox',
      '#title' => t('Large button'),
      '#default_value' => $configuration['size'],
    ];
    $form['display_options']['language'] = [
      '#type' => 'select',
      '#title' => t('Language'),
      '#description' => t('What language would you like to display this in?.'),
      '#options' => $this->getAvailableLanguages(),
      '#default_value' => $configuration['language'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsFormStates(array $form, $selector) {
    $form['display_options']['hide_username']['#states'] = [
      'visible' => [
        ['input[name="' . $selector . '[display_style]"]' => ['value' => 'follow-button']],
      ],
    ];
    $form['display_options']['hide_followers_count']['#states'] = [
      'visible' => [
        ['input[name="' . $selector . '[display_style]"]' => ['value' => 'follow-button']],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependentConfiguration(array &$configuration) {
    // @todo these rules should be used in form validation.
    // Uncheck the hide_username and hide_followers_count when unnecessary.
    if (!in_array($configuration['display_style'], ['follow-button'])) {
      $configuration['hide_username'] = FALSE;
      $configuration['hide_followers_count'] = FALSE;
    }
  }

}
