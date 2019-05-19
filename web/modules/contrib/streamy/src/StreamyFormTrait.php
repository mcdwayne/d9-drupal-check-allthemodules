<?php

namespace Drupal\streamy;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait StreamyFormTrait.
 *
 * @package Drupal\streamy_ui\Form
 */
trait StreamyFormTrait {

  /**
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * @var \Drupal\streamy\StreamyStreamManager
   */
  protected $streamyStreamManager;

  /**
   * @var \Drupal\streamy\StreamyCDNManager
   */
  protected $streamyCDNManager;

  /**
   * Must set $streamyFactory.
   *
   * @return mixed
   */
  abstract protected function setUp();

  /**
   * @param array                                $keysToParse
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param bool                                 $nestedLevel
   * @return array
   */
  protected function buildNestedPluginConfiguration(array $keysToParse, FormStateInterface $form_state, $nestedLevel = TRUE) {
    $schemes = $this->streamyFactory->getSchemesSettings();
    $levels = $this->streamyFactory->getSchemeLevels();

    $config = [];
    foreach ($schemes as $scheme => $streamConfig) {
      if ($nestedLevel) {
        foreach ($levels as $level) {
          foreach ($keysToParse as $key) {
            $config[$scheme][$level][$key] = $form_state->getValue([$scheme, $level, $key]);
          }
        }
      } else {
        foreach ($keysToParse as $key) {
          $config[$scheme][$key] = $form_state->getValue([$scheme, $key]);
        }
      }
    }
    return $config;
  }

  /**
   * @param $schemeConfig
   * @return bool
   */
  protected function schemeIsPrivate($schemeConfig) {
    return isset($schemeConfig['private']) &&
           $schemeConfig['private'] === TRUE ? TRUE : FALSE;
  }

  /**
   * @param string $scheme
   * @param string $pluginName
   * @param array  $tmpConfig
   * @return bool|mixed
   */
  protected function ensureStreamyStreamPlugin(string $scheme, $level, $pluginName, array $tmpConfig = []) {
    if ($this->streamyStreamManager->hasDefinition($pluginName)) {
      $plugin = $this->streamyStreamManager->createInstance($pluginName);
      return $plugin->ensure($scheme, $level, $tmpConfig);
    }
    return FALSE;
  }

  /**
   * @param string $scheme
   * @param string $pluginName
   * @param array  $tmpConfig
   * @return bool
   */
  protected function ensureStreamyCDNPlugin(string $scheme, $pluginName, array $tmpConfig = []) {
    if ($this->streamyCDNManager->hasDefinition($pluginName)) {
      $plugin = $this->streamyCDNManager->createInstance($pluginName);
      return $plugin->ensure($scheme, $tmpConfig);
    }
    return FALSE;
  }

  /**
   * @param $name
   * @param $schemeConfig
   * @return mixed|null
   */
  protected function getSchemeSetting($name, $schemeConfig) {
    return (isset($schemeConfig[$name]) ? $schemeConfig[$name] :
      NULL);
  }

  /**
   * @param string $key
   * @param string $scheme
   * @param string $level
   * @param array  $config
   * @return null
   */
  protected function getPluginConfigurationValue(string $key, string $scheme, string $level, array $config) {
    return (isset($config[$scheme][$level]) && isset($config[$scheme][$level][$key]) ? $config[$scheme][$level][$key] : NULL);
  }

  /**
   * @param string $key
   * @param string $scheme
   * @param array  $config
   * @return null
   */
  protected function getPluginConfigurationSingleValue(string $key, string $scheme, array $config) {
    return (isset($config[$scheme]) && isset($config[$scheme][$key]) ? $config[$scheme][$key] : NULL);
  }

  /**
   * Check if this schema has become mandatory
   * Manually check if the current stream has been filled in any of the fields.
   * If so we must consider this as a usable set of config.
   *
   * @param array $valuesToCheck
   * @param array $keys
   * @return bool
   */
  protected function checkIfAnyOfThisValuesIsFilled(array $valuesToCheck, array $keys = []) {
    $values = [];
    foreach ($keys as $keyToInclude) {
      if (isset($valuesToCheck[$keyToInclude])) {
        $values[] = $valuesToCheck[$keyToInclude];
      }
    }

    foreach ($values as $val) {
      if (!empty($val)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * @param $scheme
   * @return string
   */
  protected function getProtocol($scheme) {
    return $scheme . '://';
  }
}
