<?php

namespace Drupal\translators_content\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\translators\Services\TranslatorSkills;
use Drupal\views\Plugin\views\filter\LanguageFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TranslatorsViewsFiltersBase.
 *
 * @package Drupal\translators_content\Plugin\views\filter
 */
abstract class TranslatorsViewsFiltersBase extends LanguageFilter implements ContainerFactoryPluginInterface {

  /**
   * User skills service.
   *
   * @var \Drupal\translators\Services\TranslatorSkills
   */
  protected $translatorSkills;
  /**
   * Registered languages for the current user.
   *
   * @var array
   */
  protected $userRegisteredLanguages = [];
  /**
   * {@inheritdoc}
   */
  protected $valueOptions = [];
  /**
   * Flag about registered skills emptiness.
   *
   * @var bool
   */
  protected $isEmptySkills = FALSE;
  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * Disable the possibility to allow a exposed input to be optional.
   *
   * @var bool
   */
  protected $alwaysRequired = FALSE;

  /**
   * Constructs a new TranslatorsViewsFiltersBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   * @param \Drupal\translators\Services\TranslatorSkills $translatorSkills
   *   User skills service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LanguageManagerInterface $language_manager,
    TranslatorSkills $translatorSkills,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $language_manager);
    $this->translatorSkills = $translatorSkills;
    $this->currentUser = $current_user;
    $this->prepareUserRegisteredLanguages();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('translators.skills'),
      $container->get('current_user')
    );
  }

  /**
   * Helper method for initial call the translation skills service.
   */
  protected function prepareUserRegisteredLanguages() {
    $this->userRegisteredLanguages = $this->translatorSkills
      ->getSkills($this->currentUser, TRUE);
    $this->isEmptySkills = (bool) (FALSE === $this->userRegisteredLanguages
      || empty($this->userRegisteredLanguages));
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
    $this->postProcessExposedForm($form);
    $this->setSelectedOption($form, $form_state);
    $form[$this->field]['#required'] = $this->alwaysRequired;
  }

  /**
   * Set selected option.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  protected function setSelectedOption(array &$form, FormStateInterface $form_state) {
    $identifier = $this->getIdentifier();
    $user_input = $form_state->getUserInput();
    if (isset($user_input[$identifier])) {
      $form[$identifier]['#value']
        = $form[$identifier]['#default_value']
          = $user_input[$identifier];
    }
  }

  /**
   * Get filter identifier.
   *
   * @return string
   *   Identifier string.
   */
  protected function getIdentifier() {
    return $this->options['expose']['identifier'];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $this->valueOptions = [];
    // Leave empty languages list if no user skills are available.
    if ($this->isEmptySkills) {
      return $this->valueOptions;
    }
    // Handle column options.
    foreach ($this->options['column'] as $name => $column) {
      if (!empty($column)) {
        foreach ($this->userRegisteredLanguages as $langs) {
          $this->processColumnOption($langs, $name);
        }
      }
    }
    return $this->valueOptions;
  }

  /**
   * Process column options.
   *
   * @param array $languages
   *   Languages array.
   * @param string $column
   *   Column name.
   */
  protected function processColumnOption(array $languages, $column) {
    $key = "language_$column";
    if (isset($languages[$key])) {
      $key = $languages[$key];
      $this->valueOptions[$key] = $this->languageManager
        ->getLanguage($key)
        ->getName();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $form_state->setValue(['options', 'column', 'source'], $form['column']['source']['#value']);
    $form_state->setValue(['options', 'column', 'target'], $form['column']['target']['#value']);
    if ($this->isEmptySkills && $this->options['limit']) {
      $form['value']['#value'] = [];
      $this->translatorSkills->showEmptyMessage();
    }
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * Post processor of exposed form builder.
   *
   * @param array &$form
   *   Form array.
   */
  protected function postProcessExposedForm(array &$form) {
    $field =& $form[$this->field];
    $this->resetElementValuesAndOptions($field);
    // Show empty registered skills message inside this window.
    if ($this->isEmptySkills && $this->options['limit']) {
      $this->translatorSkills->showEmptyMessage();
      $this->resetOptionsForEmptySkills($field);
    }
    // Build languages list.
    $field['#options'] += $this->getValueOptions();
  }

  /**
   * Reset element's options, value and default_value to an empty array.
   *
   * @param array &$field
   *   Field render-able array.
   */
  protected function resetElementValuesAndOptions(array &$field) {
    $field['#default_value'] = $field['#value'] = $field['#options'] = [];
  }

  /**
   * Leave only default option if there are no registered skills.
   *
   * @param array &$field
   *   Filter's field render-able array.
   */
  abstract protected function resetOptionsForEmptySkills(array &$field);

  /**
   * Helper method to get the column options for filter's settings form.
   *
   * @return array
   *   Column options for filter's settings form.
   */
  protected function getFilterColumnsOptions() {
    return [
      'source' => $this->t('Source languages'),
      'target'   => $this->t('Target languages'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    // Handle missing translation skills specified for the user.
    if ($this->isEmptySkills && $this->options['limit']) {
      $this->translatorSkills->showEmptyMessage();
    }
    return parent::validate();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // Additional cachetags to allow filters to rebuild
    // after the user's skills gets changed.
    if (!$this->currentUser->isAnonymous()) {
      $cache_tags[] = "user:{$this->currentUser->id()}";
    }
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function listLanguages($flags = LanguageInterface::STATE_ALL, array $current_values = NULL) {
    return array_map(function ($language) {
      return (string) $language;
    }, parent::listLanguages($flags, $current_values));
  }

}
