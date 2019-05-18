<?php

namespace Drupal\bibcite;

use Drupal\bibcite\Entity\CslStyleInterface;
use Drupal\bibcite\Plugin\BibCiteProcessorInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Render CSL data to bibliographic citation.
 */
class CitationStyler implements CitationStylerInterface {

  /**
   * Processor plugin.
   *
   * @var \Drupal\bibcite\Plugin\BibCiteProcessorInterface
   */
  protected $processor;

  /**
   * Manager of processor plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Service configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configuration;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Storage of CSL style entity.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $cslStorage;

  /**
   * CSL style entity.
   *
   * @var \Drupal\bibcite\Entity\CslStyleInterface
   */
  protected $style;

  /**
   * Language code.
   *
   * @var string
   */
  protected $langCode;

  /**
   * Styler constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   Manager of processor plugins.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(PluginManagerInterface $plugin_manager, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManager = $plugin_manager;
    $this->configuration = $config_factory->get('bibcite.settings');
    $this->languageManager = $language_manager;
    $this->cslStorage = $entity_type_manager->getStorage('bibcite_csl_style');
  }

  /**
   * {@inheritdoc}
   */
  public function render($data) {
    $csl = $this->getStyle()->getCslText();
    $lang = $this->getLanguageCode();

    return $this->getProcessor()->render($data, $csl, $lang);
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessor(BibCiteProcessorInterface $processor) {
    $this->processor = $processor;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessorById($processor_id) {
    $this->processor = $this->pluginManager->createInstance($processor_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessor() {
    if (!$this->processor) {
      $this->setProcessorById($this->configuration->get('processor'));
    }

    return $this->processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableProcessors() {
    return $this->pluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableStyles() {
    return $this->cslStorage->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle() {
    if (!$this->style) {
      $this->setStyleById($this->configuration->get('default_style'));
    }

    return $this->style;
  }

  /**
   * {@inheritdoc}
   */
  public function setStyle(CslStyleInterface $csl_style) {
    $this->style = $csl_style;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStyleById($style_id) {
    $this->style = $this->cslStorage->load($style_id);

    if (!$this->style) {
      throw new \Exception('You are trying to use non-existing style.');
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageCode() {
    if (!$this->langCode) {
      $this->langCode = $this->languageManager->getCurrentLanguage()->getId();
    }

    return $this->langCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguageCode($lang_code) {
    // @todo M? $this->langCode maybe?
    $this->lang = $lang_code;
    return $this;
  }

}
