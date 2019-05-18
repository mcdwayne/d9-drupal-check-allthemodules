<?php

namespace Drupal\chatbot_api\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Chatbot\Intent plugin definitions for Views chatbot_intent displays.
 *
 * @see \Drupal\chatbot_api\Plugin\Chatbot\Intent\ViewsIntent
 */
class ViewsIntent implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entityTypeManager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // If views is not enabled, there are no derivatives.
    if (!$this->entityTypeManager->hasDefinition('view')) {
      return $this->derivatives;
    }
    // Check all Views for block displays.
    $viewStorage = $this->entityTypeManager->getStorage('view');
    /** @var \Drupal\views\ViewEntityInterface $view */
    foreach ($viewStorage->loadMultiple() as $view) {
      // Do not return results for disabled views.
      if (!$view->status()) {
        continue;
      }
      $executable = $view->getExecutable();
      $executable->initDisplay();
      foreach ($executable->displayHandlers as $display) {
        /** @var \Drupal\views\Plugin\views\display\DisplayPluginInterface $display */
        // Add a block plugin definition for each block display.
        if (isset($display) && $display->getPluginId() == 'chatbot_intent') {
          if ($intent_name = $display->getOption('intent_name')) {
            $this->derivatives[$intent_name] = [
              'label' => $view->label(),
              'view_name' => $view->id(),
              'display_name' => $display->display['id'],
            ];

            $this->derivatives[$intent_name] += $base_plugin_definition;
          }
        }
      }
    }
    return $this->derivatives;
  }

}
