<?php

namespace Drupal\gnode_field\Plugin\views\argument_default;

use Drupal\gnode_field\Service\GroupNodeFieldService;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract a group ID.
 *
 * This can be used in context filters to filter the current authenticated user.
 *
 * @ViewsArgumentDefault(
 *   id = "group_id_from_current_user",
 *   title = @Translation("Group ID from current authenticated user")
 * )
 */
class GroupIdFromCurrentUser extends ArgumentDefaultPluginBase implements CacheableDependencyInterface, ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * Group node field service.
   *
   * @var \Drupal\gnode_field\Service\GroupNodeFieldService
   */
  protected $groupService;

  /**
   * Constructs an argument plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\gnode_field\Service\GroupNodeFieldService $group_service
   *   Group node field service.
   */
  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, GroupNodeFieldService $group_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupService = $group_service;
    $this->setContainer($container);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\gnode_field\Service\GroupNodeFieldService $group_service */
    $group_service = $container->get('gnode_field.node_group_ref');

    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $group_service
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    return implode('+', array_keys($this->groupService->getUserGroups()));
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
    return ['user'];
  }

}
