<?php

namespace Drupal\layout_builder_enhancements\Plugin\Derivative;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\views\Views;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides block plugin definitions for mymodule blocks.
 *
 * @see \Drupal\mymodule\Plugin\Block\MyModuleBlock
 */
class ViewBlock extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $views = Views::getViewsAsOptions(FALSE, 'enabled');
    $entity_ids = $this->manager->getQuery()
      ->condition('status', TRUE)
      ->condition("display.*.display_plugin", ['embed'], 'IN')
      ->execute();

    foreach ($views as $block_id => $block_label) {
      $view = explode(':', $block_id)[0];
      $display = explode(':', $block_id)[1];
      if (!in_array($view, $entity_ids)) {
        continue;
      }

      $view = $this->manager->load($view);
      $display = $view->getDisplay($display);
      if ($display['display_plugin'] != 'embed') {
        continue;
      }

      $this->derivatives[$block_id] = $base_plugin_definition;
      $this->derivatives[$block_id]['admin_label'] = $this->t('@view_name: @display_name', [
        '@view_name' => $view->label(),
        '@display_name' => $display['display_title'],
      ]);
      $this->derivatives[$block_id]['config_dependencies']['config'] = [
        $view->getConfigDependencyName(),
      ];
    }
    return $this->derivatives;
  }

  /**
   * Constructs new FieldBlockDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $manager) {
    $this->manager = $manager->getStorage('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

}
