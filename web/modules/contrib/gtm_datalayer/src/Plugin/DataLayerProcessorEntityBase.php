<?php

namespace Drupal\gtm_datalayer\Plugin;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a entity base class for a GTM dataLayer Processor.
 */
class DataLayerProcessorEntityBase extends DataLayerProcessorBase {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity = NULL;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a DataLayerProcessorEntityBase object.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currently active route match object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $current_request, PathMatcherInterface $path_matcher, LanguageManagerInterface $language_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $current_route_match, DateFormatterInterface $date_formatter, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_request, $path_matcher, $language_manager, $current_user);

    $this->currentRouteMatch = $current_route_match;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->token = $token;
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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('date.formatter'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    parent::render();

    $this->getEntityFromContext();

    if (!$this->isRequestException() && $this->getEntity() instanceof EntityInterface) {
      $this->initializeRenderers();

      // Global entity properties.
      $this->addTag(['entity_type'], $this->getEntity()->getEntityTypeId());
      $this->addTag(['entity_bundle'], $this->getEntity()->bundle());
      $this->addTag(['entity_id'], (int) $this->getEntity()->id());
      $this->addTag(['entity_label'], $this->getEntity()->label());
      $this->addTag(['entity_canonical'], $this->getEntity()->toUrl('canonical', ['absolute' => TRUE])->toString());

      // Changed entity property.
      if (is_subclass_of($this->getEntity()->getEntityType()->getClass(), EntityChangedInterface::class) &&
        $this->getEntity()->getEntityType()->hasKey('changed')) {
        $this->addTag(['entity_changed'], $this->dateFormatter->format($this->getEntity()->getChangedTime(), 'gtm_datalayer'));
      }

      // Published entity property.
      if (is_subclass_of($this->getEntity()->getEntityType()->getClass(), EntityPublishedInterface::class) &&
        $this->getEntity()->getEntityType()->hasKey('published')) {
        $this->addTag(['entity_published'], $this->getEntity()->isPublished());
      }

      // Entity properties and fields.
      // @todo Add support for non fieldable entities and bundle plugin types.
      if ($this->getEntity() instanceof FieldableEntityInterface) {
        foreach ($this->getEntity()->getFieldDefinitions() as $field_definition) {
          $method = 'renderFieldType' . str_replace('_', '', Unicode::ucwords($field_definition->getType()));

          if (method_exists($this, $method)) {
            call_user_func([$this, $method], $field_definition, $this->getEntity()->get($field_definition->getName()));
          }
        }
      }
    }

    return $this->getTags();
  }

  /**
   * Initialize field renderers.
   */
  protected function initializeRenderers() {
    // Nothing to do.
  }

  /**
   * Gets the entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the entity object.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity object.
   *
   * @return $this
   */
  protected function setEntity($entity) {
    $this->entity = $entity;

    return $this;
  }

  /**
   * Provides the entity object.
   *
   * @return mixed \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getEntityFromContext() {
    // Nothing to do.
    return NULL;
  }

  /**
   * Gets the entity type storage.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getEntityStorage(string $entity_type) {
    return $this->entityTypeManager->getStorage($entity_type);
  }

}
