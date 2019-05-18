<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsExposedFilterBlock.
 */

namespace Drupal\mefibs\Plugin\Derivative;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for all Views exposed filters.
 *
 * @see \Drupal\mefibs\Plugin\Block\MefibsExposedFilterBlock
 */
class MefibsExposedFilterBlock implements ContainerDerivativeInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * The view storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $viewStorageController;

  /**
   * The base plugin ID that the derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a MefibsExposedFilterBlock object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $view_storage_controller
   *   The entity storage controller to load views.
   */
  public function __construct($base_plugin_id, EntityStorageControllerInterface $view_storage_controller) {
    $this->basePluginId = $base_plugin_id;
    $this->viewStorageController = $view_storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager')->getStorageController('view')
    );
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements \Drupal\Component\Plugin\Derivative\DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Check all Views for displays with an exposed filter block.
    foreach ($this->viewStorageController->loadMultiple() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        if (isset($display) && $display->getOption('exposed_block')) {
          // Add a block definition for the block.
          if ($display->usesExposedFormInBlock()) {
            $mefibs = $display->getOption('mefibs');
            if (isset($mefibs['blocks']) && count($mefibs['blocks'])) {
              foreach ($mefibs['blocks'] as $block) {
                $delta = $view->id() . '-' . $display->display['id'] . '-' . $block['machine_name'];
                $desc = t('Exposed form: @view-@display_id: @block', array(
                  '@view' => $view->id(),
                  '@display_id' => $display->display['id'],
                  '@block' => $block['name'],
                ));
                $this->derivatives[$delta] = array(
                  'admin_label' => $desc,
                  'cache' => DRUPAL_NO_CACHE,
                );
                $this->derivatives[$delta] += $base_plugin_definition;
              }
            }
          }
        }
      }
    }
    return $this->derivatives;
  }

}
