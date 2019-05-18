<?php

/**
 * @file
 * Contains \Drupal\fancyzoom\Controller\PathController.
 */

namespace Drupal\fancyzoom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for fancyzoom routes.
 */
class PathController extends ControllerBase {

  /**
   * The fancyzoom alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The fancyzoom alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new PathController.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The fancyzoom alias storage.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The fancyzoom alias manager.
   */
  public function __construct(AliasStorageInterface $alias_storage, AliasManagerInterface $alias_manager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    );
  }

  public function adminOverview($keys) {
    // Add the filter form above the overview table.
    $build['fancyzoom_admin_form'] = $this->formBuilder()->getForm('Drupal\fancyzoom\Form\FancyZoomConfigForm', $keys);
    return $build;
  }

}
