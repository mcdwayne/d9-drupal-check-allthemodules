<?php

namespace Drupal\local_translation_interface;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\local_translation\Services\LocalTranslationUserSkills;
use Drupal\locale\StringStorageInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trait LocalTranslationInterfaceLanguagesTrait.
 *
 * We are using this trait in TranslateEditForm and TranslateFilterForm
 * classes' children only.
 *
 * @see \Drupal\local_translation_interface\Form\TranslateEditForm
 * @see \Drupal\local_translation_interface\Form\TranslateFilterForm
 *
 * @package Drupal\local_translation_interface
 */
trait LocalTranslationInterfaceLanguagesTrait {

  /**
   * User registered languages list.
   *
   * @var array
   */
  protected $userRegisteredSkills = [];
  /**
   * Local translation skills service.
   *
   * @var \Drupal\local_translation\Services\LocalTranslationUserSkills
   */
  protected $userSkillsService;
  /**
   * Current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    StringStorageInterface $locale_storage,
    StateInterface $state,
    LanguageManagerInterface $language_manager,
    AccountProxyInterface $current_user,
    LocalTranslationUserSkills $skills
  ) {
    $this->languageManager   = $language_manager;
    $this->localeStorage     = $locale_storage;
    $this->currentUser       = $current_user;
    $this->userSkillsService = $skills;
    $this->state             = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('locale.storage'),
      $container->get('state'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('local_translation.user_skills')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function translateFilters() {
    // We are using this trait in TranslateEditForm and TranslateFilterForm
    // classes' children only.
    $filters = parent::translateFilters();

    // Get languages by registered skills.
    $languages = $this->getLanguagesBySkills();

    // Update filters array.
    $filters['langcode_from'] = [
      'title'   => $this->t('From language'),
      'options' => $languages['from'],
      'default' => !empty($languages['from']) ? key($languages['from']) : NULL,
    ];
    // Langcode will mean "langcode_to",
    // to prevent bugs with data selection it should be named as "langcode".
    $filters['langcode'] = [
      'title'   => $this->t('To language'),
      'options' => $languages['to'],
      'default' => !empty($languages['to']) ? key($languages['to']) : NULL,
    ];

    return $filters;
  }

  /**
   * Get languages from/to array using local translation skills values.
   *
   * @return array
   *   Languages from/to array.
   */
  protected function getLanguagesBySkills() {
    // Set defaults to prevent error if user has no skills.
    $languages = ['from' => [], 'to' => []];
    if (!$this->userSkillsService->getSkills()) {
      $this->userSkillsService->showEmptyMessage();
    }
    // Add registered languages to the top of the list.
    $this->addRegisteredLanguages($languages);
    // Add all other languages to the available options
    // if user has core's permission to translate interface.
    /** @var \Drupal\Core\Session\AccountInterface $current_user */
    $current_user = $this->currentUser();
    if ($current_user->hasPermission('translate interface')) {
      $this->appendOtherLanguages($languages);
    }
    // Remove English option if an appropriate option has not been enabled.
    $this->processEnglishInterfaceTranslation($languages);
    $languages['from']['en'] = 'English';
    return $languages;
  }

  /**
   * Add registered languages to the options list.
   *
   * @param array $languages
   *   Languages list.
   */
  protected function addRegisteredLanguages(array &$languages = []) {
    /** @var \Drupal\Core\Form\FormBase $this */
    $field_name = $this->config('local_translation.settings')
      ->get('field_name');
    /** @var \Drupal\user\Entity\User $user */
    $user = User::load($this->currentUser->id());
    $language_manager = $this->languageManager;
    if ($user->hasField($field_name)) {
      $combinations = $user->get($field_name);
      if (!$combinations->isEmpty()) {
        $combinations = $combinations->getValue();
        foreach ($combinations as $combination) {
          $language = $language_manager->getLanguage($combination['language_from']);
          $languages['from'][$language->getId()] = $language->getName();
          $language = $language_manager->getLanguage($combination['language_to']);
          $languages['to'][$language->getId()] = $language->getName();
        }
      }
    }
    if ($user->hasPermission('translate interface text into registered languages')
      && $user->hasPermission('translate interface')
    ) {
      $this->userRegisteredSkills = $languages;
    }
  }

  /**
   * Append all languages to the list.
   *
   * @param array &$languages
   *   Languages list.
   */
  protected function appendOtherLanguages(array &$languages) {
    $languages_list = $this->languageManager->getNativeLanguages();
    foreach ($languages_list as $lang) {
      foreach (['from', 'to'] as $type) {
        if (!isset($languages[$type][$lang->getId()])) {
          $languages[$type][$lang->getId()] = $lang->getName();
        }
      }
    }
  }

  /**
   * Process English translation options.
   *
   * @param array &$languages
   *   Languages array.
   */
  protected function processEnglishInterfaceTranslation(array &$languages) {
    if (!$this->isTranslateToEnglishEnabled()) {
      if (isset($languages['to']['en'])) {
        unset($languages['to']['en']);
      }
    }
  }

  /**
   * Check if the interface translation option is enabled for English.
   *
   * @return bool
   *   TRUE - if enabled, FALSE otherwise.
   */
  protected function isTranslateToEnglishEnabled() {
    return (bool) $this->config('locale.settings')->get('translate_english');
  }

}
