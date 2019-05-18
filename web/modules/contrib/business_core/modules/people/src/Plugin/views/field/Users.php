<?php

namespace Drupal\people\Plugin\views\field;

use Drupal\user\UserStorageInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of users.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("people_users")
 */
class Users extends PrerenderList {

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorage
   */
  protected $userStorage;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserStorageInterface $user_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['pid'] = ['table' => 'people', 'field' => 'pid'];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addAdditionalFields();
    $this->field_alias = $this->aliases['pid'];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    $this->items = [];

    foreach ($values as $result) {
      $eid = $this->getValue($result);

      $users = $this->userStorage->loadByProperties(['people' => $eid]);
      foreach ($users as $user) {
        $this->items[$eid][$user->id()]['user'] = $user->label();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    return $item['user'];
  }

}
