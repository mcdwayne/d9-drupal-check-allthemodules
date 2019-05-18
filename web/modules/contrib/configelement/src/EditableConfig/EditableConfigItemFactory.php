<?php

namespace Drupal\configelement\EditableConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Class EditableConfigItemFactory
 *
 * @see \Drupal\configelement\EditableConfig\EditableConfigItem
 *
 * Note that it's important to define this service as shared:false.
 * Otherwise it symfony caches the factory, and subsequent factory instances
 * inherit a possibly dirty factory cache from the last instance.
 *
 * @package Drupal\configelement\EditableConfig
 */
class EditableConfigItemFactory {

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  private $configFactory;

  /** @var \Drupal\Core\Language\LanguageManagerInterface */
  private $languageManager;

  /** @var EditableConfigWrapperInterface[][] */
  protected $editableConfigWrapperCache = [];

  /** @var EditableConfigItemInterface[][][][] */
  protected $editableConfigItemCache = [];

  /**
   * EditableConfigItemFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   */
  public function __construct(ConfigFactoryInterface $configFactory, LanguageManagerInterface $languageManager) {
    $this->configFactory = $configFactory;
    $this->languageManager = $languageManager;
  }

  /**
   * Get an EditableConfigItem.
   *
   * @param string $name
   *   The config name.
   * @param string $key
   *   The config key.
   * @param $langcode
   *   If a langcode is given, translated config is used.
   * @param bool $fallback
   *   If a language override is used, merge the overridden config.
   *
   * @return EditableConfigItemInterface
   */
  public function get($name, $key, $langcode = NULL, $fallback = TRUE) {
    $this->normalizeLangcode($langcode);
    if (!isset($this->editableConfigItemCache[$name][$langcode][(int)$fallback][$key])) {
      $wrapper = $this->getWrapper($name, $langcode);
      if (!$wrapper->has($key)) {
        $this->normalizeLangcode($langcode, $defaultLangcode);
        $wrapper = $this->getWrapper($name, $defaultLangcode);
      }
      $item = EditableConfigItem::create($wrapper, $key);
      if ($fallback && $wrapper instanceof EditableConfigOverride) {
        $baseItem = $this->get($name, $key, NULL, FALSE);
        $fallbackItem = EditableConfigFallbackItem::create($item, $baseItem, $key);
        $this->editableConfigItemCache[$name][$langcode][(int)$fallback][$key] = $fallbackItem;
      }
      else {
        $this->editableConfigItemCache[$name][$langcode][(int)$fallback][$key] = $item;
      }
    }
    return $this->editableConfigItemCache[$name][$langcode][(int)$fallback][$key];
  }

  /**
   * Get an EditableConfigWrapper.
   *
   * @param $name
   *   The config name.
   *
   * @return EditableConfigWrapperInterface
   */
  private function getWrapper($name, $langcode) {
    $this->normalizeLangcode($langcode, $defaultLangcode);
    if (!isset($this->editableConfigWrapperCache[$name][$langcode])) {
      if (!$langcode || $langcode === $defaultLangcode) {
        $config = $this->configFactory->getEditable($name);
        $this->editableConfigWrapperCache[$name][$langcode] = EditableConfigWrapper::create($config);
      }
      else {
        $override = $this->languageManager->getLanguageConfigOverride($langcode, $name);
        $this->editableConfigWrapperCache[$name][$langcode] = EditableConfigOverride::create($override);
      }
    }
    return $this->editableConfigWrapperCache[$name][$langcode];
  }

  /**
   * @param $langcode
   * @param $defaultLangcode
   */
  private function normalizeLangcode(&$langcode, &$defaultLangcode = NULL) {
    $langcode = (string) $langcode;
    $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();
    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      if (!$langcode) {
        // With language module, every cache entry has a language.
        $langcode = $defaultLangcode;
      }
    }
    else {
      // Without languge module, we ignore language.
      $langcode = $defaultLangcode = '';
    }
  }

  /**
   * Validate values.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationList
   */
  public function validate() {
    $violations = new ConstraintViolationList();
    /** @var EditableConfigWrapperInterface $editableConfigWrapper */
    foreach ($this->editableConfigWrapperCache as $translations) {
      foreach ($translations as $editableConfigWrapper) {
        $violations->addAll($editableConfigWrapper->validate());
      }
    }
    return $violations;
  }

  /**
   * Trigger autosave.
   *
   * We chose not to do destructor magick, so this must be done explicitly.
   */
  public function save() {
    foreach ($this->editableConfigWrapperCache as $translations) {
      foreach ($translations as $editableConfigWrapper) {
        $editableConfigWrapper->save();
      }
    }
    $this->editableConfigWrapperCache = [];
    $this->editableConfigItemCache = [];
  }

}
