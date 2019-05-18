<?php

namespace Drupal\menu_entity_index;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Tracks menu links and their referenced entities.
 */
class Tracker implements TrackerInterface {

  use DependencySerializationTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Configuration Factory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * This service's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The query factory for building entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The path processor manager service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Views data manager.
   *
   * @var \Drupal\views\ViewsData|null
   */
  protected $viewsData = NULL;

  /**
   * Constructs the Tracker object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The query factory for building entity queries.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactory $config_factory, Connection $connection, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, MenuLinkManagerInterface $menu_link_manager, InboundPathProcessorInterface $path_processor, RequestStack $request_stack, RequestMatcherInterface $router, PathValidatorInterface $path_validator) {
    $this->configFactory = $config_factory;

    // Load configuration.
    $config = $this->configFactory->get('menu_entity_index.configuration');
    if ($config->isNew()) {
      $this->messenger()->addError($this->t('The @service service has not been <a href=":url">configured</a> yet.', [
        '@service' => $this->t('Menu Entity Index Tracker'),
        ':url' => Url::fromRoute('menu_entity_index.configure')->toString(),
      ]));
    }
    $this->config = $config;

    $this->database = $connection;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
    $this->menuLinkManager = $menu_link_manager;
    $this->pathProcessor = $path_processor;
    $this->requestStack = $request_stack;
    $this->router = $router;
    $this->pathValidator = $path_validator;
  }

  /**
   * Sets optional views data manager dependency, if available.
   *
   * @param \Drupal\views\ViewsData|null $views_data
   *   The views data manager.
   */
  public function setViewsData($views_data) {
    $this->viewsData = $views_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableMenus() {
    $options = [];

    $eids = $this->entityQuery->get('menu', 'AND')->execute();
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple($eids);
    foreach ($menus as $name => $menu) {
      $options[$name] = $menu->label();
    }
    asort($options);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableEntityTypes() {
    $options = [];

    $types = $this->entityTypeManager->getDefinitions();
    foreach ($types as $type_id => $type) {
      if ($type instanceof ContentEntityType) {
        $options[$type_id] = $type->getLabel();
      }
    }

    if (isset($options['menu_link_content'])) {
      unset($options['menu_link_content']);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedMenus() {
    if ($this->config->get('all_menus')) {
      return array_keys($this->getAvailableMenus());
    }
    $menus = $this->config->get('menus');
    return array_values((array) $menus);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->get('menu_entity_index.configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $form_values = [], $force_rebuild = FALSE) {
    $entity_type_ids = !empty($form_values['entity_types']) ? array_filter($form_values['entity_types']) : [];
    $force = $force_rebuild || $this->setTrackedEntityTypes($entity_type_ids);

    if ($form_values['all_menus']) {
      $this->setTrackAllMenus($force);
    }
    elseif (!empty($form_values['menus'])) {
      $menus = array_filter($form_values['menus']);
      $this->setTrackedMenus($menus, $force);
    }
    // Clear views table data cache.
    if (is_object($this->viewsData)) {
      $this->viewsData->clear();
    }
  }

  /**
   * Sets all menus option to track and update database table accordingly.
   *
   * @param bool $force
   *   Retrack all tracked menus, even if configuration didn't change. Default
   *   is FALSE.
   */
  protected function setTrackAllMenus($force = FALSE) {
    $old_value = $this->config->get('all_menus');
    $this->configFactory->getEditable('menu_entity_index.configuration')
      ->set('all_menus', TRUE)
      ->set('menus', [])
      ->save();
    if (!$old_value || $force) {
      $this->untrackMenus($this->getTrackedMenus());
      $this->trackMenus($this->getTrackedMenus());
    }
  }

  /**
   * Sets menus to track and updates database table accordingly.
   *
   * @param array $menus
   *   Menu names to track.
   * @param bool $force
   *   Retrack all tracked menus, even, if menu configuration didn't change.
   *   Default is FALSE.
   */
  protected function setTrackedMenus(array $menus = [], $force = FALSE) {
    $old_values = $this->getTrackedMenus();
    $this->configFactory->getEditable('menu_entity_index.configuration')
      ->set('all_menus', FALSE)
      ->set('menus', $menus)
      ->save();
    $this->config = $this->configFactory->get('menu_entity_index.configuration');
    if ($force) {
      // If we force a rebuild, make sure we untrack all existing indexed items.
      $this->untrackMenus(array_keys($this->getAvailableMenus()));
      $this->trackMenus($this->getTrackedMenus());
    }
    else {
      $new_menus = (array) array_diff($menus, $old_values);
      $this->untrackMenus((array) array_diff($old_values, $menus));
      $this->trackMenus($new_menus);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackedEntityTypes() {
    $entity_type_ids = $this->config->get('entity_types');
    return array_values((array) $entity_type_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function isTrackedEntityType(EntityTypeInterface $type) {
    return $type instanceof ContentEntityType && in_array($type->id(), $this->getTrackedEntityTypes());
  }

  /**
   * Sets entity types to track and updates database table accordingly.
   *
   * @param array $entity_type_ids
   *   Entity Type Ids to track.
   */
  protected function setTrackedEntityTypes(array $entity_type_ids = []) {
    $values = $this->config->get('entity_types');
    $this->configFactory->getEditable('menu_entity_index.configuration')->set('entity_types', $entity_type_ids)->save();
    $this->config = $this->configFactory->get('menu_entity_index.configuration');
    $this->untrackEntityTypeIds((array) array_diff($values, $entity_type_ids));
    $new_types = (array) array_diff($entity_type_ids, $values);
    return count($new_types) > 0;
  }

  /**
   * Scans menu links in menus for references to target entities via Batch API.
   *
   * @param array $menus
   *   Menu names to scan.
   */
  protected function trackMenus(array $menus = []) {
    if (empty($menus)) {
      return;
    }

    $operations = [];
    foreach ($menus as $menu) {
      $arguments = [[$menu]];
      $operations[] = ['menu_entity_index_track_batch', $arguments];
    }
    batch_set([
      'title' => $this->t('Scanning menu links'),
      'operations' => $operations,
      'file' => drupal_get_path('module', 'menu_entity_index') . '/menu_entity_index.batch.inc',
    ]);
  }

  /**
   * Deletes all tracked records for menus.
   *
   * @param array $menu_names
   *   Menu names to delete records for.
   */
  protected function untrackMenus(array $menu_names = []) {
    if (empty($menu_names)) {
      return;
    }
    $this->database->delete('menu_entity_index')
      ->condition('menu_name', (array) $menu_names, 'IN')
      ->execute();
  }

  /**
   * Deletes all tracked records for target entity types.
   *
   * @param array $entity_type_ids
   *   Target entity type ids to delete records for.
   */
  protected function untrackEntityTypeIds(array $entity_type_ids = []) {
    if (empty($entity_type_ids)) {
      return;
    }
    $this->database->delete('menu_entity_index')
      ->condition('target_type', (array) $entity_type_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntity(EntityInterface $entity) {
    // Process menu links only.
    if ($entity->getEntityTypeId() !== 'menu_link_content') {
      return;
    }
    // Process menu links in tracked menus only.
    if (!in_array($entity->getMenuName(), $this->getTrackedMenus())) {
      return;
    }

    $query = $this->database->delete('menu_entity_index')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id());

    if ($entity->getEntityType()->hasKey('langcode')) {
      $query->condition('langcode', $entity->language()->getId());
    }
    $query->execute();
  }

  /**
   * Checks, if an entity is translatable.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE, if entity is translatable. Otherwise FALSE.
   */
  protected function isEntityTranslatable(EntityInterface $entity) {
    // @todo: Decide what to do with the default translation check.
    return $entity instanceof TranslatableInterface && $entity->isTranslatable();/* && !$entity->isDefaultTranslation()*/
  }

  /**
   * Matches an absolute URL in the router.
   *
   * @param string $url
   *   The absolute URL to match.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForUrl($url) {
    // Work around 1433996, 2070185, 2529170, 2548095, 2568773, 2753591 and
    // other related core issues.
    $current_request = $this->requestStack->getCurrentRequest();
    if (strpos($url, $current_request->getSchemeAndHttpHost()) === 0) {
      $url = substr($url, strlen($current_request->getSchemeAndHttpHost()));
    }
    if (!empty($current_request->getBaseUrl()) && strpos($url, $current_request->getBaseUrl()) === 0) {
      $url = substr($url, strlen($current_request->getBaseUrl()));
    }

    // Don't try to track external URLs.
    $validated_url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($url);
    if (!$validated_url || $validated_url->isExternal()) {
      return NULL;
    }

    $request = Request::create($url);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($url, $request);
    if (empty($processed)) {
      // This resolves to the front page.
      return NULL;
    }
    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntity(EntityInterface $entity) {
    // Process menu links only.
    if ($entity->getEntityTypeId() !== 'menu_link_content') {
      return;
    }
    // Process menu links in tracked menus only.
    if (!in_array($entity->getMenuName(), $this->getTrackedMenus())) {
      return;
    }

    // Delete any existing references for this host entity from db.
    if (!$entity->isNew()) {
      $this->deleteEntity($entity);
    }

    $targets = [];

    // Get a route match object for the target path of the menu link, so that we
    // can get a parameter bag.
    try {
      $url = $entity->getUrlObject();
      if (!$url->isExternal()) {
        $url->setOption('absolute', TRUE);
      }
      $url = $url->toString();
    }
    catch (\InvalidArgumentException $e) {
      return;
    }
    $route_request = $this->getRequestForUrl($url);
    if ($route_request) {
      $route_match = RouteMatch::createFromRequest($route_request);
      $parameters = $route_match->getParameters();
      // Check, if any parameters are content entities, that we want to track.
      foreach ($parameters as $parameter) {
        if ($parameter instanceof ContentEntityInterface) {
          if (in_array($parameter->getEntityTypeId(), $this->getTrackedEntityTypes())) {
            // This is a target entity we want to track.
            if (!$this->isEntityTranslatable($parameter)) {
              $targets[] = $parameter;
            }
            else {
              if ($this->isEntityTranslatable($entity)) {
                $targets[] = $parameter->hasTranslation($entity->language()->getId()) ? $parameter->getTranslation($entity->language()->getId()) : $parameter;
              }
              else {
                foreach ($parameter->getTranslationLanguages() as $language) {
                  if ($entity->language()->getId() === $language->getId()) {
                    $targets[] = $parameter->getTranslation($entity->language()->getId());
                  }
                }
              }
            }
          }
        }
      }
    }

    // Add new records to database, if any.
    if (count($targets) > 0) {
      $this->addEntityTargets($entity, $targets);
    }
  }

  /**
   * Inserts a database entry for each target entity of the given host entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The host entity for which to add tracking records.
   * @param array $targets
   *   An array of target entities.
   *
   * @return int|bool
   *   The last insert ID of the query or FALSE if no targets were provided.
   */
  protected function addEntityTargets(EntityInterface $entity, array $targets = []) {
    if (empty($targets)) {
      return FALSE;
    }

    $parent_link = NULL;
    $parent_entity = NULL;
    $parent_id = $entity->getParentId();
    if (!empty($parent_id) && $this->menuLinkManager->hasDefinition($parent_id)) {
      $parent_link = $this->menuLinkManager->createInstance($parent_id);
      if ($parent_link->getBaseId() == 'menu_link_content') {
        $parent_entity = $this->entityRepository->loadEntityByUuid($parent_link->getBaseId(), $parent_link->getDerivativeId());
      }
      elseif ($parent_link->getBaseId() == 'views_view') {
        $parent_entity = $parent_link->loadView();
      }
    }
    $menu_link = $this->menuLinkManager->createInstance($entity->getPluginId());
    $host_values = [
      'menu_name' => $entity->getMenuName(),
      'level' => $menu_link ? $this->getMenuLinkLevel($menu_link) : 0,
      'entity_type' => $entity->getEntityTypeId(),
      'entity_subtype' => $entity->bundle(),
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'parent_type' => $parent_link ? $parent_link->getBaseId() : '',
      'parent_id' => $parent_entity ? $parent_entity->id() : NULL,
      'parent_uuid' => $parent_link ? $parent_link->getDerivativeId() : '',
      'langcode' => $entity->getEntityType()->hasKey('langcode') ? $entity->language()->getId() : '',
    ];
    $query = $this->database->insert('menu_entity_index')
      ->fields([
        'menu_name',
        'level',
        'entity_type',
        'entity_subtype',
        'entity_id',
        'entity_uuid',
        'parent_type',
        'parent_id',
        'parent_uuid',
        'langcode',
        'target_type',
        'target_subtype',
        'target_id',
        'target_uuid',
        'target_langcode',
      ]);
    foreach ($targets as $target_entity) {
      $values = [
        'target_type' => $target_entity->getEntityTypeId(),
        'target_subtype' => $target_entity->bundle(),
        'target_id' => $target_entity->id(),
        'target_uuid' => $target_entity->uuid(),
        'target_langcode' => $target_entity->getEntityType()->hasKey('langcode') ? $target_entity->language()->getId() : '',
      ];
      $query->values($values + $host_values);
    }
    return $query->execute();
  }

  /**
   * Gets menu level of a menu link.
   *
   * Recursive method.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link
   *   The menu link plugin to get the level for.
   * @param int $level
   *   Used internally to track level during recursive calls.
   *
   * @return int
   *   Menu level of the menu link.
   */
  protected function getMenuLinkLevel(MenuLinkInterface $menu_link, $level = 0) {
    $parent_id = $menu_link->getParent();
    if (!empty($parent_id) && $this->menuLinkManager->hasDefinition($parent_id)) {
      $parent_link = $this->menuLinkManager->createInstance($parent_id);
      if ($parent_link) {
        return $this->getMenuLinkLevel($parent_link, ($level + 1));
      }
      return $level;
    }
    else {
      return $level;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHostData(EntityInterface $entity) {
    $data = [];

    $type = $entity->getEntityTypeId();
    if (in_array($entity->getEntityTypeId(), $this->getTrackedEntityTypes())) {
      $id = $entity->id();
      $result = $this->database->select('menu_entity_index')
        ->fields('menu_entity_index', [
          'entity_type',
          'entity_id',
          'menu_name',
          'level',
          'langcode',
        ])
        ->condition('target_type', $type)
        ->condition('target_id', $id)
        ->orderBy('menu_name', 'ASC')
        ->orderBy('level', 'ASC')
        ->execute();
      $menus = [];
      foreach ($result as $row) {
        if (!isset($menus[$row->menu_name])) {
          $entity = $this->entityTypeManager->getStorage('menu')->load($row->menu_name);
          $menus[$row->menu_name] = $entity->label();
        }
        $entity = $this->entityTypeManager->getStorage($row->entity_type)->load($row->entity_id);
        if ($entity) {
          $data[] = [
            'menu_name' => $menus[$row->menu_name],
            'level' => $row->level,
            'label' => $entity->getTitle(),
            'link' => $entity->access('view') ? $entity->toUrl() : '',
            'language' => $entity->language()->getName(),
          ];
        }
      }
    }

    return $data;
  }

}
