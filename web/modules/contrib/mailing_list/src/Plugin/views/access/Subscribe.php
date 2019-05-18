<?php

namespace Drupal\mailing_list\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Views access plugin that provides access for mailing list subscribers.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "mailing_list_subscribe",
 *   base = {
 *     "mailing_list_subscription_field_data",
 *   },
 *   title = @Translation("Mailing list subscribe"),
 *   help = @Translation("Access will be granted to users who can subscribe to at least one mailing list."),
 * )
 */
class Subscribe extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a Subscribe object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Walk over user roles.
    foreach ($this->entityManager->getStorage('user_role')->loadMultiple($account->getRoles()) as $role) {
      /** @var \Drupal\user\RoleInterface $role */
      if ($role->isAdmin()) {
        return TRUE;
      }

      // Look for any subscribe permission on current user role.
      foreach ($role->getPermissions() as $permission) {
        $matches = [];
        // Mailing list bundle must exists.
        if (preg_match('/^subscribe to (.+) mailing list$/', $permission, $matches)
          && $this->entityManager->getStorage('mailing_list')->load($matches[1])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    // Add all subscribe permissions in OR logic.
    $perms = '';
    foreach ($this->entityManager->getStorage('mailing_list')->loadMultiple() as $mailing_list) {
      $perm = 'subscribe to ' . $mailing_list->id() . ' mailing list';
      $perms .= empty($perms) ? $perm : '+' . $perm;
    }

    if (!empty($perms)) {
      $route->setRequirement('_permission', $perms);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
