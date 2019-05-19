<?php

namespace Drupal\visualn\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\DataGeneratorManager;
use Drupal\Core\Link;

/**
 * Class DrawersListController.
 */
class DrawersListController extends ControllerBase {

  /**
   * Drupal\visualn\Manager\DrawerManager definition.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Drupal\visualn\Manager\DataGeneratorManager definition.
   *
   * @var \Drupal\visualn\Manager\DataGeneratorManager
   */
  protected $visualNDataGeneratorManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.data_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DrawerManager $plugin_manager_visualn_drawer, DataGeneratorManager $plugin_manager_visualn_data_generator) {
    $this->visualNDrawerManager = $plugin_manager_visualn_drawer;
    $this->visualNDataGeneratorManager = $plugin_manager_visualn_data_generator;
  }

  /**
   * Page content for list of available base drawers.
   *
   * @return array
   *   Return Available drawers table markup.
   */
  public function page() {

    // @todo: also show subdrawers and make the drawer preview page to work with them

    $definitions = $this->visualNDrawerManager->getDefinitions();

    // @todo: sort by name, add columns sort

    $drawers_list = [];
    foreach ($definitions as $k => $definition) {
      if ($definition['role'] == 'wrapper') {
        unset($definitions[$k]);
        continue;
      }
    }

    // @todo: add method to the VisualN helper and reuse in resource provider
    $compatible_dgs = [];
    $dg_definitions = $this->visualNDataGeneratorManager->getDefinitions();
    foreach ($dg_definitions as $id => $dg_definition) {
      if (!empty($dg_definition['compatible_drawers'])) {
        // @todo: is it any drawer or just base drawers?
        foreach ($dg_definition['compatible_drawers'] as $drawer_id) {
          $compatible_dgs[$drawer_id][] = $id;
        }
      }
    }

    // Show list of available drawers and link each item to the drawer preview page
    $rows = [];
    foreach ($definitions as $definition) {
      $id = $definition['id'];
      $module = \Drupal::moduleHandler()->getName($definition['provider']);

      $label_link = Link::createFromRoute($definition['label'],
        'visualn.drawer_preview_controller_page', ['id' => $id])->toString();
      $preview_link = Link::createFromRoute(t('preview'),
        'visualn.drawer_preview_controller_page', ['id' => $id])->toString();

      // @todo: limit number of shown drawers in the preview list to avoid loading all the plugins
      // @todo: try check for errors not to break the list in case of errors when loading plugins
      $drawer_plugin = $this->visualNDrawerManager->createInstance($id);
      $description = $drawer_plugin->getDescription();
      $row = [$label_link, $id, $module, $preview_link, $description];
      if (isset($compatible_dgs[$id])) {
        // highlight green drawers having compatible data generators,
        $rows[] = ['data' => $row, 'class' => 'available-drawer-with-generator-row'];
      }
      else {
        $rows[] = $row;
      }
    }


    // @todo: add 'Create style' to operations
    $header = [t('Label'), t('Machine name'), t('Module'), t('Operations'), t('Description')];

    // @todo: add some description help text on top of the table

    if (!empty($rows)) {
      $build = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        // @todo: add some more text to make it more explicit
        '#prefix' => '<div class="available-drawers-table-header-text">' . t('Drawers that have compatible Data generators are highlighted <span>green</span>.') . '</div>',
      ];

      // @todo: register a different library if needed (e.g. visualn/visualn-drawer-preview-list)
      $build['#attached']['library'][] = 'visualn/visualn-drawer-preview';
    }
    else {
      $output = '<div>' . t('No drawers found') . '</div>';
      $build = [
        '#type' => 'markup',
        '#markup' => $output,
      ];
    }

    return $build;


  }

}
