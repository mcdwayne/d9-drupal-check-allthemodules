<?php

namespace Drupal\interface_string_stats;

use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\Translator\TranslatorInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class TranslationManagerStatsCapture.
 */
class StringStatsTranslationManager extends TranslationManager {

  /**
   * Original TranslationManager object.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a TranslationManager object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The original TranslationManager object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The ConfigFactoryInterface object.
   * @param \Drupal\Core\Session\AccountProxyInterface $proxyUser
   *   The logged in user.
   */
  public function __construct(
    TranslationInterface $translation_manager,
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $proxyUser
  ) {
    $this->translationManager = $translation_manager;
    $this->config = $configFactory->get('interface_string_stats.settings');
    $this->currentUser = $proxyUser;
  }

  /**
   * @inheritdoc
   */
  public function addTranslator(TranslatorInterface $translator, $priority = 0) {
    return $this->translationManager->addTranslator($translator, $priority);
  }

  /**
   * @inheritdoc
   */
  protected function sortTranslators() {
    return $this->translationManager->sortTranslators();
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    return $this->translationManager->getStringTranslation($langcode, $string, $context);
  }

  /**
   * @inheritdoc
   */
  public function translate($string, array $args = [], array $options = []) {
    return $this->translationManager->translate($string, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function translateString(TranslatableMarkup $translated_string) {
    $string = $this->translationManager->translateString($translated_string);

    // Check if we should be processing strings and save them to the cache
    // so we're not running through this logic for every string.
    $process = &drupal_static(__METHOD__, NULL);
    if (is_null($process)) {
      $process = $this->getProcessUser();
    }
    if ($process === FALSE) {
      return $string;
    }

    // We're going to build an array and save any string which requires
    // translation. They key here is to do as little processing as possible,
    // as few calls to the database to make this performant. We'll build this
    // array as we go, pass it off to the queue at the complete end of the
    // Drupal call.
    $options = $translated_string->getOptions();
    $context = isset($options['context']) ? $options['context'] : '';
    $original_string = $translated_string->getUntranslatedString();

    $translation_request = [
      'language' => $this->translationManager->defaultLangcode,
      'string' => $original_string,
      'context' => $context,
    ];

    $requested_string_translations = &drupal_static('interface_string_stats_strings', []);
    $requested_string_translations[] = $translation_request;

    return $string;
  }

  /**
   * @inheritdoc
   */
  protected function doTranslate($string, array $options = []) {
    return $this->translationManager->doTranslate($string, $options);
  }

  /**
   * @inheritdoc
   */
  public function formatPlural($count, $singular, $plural, array $args = [], array $options = []) {
    return $this->translationManager->formatPlural($count, $singular, $plural, $args, $options);
  }

  /**
   * @inheritdoc
   */
  public function setDefaultLangcode($langcode) {
    return $this->translationManager->setDefaultLangcode($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    return $this->translationManager->reset();
  }

  /**
   * Checks if we should be processing strings for this user.
   *
   * @return bool
   *   TRUE if we should process strings for this user, FALSE if not.
   */
  public function getProcessUser() {
    // Only capture strings if the setting is enabled.
    if ($this->config->get('capture') != 1) {
      return FALSE;
    }

    // Exclude roles and users we're not interested in.
    $roles = $this->currentUser->getRoles();
    $selected_roles = $this->config->get('roles');
    if (
      !is_array($selected_roles) ||
      array_intersect(array_filter($selected_roles), $roles) ||
      $this->currentUser->id() == 1 ||
      in_array(PHP_SAPI, ['cli', 'cli-server', 'phpdbg'])
    ) {
      return FALSE;
    }

    return TRUE;
  }

}
