<?php
/**
 * @file
 * Contains \Drupal\widget_block\Form\WidgetBlockConfigRefreshForm.
 */

namespace Drupal\widget_block\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Provides a configuration form for refreshing a widget block configuration entity.
 */
class WidgetBlockConfigRefreshForm extends EntityConfirmFormBase { 

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $languageManager;

  /**
   * Create a WidgetBlockConfigRefreshForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The Language Manager service.
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    // Setup object members.
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('language_manager'));
  }

  /**
   * Get the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   An instance of LanguageManagerInterface.
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * Get a list of languages which can be used in a selection element.
   *
   * @return array
   *   An associative array which contains the language identifier as key and
   *   human readable name as value.
   */
  protected function getLanguageOptions() {
    // Initialize $options to an empty array. This will hold the selection
    // list.
    $options = [];

    // Get a list of available languages.
    $available_languages = $this->getLanguageManager()->getLanguages();
    // Iterate through the available languages.
    foreach ($available_languages as $language) {
      // Build the options list with given language identifier and name.
      $options[$language->getId()] = $language->getName();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to refresh the widget block markup?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.widget_block_config.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Perform default form building.
    $form = parent::buildForm($form, $form_state);

    // Get the current interface language.
    $current_language = $this->getLanguageManager()->getCurrentLanguage();
    // Build the language selection element.
    $form['languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Languages'),
      '#description' => $this->t('Select the language which should be refreshed.'),
      '#options' => $this->getLanguageOptions(),
      // Auto select the current language.
      '#default_value' => [$current_language->getId()],
      '#required' => TRUE,
    ];

    $form['refresh_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Refresh method'),
      '#description' => $this->t('Select "Forced" mode if you whish to refresh even if markup is already up to date.'),
      '#options' => [
        'default' => $this->t('Default'),
        'forced' => $this->t('Forced'),
      ],
      '#default_value' => 'default',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the Widget Block Configuration.
    $widget_block_config = $this->getEntity();
    // Get the language manager.
    $language_manager = $this->getLanguageManager();
    // Determine whether forced method should be used.
    $forced = $form_state->getValue('refresh_method') === 'forced';

    // Create a default batch definition.
    $batch_definition = [
      'operations' => [],
      'finished' => '_widget_block_batch_refresh_finished',
    ];

    // Get the selected language codes.
    $language_codes = array_keys(array_filter($form_state->getValue('languages')));
    // Iterate through the selected language codes.
    foreach ($language_codes as $language_code) {
      // Append the refresh operation for given language code to the list.
      $batch_definition['operations'][] = [
        '_widget_block_batch_refresh_operation',
        [$widget_block_config, $language_manager->getLanguage($language_code), $forced],
      ];
    }

    // Apply the batch definition.
    batch_set($batch_definition);
  }

}
