<?php

namespace Drupal\entity_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Twig extension that wraps Entity Tools service methods.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * Drupal\entity_tools\EntityTools definition.
   *
   * @var \Drupal\entity_tools\EntityTools
   */
  protected $entityTools;

  /**
   * Constructor.
   */
  public function __construct() {
    // @todo dependency injection
    $this->entityTools = \Drupal::service('entity_tools');
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    // Currently a simple subset from the Twig Tweak module.
    // @todo provide implementation from entity_tools service.
    // @todo change get by display to comply service naming conventions
    return [
      new \Twig_SimpleFunction('get_entity', [$this, 'getEntity']),
      new \Twig_SimpleFunction('get_field', [$this, 'getField']),
      new \Twig_SimpleFunction('get_block', [$this, 'getBlock']),
      new \Twig_SimpleFunction('get_block_content', [$this, 'getBlockContent']),
      new \Twig_SimpleFunction('get_block_plugin', [$this, 'getBlockPlugin']),
      new \Twig_SimpleFunction('get_menu', [$this, 'getMenu']),
      new \Twig_SimpleFunction('get_form', [$this, 'getForm']),
      new \Twig_SimpleFunction('get_token', [$this, 'getToken']),
      new \Twig_SimpleFunction('get_config', [$this, 'getConfig']),
      new \Twig_SimpleFunction('get_url', [$this, 'getUrl']),
      new \Twig_SimpleFunction('get_view', 'views_embed_view'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'entity_tools';
  }

  /**
   * Returns the render array for an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param mixed $id
   *   The ID of the entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return null|array
   *   A render array for the entity or NULL if the entity does not exist.
   */
  public function getEntity($entity_type, $id = NULL, $view_mode = NULL, $langcode = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity = $id
      ? $entity_type_manager->getStorage($entity_type)->load($id)
      : \Drupal::routeMatch()->getParameter($entity_type);
    if ($entity && $this->entityAccess($entity)) {
      $render_controller = $entity_type_manager->getViewBuilder($entity_type);
      return $render_controller->view($entity, $view_mode, $langcode);
    }
  }

  /**
   * Returns the render array for a single entity field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $entity_type
   *   The entity type.
   * @param mixed $id
   *   The ID of the entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the field.
   * @param string $langcode
   *   (optional) Language code to load translation.
   *
   * @return null|array
   *   A render array for the field or NULL if the value does not exist.
   */
  public function getField($field_name, $entity_type, $id = NULL, $view_mode = 'default', $langcode = NULL) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $id
      ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($id)
      : \Drupal::routeMatch()->getParameter($entity_type);
    if ($entity && $this->entityAccess($entity)) {
      if ($langcode && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }
      if (isset($entity->{$field_name})) {
        return $entity->{$field_name}->view($view_mode);
      }
    }
  }

  /**
   * Builds the render array for the provided block.
   *
   * @param mixed $id
   *   The ID of the block to render.
   *
   * @return null|array
   *   A render array for the block or NULL if the block does not exist.
   */
  public function getBlock($id) {
    return $this->entityTools->blockDisplay($id);
  }

  /**
   * Builds the render array for the provided block content.
   *
   * @param mixed $id
   *   The ID of the block to render.
   *
   * @return null|array
   *   A render array for the block or NULL if the block does not exist.
   */
  public function getBlockContent($id) {
    return $this->entityTools->blockContentDisplay($id);
  }

  /**
   * Builds the render array for the provided block plugin.
   *
   * @param mixed $id
   *   The ID of the block to render.
   * @param array $config
   *   Optional configuration.
   *
   * @return null|array
   *   A render array for the block or NULL if the block does not exist.
   */
  public function getBlockPlugin($id, array $config = []) {
    return $this->entityTools->blockPluginDisplay($id, $config);
  }

  /**
   * Returns the render array for Drupal menu.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param int $level
   *   (optional) Initial menu level.
   * @param int $depth
   *   (optional) Maximum number of menu levels to display.
   *
   * @return array
   *   A render array for the menu.
   */
  public function getMenu($menu_name, $level = 1, $depth = 0) {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree */
    $menu_tree = \Drupal::service('menu.link_tree');
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);

    // Adjust the menu tree parameters based on the block's configuration.
    $parameters->setMinDepth($level);
    // When the depth is configured to zero, there is no depth limit. When depth
    // is non-zero, it indicates the number of levels that must be displayed.
    // Hence this is a relative depth that we must convert to an actual
    // (absolute) depth, that may never exceed the maximum depth.
    if ($depth > 0) {
      $parameters->setMaxDepth(min($level + $depth - 1, $menu_tree->maxDepth()));
    }

    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    return $menu_tree->build($tree);
  }

  /**
   * Builds and processes a form for a given form ID.
   *
   * @param string $form_id
   *   The form ID.
   * @param ...
   *   Additional arguments are passed to form constructor.
   *
   * @return array
   *   A render array to represent the form.
   */
  public function getForm($form_id) {
    $form_builder = \Drupal::formBuilder();
    $args = func_get_args();
    return call_user_func_array([$form_builder, 'getForm'], $args);
  }

  /**
   * Replaces a given tokens with appropriate value.
   *
   * @param string $token
   *   A replaceable token.
   * @param array $data
   *   (optional) An array of keyed objects. For simple replacement scenarios
   *   'node', 'user', and others are common keys, with an accompanying node or
   *   user object being the value. Some token types, like 'site', do not
   *   require any explicit information from $data and can be replaced even if
   *   it is empty.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process.
   *
   * @return string
   *   The token value.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function getToken($token, array $data = [], array $options = []) {
    return \Drupal::token()->replace("[$token]", $data, $options);
  }

  /**
   * Gets data from this configuration.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function getConfig($name, $key) {
    return \Drupal::config($name)->get($key);
  }

  /**
   * Generates a URL from internal path.
   *
   * @param string $user_input
   *   User input for a link or path.
   * @param array $options
   *   (optional) An array of options.
   *
   * @return \Drupal\Core\Url
   *   A new Url object based on user input.
   *
   * @see \Drupal\Core\Url::fromUserInput()
   */
  public function getUrl($user_input, array $options = []) {
    if (!in_array($user_input[0], ['/', '#', '?'])) {
      $user_input = '/' . $user_input;
    }
    return Url::fromUserInput($user_input, $options);
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
  protected function entityAccess(EntityInterface $entity) {
    // Prior version 8.x-1.7 entity access was not checked. The "check_access"
    // option provides a workaround for possible BC issues.
    return $entity->access('view');
  }

}
