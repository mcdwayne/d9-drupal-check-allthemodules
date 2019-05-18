<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests installing with a different language with Configuration Overlay.
 *
 * @group config_overlay
 */
class ConfigOverlayTestingLanguageTest extends ConfigOverlayTestingTest {

  /**
   * The language to install the site in.
   *
   * @var string
   */
  protected $langcode = 'af';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    /* @see https://www.drupal.org/project/drupal/issues/2990234 */
    $this->translationFilesDirectory = $this->publicFilesDirectory . '/translations';
    mkdir($this->translationFilesDirectory, 0777, TRUE);

    // Prepare a translation file to avoid attempting to download a translation
    // file from the actual translation server during the test.
    file_put_contents("{$this->root}/{$this->translationFilesDirectory}/drupal-8.0.0.{$this->langcode}.po", '');
  }

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();
    $parameters['parameters']['langcode'] = $this->langcode;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Document this
   */
  protected function doTestRecreateInitial(ConfigEntityInterface $initial_entity) {
    $recreated_entity = $this->recreateEntity($initial_entity);

    $this->exportConfig();

    return $recreated_entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig() {
    $overridden_config = parent::getOverriddenConfig();

    // Installing in a language other than English enables the Interface
    // Translation mdoule (including its dependencies).
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module'] += [
      'field' => 0,
      'file' => 0,
      'language' => 0,
      'locale' => 0,
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module'] = module_config_sort($overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module']);

    /* @see drupal_install_system() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['default_langcode'] = $this->langcode;

    /* @see install_download_additional_translations_operations() */
    /* @see @see \Drupal\Core\Config\Entity\ConfigEntityType::getPropertiesToExport() */
    $language = ConfigurableLanguage::createFromLangcode($this->langcode);
    $config_name = 'language.entity.' . $this->langcode;
    $overridden_config[StorageInterface::DEFAULT_COLLECTION][$config_name] = [
      'uuid' => $this->configStorage->read($config_name)['uuid'],
      'langcode' => $this->langcode,
      'status' => TRUE,
      'dependencies' => [],
      'id' => $this->langcode,
      'label' => $language->label(),
      'direction' => $language->getDirection(),
      'weight' => 0,
      'locked' => FALSE,
    ];
    /* @see \Drupal\language\Entity\ConfigurableLanguage::postSave() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['language.negotiation'] = [
      'url' => [
        'prefixes' => [$this->langcode => ''],
        'domains' => [$this->langcode => ''],
      ],
    ];

    // All configuration will specify the site default language as their its
    // language while the shipped configuration specifies English.
    foreach ($this->configStorage->listAll() as $config_name) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION][$config_name]['langcode'] = $this->langcode;
    }

    return $overridden_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedConfig() {
    $expected_config = parent::getExpectedConfig();

    // This will be exported as an empty file.
    $expected_config[StorageInterface::DEFAULT_COLLECTION]['language.entity.en'] = NULL;

    unset(
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['language.negotiation']['url']['prefixes']['en'],
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['language.negotiation']['url']['domains']['en']
    );

    /* @see https://www.drupal.org/project/drupal/issues/2988960 */
    unset($expected_config[StorageInterface::DEFAULT_COLLECTION]['locale.settings']['translate_english']);

    return $expected_config;
  }

}
