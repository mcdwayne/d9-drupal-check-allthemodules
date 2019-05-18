<?php

namespace Drupal\gtm_datalayer\Plugin;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for a GTM dataLayer Processor.
 */
class DataLayerProcessorBase extends PluginBase implements DataLayerProcessorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Generated Google Tags.
   *
   * @var array
   */
  protected $dataLayerTags = [];

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The error status code.
   *
   * @var integer
   */
  protected $statusCode = NULL;

  /**
   * Constructs a DataLayerProcessorBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current HTTP request.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $current_request, PathMatcherInterface $path_matcher, LanguageManagerInterface $language_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->currentRequest = $current_request;
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.matcher'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Global site tags.
    $this->addTag(['current_uri'], $this->currentRequest->getUri());
    $this->addTag(['language'], $this->languageManager->getCurrentLanguage()->getId());
    $this->addTag(['is_frontpage'], $this->pathMatcher->isFrontPage());

    // User related tags.
    $this->addTag(['current_user'], (int) $this->currentUser->id());
    $this->addTag(['current_user_roles'], $this->currentUser->getRoles());

    if ($this->currentRequest->attributes->has('exception')) {
      $this->statusCode = $this->currentRequest->attributes->get('exception')->getStatusCode();
      $this->addTag(['status_code'], $this->statusCode);
    }

    return $this->getTags();
  }

  /**
   * Check if the current request returns an exception.
   *
   * @return bool
   *   If the current request returns an exception.
   */
  protected function isRequestException() {
    return ($this->statusCode !== NULL) ? TRUE : FALSE;
  }

  /**
   * Sets a value in self::dataLayerTags array with variable depth.
   *
   * @param array $key
   *   An array of parent keys, starting with the outermost key.
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  protected function addTag(array $key, $value) {
    NestedArray::setValue($this->dataLayerTags, $key, $value);

    return $this;
  }

  /**
   * Retrieves a value from self::dataLayerTags array with variable depth.
   *
   * @param array $key
   *   An array of parent keys, starting with the outermost key.
   * @param $default
   *   The default value to use if this key has never been set.
   *
   * @return mixed
   *   The value to get.
   */
  protected function getTag(array $key, $default = NULL) {
    $value = NestedArray::getValue($this->dataLayerTags, $key);

    return ($value !== NULL) ? $value : $default;
  }

  /**
   * Removes a tag.
   *
   * @param array $key
   *   An array of parent keys to remove.
   *
   * @return $this
   */
  protected function removeTag(array $key) {
    $tags = $this->getTags();

    if (NestedArray::keyExists($tags, $key)) {
      NestedArray::unsetValue($tags, $key);
      $this->setTags($tags);
    }

    return $this;
  }

  /**
   * Sets multiple values in self::dataLayerTags array with variable depth.
   *
   * @param array $values
   *   The values to set.
   *
   * @return $this
   */
  protected function addTags(array $values) {
    foreach ($values as $key => $value) {
      $this->addTag([$key], $value);
    }

    return $this;
  }

  /**
   * Retrieves all values from self::dataLayerTags array.
   *
   * @return mixed
   *   The entire dataLayer tags.
   */
  protected function getTags() {
    return $this->dataLayerTags;
  }

  /**
   * Merges multiple values in self::dataLayerTags array with variable depth.
   *
   * @param array $values
   *   The values to merge.
   *
   * @return $this
   */
  protected function mergeTags(array $values) {
    foreach ($values as $key => $value) {
      $this->mergeTag([$key], $value);
    }

    return $this;
  }

  /**
   * Retrieves all tags in self::dataLayerTags array.
   *
   * @param array $value
   *   The value to set.
   *
   * @return $this
   */
  protected function setTags(array $value) {
    $this->dataLayerTags = $value;

    return $this;
  }

}
