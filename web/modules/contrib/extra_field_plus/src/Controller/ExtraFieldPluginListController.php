<?php

namespace Drupal\extra_field_plus\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\extra_field_plus\Plugin\ExtraFieldPlusFormManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ExtraFieldPluginListController.
 */
class ExtraFieldPluginListController extends ControllerBase {

  /**
   * The extra fields plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The extra field plugin form manager.
   *
   * @var \Drupal\extra_field_plus\Plugin\ExtraFieldPlusFormManager
   */
  protected $pluginFormManager;

  /**
   * Creates an ExtraFieldPluginListController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manger
   *   The extra fields plugin manager.
   * @param \Drupal\extra_field_plus\Plugin\ExtraFieldPlusFormManager $plugin_form_manger
   *   The extra field plugin form manager.
   */
  public function __construct(PluginManagerInterface $plugin_manger, ExtraFieldPlusFormManager $plugin_form_manger) {
    $this->pluginManager = $plugin_manger;
    $this->pluginFormManager = $plugin_form_manger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.extra_field_display'),
      $container->get('plugin.manager.extra_field_plus.form')
    );
  }

  /**
   * Provides extra field plugins list.
   */
  public function pluginsList() {
    $row = [];

    $definitions = $this->pluginManager->getDefinitions();
    if (ksort($definitions)) {
      foreach ($definitions as $plugin_id => $definition) {
        $class = explode('\\', $definition['class']);
        $settings = $this->pluginFormManager->hasSettingsForm($plugin_id);
        $row[] = [
          [
            'data' => $plugin_id,
          ],
          [
            'data' => $definition['label'],
          ],
          [
            'data' => [
              '#markup' => implode('<br/>', $definition['bundles']),
            ],
          ],
          [
            'data' => end($class),
          ],
          [
            'data' => $definition['provider'],
          ],
          [
            'data' => $settings ? $this->t('Yes') : $this->t('No'),
          ],
        ];
      }
    }

    $elements = [
      '#type' => 'table',
      '#sticky' => TRUE,
      '#empty' => $this->t('Fields list is empty.'),
      '#header' => [
        [
          'data' => $this->t('Id'),
        ],
        [
          'data' => $this->t('Label'),
        ],
        [
          'data' => $this->t('Bundles'),
        ],
        [
          'data' => $this->t('Class'),
        ],
        [
          'data' => $this->t('Provider'),
        ],
        [
          'data' => $this->t('Settings'),
        ],
      ],
      '#rows' => $row,
    ];

    return $elements;
  }

}
