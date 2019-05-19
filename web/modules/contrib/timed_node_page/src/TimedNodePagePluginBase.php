<?php

namespace Drupal\timed_node_page;

use Drupal\timed_node_page\Repository\TimedNodeRepositoryInterface;
use Drupal\timed_node_page\Service\TimedNodeDateFormatter;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for the timed node page plugin.
 *
 * @package Drupal\node_form_delegate
 */
abstract class TimedNodePagePluginBase extends PluginBase implements TimedNodePagePluginInterface, ContainerFactoryPluginInterface {

  const SECONDS_IN_DAY = 86400;

  /**
   * The default storage timezone.
   *
   * @var \DateTimeZone
   */
  protected $defaultTimezone;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The timed node repository service.
   *
   * @var \Drupal\timed_node_page\Repository\TimedNodeRepositoryInterface
   */
  protected $timedNodeRepository;

  /**
   * The timed node date formatter service.
   *
   * @var \Drupal\timed_node_page\Service\TimedNodeDateFormatter
   */
  protected $timedNodeDateFormatter;

  /**
   * The current node.
   *
   * @var \Drupal\apsys_home_page\Plugin\Entity\Homepage
   */
  private $currentNode;

  /**
   * TimedNodePagePluginBase constructor.
   *
   * @param array $configuration
   *   See parent.
   * @param string $plugin_id
   *   See parent.
   * @param mixed $plugin_definition
   *   See parent.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   * @param \Drupal\timed_node_page\Repository\TimedNodeRepositoryInterface $timedNodeRepository
   *   The timed node repository service.
   * @param \Drupal\timed_node_page\Service\TimedNodeDateFormatter $timedNodeDateFormatter
   *   The timed node date formatter service.
   *
   * @see \Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase::massageFormValues()
   *   The datetime values storage logic.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    TimedNodeRepositoryInterface $timedNodeRepository,
    TimedNodeDateFormatter $timedNodeDateFormatter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->timedNodeRepository = $timedNodeRepository;
    $this->timedNodeDateFormatter = $timedNodeDateFormatter;
    $this->defaultTimezone = new \DateTimezone(DATETIME_STORAGE_TIMEZONE);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('timed_node_page.repository'),
      $container->get('timed_node_page.date_formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->pluginDefinition['bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStartFieldName() {
    if (!isset($this->pluginDefinition['startField'])) {
      throw new \LogicException('Start date field name is required for timed node page.');
    }

    return $this->pluginDefinition['startField'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEndFieldName() {
    if (!isset($this->pluginDefinition['endField'])) {
      return NULL;
    }

    return $this->pluginDefinition['endField'];
  }

  /**
   * Gets the start time of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The timed node.
   *
   * @return int
   *   The time.
   */
  protected function getStartTimeFor(NodeInterface $node) {
    return strtotime($node->get($this->getStartFieldName())->getString());
  }

  /**
   * Gets the end time of the given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The timed node.
   *
   * @return int
   *   The time.
   */
  protected function getEndTimeFor(NodeInterface $node) {
    $endFieldName = $this->getEndFieldName();
    $endTime = strtotime($node->get($endFieldName)->getString());

    // If the time field is only date granular then for the end time we should
    // expect that it be the end of the day.
    if ($this->timedNodeDateFormatter->getFieldType($endFieldName, $this->getBundle()) == 'date') {
      $endTime += static::SECONDS_IN_DAY - 1;
    }

    return $endTime;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentNode($langcode = '') {
    if (!$langcode) {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    if (isset($this->currentNode)) {
      // If the langcode is the same, then we have already loaded the node.
      if ($this->currentNode->language() == $langcode) {
        return $this->currentNode;
      }
      else {
        // If the node in the original language is loaded already, then we just
        // need to return the translation.
        return $this->getTranslation($this->currentNode, $langcode);
      }
    }

    $startField = $this->getStartFieldName();
    $endField = $this->getEndFieldName();
    // If the node in the original language hasn't been loaded yet, load it.
    $this->currentNode = $this->timedNodeRepository->getCurrentNode($this->getBundle(), $startField, $endField);

    return $this->currentNode instanceof NodeInterface ?
      $this->getTranslation($this->currentNode, $langcode) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextNode($langcode = '') {
    if (!$langcode) {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    if (isset($this->nextNode)) {
      // If the langcode is the same, then we have already loaded the node.
      if ($this->nextNode->language() == $langcode) {
        return $this->nextNode;
      }
      else {
        // If the node in the original language is loaded already, then we just
        // need to return the translation.
        return $this->getTranslation($this->nextNode, $langcode);
      }
    }

    $startField = $this->getStartFieldName();
    // If the node in the original language hasn't been loaded yet, load it.
    $this->nextNode = $this->timedNodeRepository->getNextNode($this->getBundle(), $startField);

    return $this->nextNode instanceof NodeInterface ?
      $this->getTranslation($this->nextNode, $langcode) : NULL;
  }

  /**
   * Gets the translation of the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The original node.
   * @param string $langcode
   *   The langcode.
   *
   * @return \Drupal\node\NodeInterface
   *   The translated node.
   */
  private function getTranslation(NodeInterface $node, $langcode) {
    return $node->hasTranslation($langcode) ?
      $node->getTranslation($langcode) :
      $node;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    $currentTime = time();
    $next = $this->getNextNode();

    if ($endField = $this->getEndFieldName()) {
      $current = $this->getCurrentNode();
      $currentEndTime = $current ? $this->getEndTimeFor($current) : NULL;
    }

    // If we have no next node timed then the max age should be infinite.
    if (!$next) {

      if (!isset($currentEndTime)) {
        return Cache::PERMANENT;
      }

      // If we have end time configured then it should last until then.
      return $currentEndTime - $currentTime;
    }

    $nextTime = $this->getStartTimeFor($next);

    // If there is also an end field configured then we have to check if it
    // expired before the previous.
    if (isset($currentEndTime) && $nextTime > $currentEndTime) {
      return $currentEndTime - $currentTime + 1;
    }

    return $nextTime - $currentTime + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function usesCustomResponse() {
    return $this->pluginDefinition['usesCustomResponse'] ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomResponse() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['node:' . $this->pluginDefinition['bundle'] . ':page'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

}
