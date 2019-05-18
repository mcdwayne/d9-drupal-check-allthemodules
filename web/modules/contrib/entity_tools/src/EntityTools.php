<?php

namespace Drupal\entity_tools;

use Drupal\user\Entity\Role;
use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
// Use Drupal\Core\Entity\Query\Sql\QueryFactory;.
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Entity\View;

/**
 * Class EntityTools.
 *
 * Facade on fetching and displaying Content and Config Entity.
 *
 * @package Drupal\entity_tools
 */
class EntityTools {

  // Content or Config Entity Type id
  // content.
  const ENTITY_NODE = 'node';

  // Content.
  const ENTITY_TERM = 'taxonomy_term';

  // Content.
  const ENTITY_USER = 'user';

  // Content.
  const ENTITY_MEDIA = 'media';

  // Content.
  const ENTITY_BLOCK_CONTENT = 'block_content';

  const ENTITY_BLOCK = 'block';

  // Config.
  const ENTITY_VOCABULARY = 'taxonomy_vocabulary';

  // Config.
  const ENTITY_VIEWS = 'view';

  // @todo define other content or config entities
  // @todo refactor to allow method composition and method chaining load->display
  // @todo use other "display" : json, xls, ... rename Display into Html or Build?
  // @todo provide facade on most commonly used caching (including max-age 0)
  // @todo check access

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager, LanguageManagerInterface $language_manager, Renderer $renderer, QueryFactory $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    $this->entityQuery = $entity_query;
  }

  /**
   * Factory that instantiates an entity query with default values.
   *
   * It merely decorates the entityQuery provided by the core
   * - provides default values
   * - provides a facade on several content and config entities
   * - the core entity query should still be available from the client.
   *
   * - Current interface language used for translation if site is multilingual.
   * - Visibility of entity:
   *   - published status for Node
   *   - ...
   * - Do not bypass content access by default
   *   - permission to view user profile for current user
   *   - ...
   *
   * @param string $entity_type_id
   *   Entity type id.
   *
   * @return \Drupal\entity_tools\EntityQueryInterface
   *   Entity query.
   */
  public function getEntityQuery($entity_type_id) {
    $query = NULL;
    switch ($entity_type_id) {
      case self::ENTITY_NODE:
        $query = new NodeQuery();
        break;

      case self::ENTITY_TERM:
        $query = new TermQuery();
        break;

      case self::ENTITY_USER:
        $query = new UserQuery();
        break;

      case self::ENTITY_BLOCK_CONTENT:
        $query = new BlockContentQuery();
        break;

      // Used for not yet implemented entity (group, media, ...)
      default:
        $query = new GenericQuery($entity_type_id);
        break;

      // @todo implement other entity types
      //   case self::ENTITY_MEDIA:
      //        break;
      //
      //      case self::ENTITY_VOCABULARY:
      //        break;
      //
      //      case self::ENTITY_VIEWS:
      //        break;
    }
    return $query;
  }

  /**
   * Returns an array of Entity objects.
   *
   * @param string $entity_type_id
   *   Content (node, term, view, ...) or configuration (vocabulary, ...) entity.
   * @param string|null $type
   *   Optional Content Entity type.
   * @param \Drupal\entity_tools\EntityQueryInterface|null $query
   *   Optional query to filter and sort the entity selection.
   *
   * @return array
   *   Array of Content Entity ids.
   */
  public function getEntities($entity_type_id, $type = NULL, EntityQueryInterface $query = NULL) {
    $entityQuery = NULL;
    if (!isset($query)) {
      $entityQuery = $this->getEntityQuery($entity_type_id);
    }
    elseif ($query instanceof EntityQueryInterface) {
      $entityQuery = $query;
    }
    else {
      throw new \Exception(t('Entity query not set.'));
    }
    // Optionally add extra query attribute.
    if (isset($type)) {
      $entityQuery->setType($type);
    }
    $ids = $entityQuery->execute();
    $result = $this->entityLoadMultiple($entity_type_id, $ids);
    return $result;
  }

  /**
   * Returns an array of Node objects.
   *
   * @param string $content_type
   *   Content type, also known as bundle.
   * @param \Drupal\entity_tools\EntityQueryInterface|null $query
   *   Entity query.
   *
   * @return array
   *   Array of Nodes.
   */
  public function getNodes($content_type, EntityQueryInterface $query = NULL) {
    return $this->getEntities(self::ENTITY_NODE, $content_type, $query);
  }

  /**
   * Returns an array of Term objects.
   *
   * See also loadVocabulary method.
   *
   * @param string $vocabulary
   *   Vocabulary name.
   * @param \Drupal\entity_tools\EntityQueryInterface|null $query
   *   Entity query.
   *
   * @return array
   *   Array of Terms.
   */
  public function getTerms($vocabulary, EntityQueryInterface $query = NULL) {
    return $this->getEntities(self::ENTITY_TERM, $vocabulary, $query);
  }

  /**
   *
   */
  public function getBlockContents($block_type, EntityQueryInterface $query = NULL) {
    return $this->getEntities(self::ENTITY_BLOCK_CONTENT, $query);
  }

  /**
   *
   */
  public function getUsers($role = NULL, EntityQueryInterface $query = NULL) {
    return $this->getEntities(self::ENTITY_USER, $role, $query);
  }

  /**
   * Returns an array of role ids that contain the given permission.
   *
   * @param string $permission
   *   The permission machine name.
   * @param bool $reset
   *   Reset cache.
   *
   * @return array
   *   Array of role ids.
   */
  public function getRolesByPermission($permission, $reset = FALSE) {
    $roles = &drupal_static(__FUNCTION__, []);

    if ($reset) {
      $roles = [];
    }
    if (!isset($roles[$permission]) && $permission) {
      $user_roles = Role::loadMultiple();
      foreach ($user_roles as $role) {
        if ($role->hasPermission($permission)) {
          $roles[$permission][] = $role->id();
        }
      }
    }
    return isset($roles[$permission]) ? $roles[$permission] : [];
  }

  /**
   * Selects and loads users that holds a permission.
   *
   * This cannot be covered by an EntityQuery
   * It iterates through roles to get the ones that are
   * containing the permission.
   * Then it loads the users for theses roles and reduces duplicates.
   *
   * @param string $permission
   *   Permission that can be found in a yml definition.
   *
   * @return array
   *   Array of User entities.
   */
  public function getUsersByPermission($permission) {
    $result = [];
    // @todo refactor by defining a RoleQuery->setPermission, use getRolesByPermission
    $roles = $this->getRoles();
    $roleIdsWithPermission = [];
    foreach ($roles as $role) {
      if ($role instanceof
      Role
        && $role->hasPermission($permission)) {
        $roleIdsWithPermission[] = $role->id();
      }
    }

    // If the role authenticated has the permission, do not iterate through roles
    // because other roles will inherit the permissions.
    if (in_array('authenticated', $roleIdsWithPermission)) {
      $result = $this->getUsers();
    }
    else {
      foreach ($roleIdsWithPermission as $roleId) {
        $users = $this->getUsers($roleId);
        foreach ($users as $user) {
          // Reduce possible duplicates with id key.
          $result[$user->id()] = $user;
        }
      }
    }

    return $result;
  }

  /**
   *
   */
  public function getRoles(EntityQueryInterface $query = NULL) {
    // @todo refactor
    return Role::loadMultiple();
  }

  /**
   *
   */
  public function getVocabularies(EntityQueryInterface $query = NULL) {
    // @todo implement
  }

  /**
   *
   */
  public function getMedias($media_type, EntityQueryInterface $query = NULL) {
    // @todo implement
  }

  /**
   *
   */
  public function getViews(EntityQueryInterface $query = NULL) {
    // @todo refactor
    return View::loadMultiple();
  }

  /**
   * Returns an executed View ConfigurationEntity.
   *
   * @param string $viewName
   *   View name.
   * @param string $display
   *   View display.
   * @param array $args
   *   Optional View arguments.
   *
   * @return todo|null
   *   Executable view
   */
  public function getExecutableView($viewName, $display = 'default', $args = []) {
    $result = NULL;
    $view = $this->entityTypeManager->getStorage('view')->load($viewName);
    if ($view instanceof View) {
      $result = $view->getExecutable();
      $result->setDisplay($display);
      $result->preExecute();
      $result->setArguments($args);
      $result->execute();
    }
    return $result;
  }

  /**
   * Use an executable View to fetch entities.
   *
   * @param string $viewName
   *   View name.
   * @param string $display
   *   View display.
   * @param array $args
   *   Optional View arguments.
   *
   * @return array \Drupal\views\ResultRow[]
   *   Array of ResultRows.
   */
  public function getViewResult($viewName, $display = 'default', $args = []) {
    $result = [];
    $executableView = $this->getExecutableView($viewName, $display = 'default', $args = []);
    if (isset($executableView) && $executableView) {
      $result = $executableView->result;
    }
    return $result;
  }

  /**
   *
   */
  public function getViewDisplay($viewName, $display = 'default', $args = []) {
    $result = NULL;
    $executableView = $this->getExecutableView($viewName, $display = 'default', $args = []);
    if (isset($executableView) && $executableView) {
      $result = $executableView->buildRenderable();
    }
    return $result;
  }

  /**
   * Loads multiple entities, with default translation
   * to the current interface language.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param array $entity_ids
   *   Array of entity ids.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   */
  public function entityLoadMultiple($entity_type_id, array $entity_ids) {
    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple($entity_ids);
    $result = $this->entityTranslateMultiple($entities);
    return $result;
  }

  /**
   * Loads a single Entity, with default translation
   * to the current interface language.
   *
   * @param $entity_type_id
   * @param $entity_id
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function entityLoad($entity_type_id, $entity_id) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $result = $this->entityTranslate($entity);
    return $result;
  }

  /**
   * Loads multiple Entities.
   *
   * @param array $node_ids
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   */
  public function nodeLoadMultiple(array $node_ids) {
    return $this->entityLoadMultiple(self::ENTITY_NODE, $node_ids);
  }

  /**
   * Loads a Node Entity.
   *
   * @param int $id
   *   The Node Entity id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   */
  public function nodeLoad($id) {
    return $this->entityLoad(self::ENTITY_NODE, $id);
  }

  /**
   * Loads a Term Entity.
   *
   * @param int $id
   *   The Term Entity id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   */
  public function termLoad($id) {
    return $this->entityLoad(self::ENTITY_TERM, $id);
  }

  /**
   * Loads a Block that is defined via the UI.
   *
   * @param $id
   *   The Block machine name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function blockLoad($id) {
    // @todo translation
    return $this->entityTypeManager->getStorage(self::ENTITY_BLOCK)->load($id);
    // Return $this->entityLoad(self::ENTITY_BLOCK, $id);.
  }

  /**
   * Loads a Block Content.
   *
   * @param int $id
   *   The BlockContent instance id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function blockContentLoad($id) {
    // @todo set title
    return $this->entityLoad(self::ENTITY_BLOCK_CONTENT, $id);
  }

  /**
   * Loads paragraphs from a Content Entity field.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   * @param $paragraph_field_name
   *
   * @return null
   */
  public function entityParagraphsLoad(ContentEntityBase $entity, $paragraph_field_name) {
    $result = NULL;
    if ($entity->hasField($paragraph_field_name)) {
      $paragraphField = $entity->get($paragraph_field_name);
      $referencedEntities = NULL;
      // @todo cast
      if ($paragraphField) {
        $referencedEntities = $paragraphField->referencedEntities();
        $result = $this->entityTranslateMultiple($referencedEntities);
      }
    }
    else {
      drupal_set_message(t('No paragraph field found for field name @name', ['@name' => $paragraph_field_name]), 'error');
    }
    return $result;
  }

  /**
   * Loads and builds a render array of an Entity, using a view mode.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string|null $view_mode
   *
   * @return array
   */
  public function entityDisplay(EntityInterface $entity, $view_mode = 'full') {
    $result = NULL;
    if ($entity && $this->entityAccess($entity)) {
      $viewBuilder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      // @todo check setTranslationMode()
      // $result = $viewBuilder->view($entity, $view_mode, $this->languageManager->getCurrentLanguage());
      $result = $viewBuilder->view($entity, $view_mode);
    }
    return $result;
  }

  /**
   * Loads and builds a render array of a Node, using a view mode.
   *
   * @param int $node_id
   *   Node id.
   * @param string $view_mode
   *   View mode.
   *
   * @return array
   */
  public function nodeDisplay($id, $view_mode = 'full') {
    $result = NULL;
    $node = $this->nodeLoad($id);
    if ($node instanceof Node) {
      $result = $this->entityDisplay($node, $view_mode);
    }
    return $result;
  }

  /**
   * @todo generalize with referencedEntities
   */
  public function entityParagraphsListDisplay(ContentEntityBase $entity, $paragraph_field_name, $view_mode = 'default', $attributes = []) {
    $result = NULL;
    $paragraphs = $this->entityParagraphsLoad($entity, $paragraph_field_name);
    if (!empty($paragraphs)) {
      $result = $this->entitiesListDisplay($paragraphs, $view_mode, $attributes);
    }
    return $result;
  }

  /**
   * Loads and builds a render array for a Block that is defined via the UI.
   *
   * @param string $id
   *   The Block machine name.
   *
   * @return array|null
   */
  public function blockDisplay($id) {
    $result = NULL;
    $block = $this->blockLoad($id);
    if ($block instanceof Block) {
      $result = $this->entityDisplay($block, 'block');
    }
    return $result;
  }

  /**
   * Loads and builds a render array for a Block Content.
   *
   * @param int $id
   *   The BlockContent instance id.
   *
   * @return array|null
   */
  public function blockContentDisplay($id) {
    $result = NULL;
    $block = $this->blockContentLoad($id);
    if ($block instanceof BlockContent) {
      $result = $this->entityDisplay($block, 'block_content');
    }
    return $result;
  }

  /**
   * Instantiates and builds a render array for a Block Plugin.
   *
   * Block Plugins are not really entities, but this helper still makes
   * sense in the context of the module use case.
   *
   * @param $block_id
   * @param array $config
   *
   * @see https://drupal.stackexchange.com/questions/171686/how-can-i-programmatically-display-a-block
   *
   * @return array
   */
  public function blockPluginDisplay($id, array $config = []) {
    // @todo refactor, separate load and display
    $blockManager = \Drupal::service('plugin.manager.block');
    $pluginBlock = $blockManager->createInstance($id, $config);
    // Some blocks might implement access check.
    $access_result = $pluginBlock->access(\Drupal::currentUser());
    // Return empty render array if user doesn't have access.
    // $access_result can be boolean or an AccessResult class.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      // You might need to add some cache tags/contexts.
      return [];
    }
    $render = $pluginBlock->build();
    // In some cases, you need to add the cache tags/context depending on
    // the block implemention. As it's possible to add the cache tags and
    // contexts in the render method and in ::getCacheTags and
    // ::getCacheContexts methods.
    return $render;
  }

  /**
   * Get Block Plugin definitions.
   *
   * Lists all available block plugins defined by modules,
   * even if they are not instantiated.
   *
   * @return mixed
   */
  public function getBlockPluginsDefinitions() {
    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');
    /** @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository */
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());
    return $definitions;
  }

  /**
   *
   */
  public function viewDisplay($name, $display = 'default') {
    return views_embed_view($name, $display);
  }

  /**
   * Builds a form.
   *
   * @param $form_class
   * @param $params
   */
  public function formDisplay($form_class, $params = []) {
    $result = NULL;
    if (class_exists($form_class)) {
      $result = \Drupal::formBuilder()->getForm($form_class, $params);
    }
    return $result;
  }

  /**
   *
   */
  public function webformDisplay($webform_id) {
    // @todo implement
    // @todo if module is installed
    return NULL;
  }

  /**
   * Returns a HTML list.
   *
   * @param $items
   *   The list items.
   * @param array $attributes
   *   Optional attributes : list_type (ul,li), title, list_class and
   *   item_class.
   * @param string $listKey
   *   Key used for the render array.
   *
   * @return array
   */
  public function listDisplay($items, $attributes = [], $listKey = 'html_list') {
    // @todo caching helpers using key.

    // Defaults to ul.
    $listType = 'ul';
    if (!empty($attributes['list_type']) && in_array($attributes['list_type'], [
      'ul',
      'li',
    ])) {
      $listType = $attributes['list_type'];
    }

    // List title.
    $listTitle = NULL;
    if (!empty($attributes['title'])) {
      $listTitle = $attributes['title'];
    }

    // Syntactic sugar for list and item classes.
    $listAttributes = [];
    if (!empty($attributes['list_class'])) {
      $listAttributes['class'][] = $attributes['list_class'];
    }

    $listItems = [];
    if (!empty($attributes['item_class'])) {
      foreach ($items as $item) {
        $newItem = [];
        $newItem['#markup'] = $this->renderer->render($item);
        $newItem['#wrapper_attributes']['class'] = $attributes['item_class'];
        $listItems[] = $newItem;
      }
    }
    else {
      $listItems = $items;
    }

    $build[$listKey] = [
      '#theme' => 'item_list',
      '#title' => $listTitle,
      '#items' => $listItems,
      '#type' => $listType,
      '#attributes' => $listAttributes,
    ];
    return $build;
  }

  /**
   * Returns a render array that represents an unordered list of entities.
   * for a view mode.
   *
   * @param $entities
   * @param string $view_mode
   *
   * @return array
   */
  public function entitiesListDisplay($entities, $view_mode = 'teaser', $attributes = []) {
    $items = [];
    foreach ($entities as $entity) {
      $items[] = $this->entityDisplay($entity, $view_mode);
    }
    $build = $this->listDisplay($items, $attributes, 'entity-list');
    return $build;
  }

  /**
   * Translates an Entity, with a fallback to the current interface
   * language if no language defined.
   *
   * @param $entities
   * @param null $language
   *
   * @return array
   */
  public function entityTranslateMultiple($entities, $language = NULL) {
    // @todo do not use several arrays
    $result = [];
    foreach ($entities as $entity) {
      if ($entity instanceof EntityInterface) {
        $result[] = $this->entityTranslate($entity);
      }
      else {
        $result[] = $entity;
      }
    }
    return $result;
  }

  /**
   * Translates a Content or Config Entity, with a fallback to the current interface
   * language if no language id defined.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param null $language_id
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface|\Drupal\Core\Entity\EntityInterface|\Drupal\entity_tools\EntityTools|null
   */
  public function entityTranslate(EntityInterface $entity, $language_id = NULL) {
    $result = $entity;
    if ($entity instanceof ContentEntityInterface) {
      $result = $this->contentEntityTranslate($entity, $language_id);
    }
    elseif ($entity instanceof ConfigEntityInterface) {
      $result = $this->configEntityTranslate($entity, $language_id);
    }
    return $result;
  }

  /**
   *
   */
  private function configEntityTranslate(ConfigEntityInterface $entity, $language_id = NULL) {
    // @todo implement
    return $entity;
  }

  /**
   * Translates an Entity, with a fallback to the current interface
   * language if no language id defined.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param null $language
   *
   * @return $this|\Drupal\Core\Entity\EntityInterface|null
   */
  private function contentEntityTranslate(ContentEntityInterface $entity, $language_id = NULL) {
    // @todo run extra checks to know if content translation is available for nodes, ...
    // Always fallback to source language so this method can safely be called
    // without any testing in the client.
    $translatedEntity = $entity;
    if ($this->languageManager->isMultilingual()) {
      $languageId = NULL;
      // Assume that the current language is desired
      // do not check if useCurrentLanguage is desired because if the language
      // is defined as a parameter, it most probably means that an override is wanted.
      if (!isset($language)) {
        $languageId = $this->languageManager->getCurrentLanguage()->getId();
      }
      else {
        // @todo check the language definition in the available languages list
        $languageId = $language_id;
      }

      if ($entity->hasTranslation($languageId)) {
        $translatedEntity = $entity->getTranslation($languageId);
      }
    }
    return $translatedEntity;
  }

  /**
   * Tries to get the current Node instance.
   *
   * @return \Drupal\node\NodeInterface|null
   */
  public function getCurrentNode() {
    $result = NULL;
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      $result = $node;
    }
    return $result;
  }

  /**
   * Tries to get the current Node id.
   *
   * @return int|null
   */
  public function getCurrentNodeId() {
    $result = NULL;
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      $result = $node->id();
    }
    return $result;
  }

  /**
   * Tries to get the current Term instance.
   *
   * @return \Drupal\taxonomy\Entity\Term|mixed|null
   */
  public function getCurrentTerm() {
    // @todo review multiplicity
    $result = NULL;
    $term = \Drupal::routeMatch()->getParameter('taxonomy_term');
    if ($term instanceof Term) {
      $result = $term;
    }
    return $result;
  }

  /**
   * Tries to get the current Node id.
   *
   * @return int|mixed|null
   */
  public function getCurrentTermId() {
    // @todo review multiplicity
    $result = NULL;
    $term = \Drupal::routeMatch()->getParameter('taxonomy_term');
    if ($term instanceof Term) {
      $result = $term->id();
    }
    return $result;
  }

  /**
   * Returns available view modes (also known as display modes)
   * for an entity type.
   *
   * @param $entity_type
   *   Entity type. Example: 'node'.
   *
   * @return array|int
   */
  public function getViewModes($entity_type) {
    $result = $this->entityQuery->get('entity_view_mode')
      ->condition('targetEntityType', $entity_type)
      ->execute();
    return $result;
  }

  /**
   * Returns available view modes (also known as display modes)
   * for an entity type.
   *
   * @param $entity_type
   *   Entity type. Example: 'node'.
   *
   * @return array|int
   */
  public function getContentTypes() {
    // $result = $this->entityQuery->get('node_type')->execute();
    // Adds label.
    $result = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    return $result;
  }

  /**
   * Gets a Term name from its id.
   *
   * @todo normalize the difference between node title and term name
   *
   * @param int $term_id
   *   Term id.
   *
   * @return string
   *   Language source name or translated Term name
   *   if a translation is available.
   */
  public function getTermName($term_id) {
    $result = NULL;
    $term = $this->entityLoad(self::ENTITY_TERM, $term_id);
    if ($term instanceof Term) {
      $result = $term->getName();
    }
    return $result;
  }

  /**
   * Returns a Term id from its name.
   *
   * @todo handle translation
   *
   * @param string $term_name
   *   Term name.
   * @param null|string $vocabulary
   *   Vocabulary name (id).
   *
   * @return int|null|string
   */
  public function getTermId($term_name, $vocabulary = NULL) {
    $properties = [];
    if (!empty($term_name)) {
      $properties['name'] = $term_name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vocabulary;
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }

  /**
   * Loads the tree of a vocabulary.
   *
   * @todo handle translation
   *
   * @see https://www.webomelette.com/loading-taxonomy-terms-tree-drupal-8
   *
   * @param string $vocabulary
   *   Machine name.
   *
   * @return array
   */
  public function loadVocabulary($vocabulary) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vocabulary);
    $tree = [];
    foreach ($terms as $tree_object) {
      $this->termTreeBuild($tree, $tree_object, $vocabulary);
    }
    return $tree;
  }

  /**
   * Populates a tree array given a taxonomy term tree object.
   *
   * @param $tree
   * @param $object
   * @param $vocabulary
   */
  private function termTreeBuild(&$tree, $object, $vocabulary) {
    if ($object->depth != 0) {
      return;
    }
    $tree[$object->tid] = $object;
    $tree[$object->tid]->children = [];
    $object_children = &$tree[$object->tid]->children;

    $children = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadChildren($object->tid);
    if (!$children) {
      return;
    }

    $child_tree_objects = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vocabulary, $object->tid);

    foreach ($children as $child) {
      foreach ($child_tree_objects as $child_tree_object) {
        if ($child_tree_object->tid == $child->id()) {
          $this->termTreeBuild($object_children, $child_tree_object, $vocabulary);
        }
      }
    }
  }

  /**
   * Checks view access to a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check access.
   *
   * @return bool
   *   The access check result.
   *
   * @TODO Remove "check_access" option in 9.x.
   */
  private function entityAccess(EntityInterface $entity) {
    return $entity->access('view');
  }

}
