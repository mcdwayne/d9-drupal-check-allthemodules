<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\Form\Adapter\AddOverviewForm.
 */

namespace Drupal\wisski_salz\Form\Adapter;

use Drupal\Core\Form\FormBase as FormBase1;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\wisski_salz\EngineManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a form to apply engines to a pipe.
 */
class AddOverviewForm extends FormBase1 {

  
  /**
   * The engine manager.
   *
   * @var \Drupal\wisski_salz\EngineManager
   */
  protected $manager;

  /**
   * Constructs a new AddForm.
   *
   * @param \Drupal\wisski_salz\EngineManager $manager
   *   The engine manager.
   */
  public function __construct(EngineManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.wisski_salz_engine')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "wisski_salz_engine_add_overview";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //$this->pipe = $wisski_salz;

    $form['#attached']['library'][] = 'wisski_salz/wisski_salz.admin';
    $header = [
      'label' => [
        'data' => $this->t('Available Engines'),
        'colspan' => 2
      ]
    ];

    $form['plugin'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $this->buildRows(),
      '#empty' => $this->t('No engines available.'),
    ];

    $options = [];
    foreach ($this->manager->getDefinitions() as $id => $plugin) {
      $options[$id] = isset($plugin['label']) ? $plugin['label']:$id;
    }

    return $form;
  }

  /**
   * Builds the table rows.
   *
   * @return array
   *   An array of table rows.
   */
  private function buildRows() {
    $rows = [];
    $all_plugins = $this->manager->getDefinitions();
    //uasort($all_plugins, function ($a, $b) {
    //  return strnatcasecmp($a['label'], $b['label']);
    //});
    foreach ($all_plugins as $definition) {
      /** @var \Drupal\wisski_salz\EngineInterface $plugin */
      $plugin = $this->manager->createInstance($definition['id']);
      $row = [
        'label' => Link::createFromRoute(
          $plugin->getName(),
          'entity.wisski_salz_adapter.add_form',
          ['engine_id' => $plugin->getPluginId()]
        ),
        'description' => $plugin->getDescription(),
      ];
      $rows[$plugin->getPluginId()] = $row;
    }
    return $rows;
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
