<?php

namespace Drupal\panels_extra_styles\Plugin\PanelsStyle;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels\Plugin\PanelsStyle\PanelsStyleBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list panels style plugin.
 *
 * @PanelsStyle(
 *   id = "raw_style",
 *   title = @Translation("Wrapper: Raw"),
 *   description=@Translation("Wrap regions and panes with raw HTML.")
 * )
 */
class RawStyle extends PanelsStyleBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * Route.
   *
   * @var CurrentRouteMatch
   */
  private $routeMatch;


  /**
   * Renderer.
   *
   * @var Renderer
   */
  private $renderer;

  /**
   * RedpillElementStyle constructor.
   *
   * @param array $configuration
   *   Config.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Definition of plugin.
   * @param CurrentRouteMatch $routeMatch
   *   Current route.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $moduleHandler, CurrentRouteMatch $routeMatch, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $moduleHandler);
    $this->routeMatch = $routeMatch;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRegion(PanelsDisplayVariant $display, array $build, $region, array $blocks) {
    $config = $this->getConfiguration();
    $build = parent::buildRegion($display, $build, $region, $blocks);
    if (!empty($config['region']['content']['prefix']) && !empty($config['region']['content']['suffix'])) {
      $build['#prefix'] = $config['region']['content']['prefix'];
      $build['#suffix'] = $config['region']['content']['suffix'];
    }
    if (empty($config['region']['content']['prefix']) && empty($config['region']['content']['suffix'])) {
      unset($build['#prefix'], $build['#suffix']);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function buildBlock(PanelsDisplayVariant $display, BlockPluginInterface $block) {
    $config = $block->getConfiguration();
    $config_prefix = $block->getConfiguration()['style']['configuration']['pane']['content']['prefix'];
    $config_suffix = $block->getConfiguration()['style']['configuration']['pane']['content']['suffix'];

    $build = parent::buildBlock($display, $block);
    $render_array = $block->build() ?: [];
    if (!empty($config_prefix) && !empty($config_suffix)) {
      $build['content'] = [
        '#markup' => $this->renderer->render($render_array),
        '#prefix' => $config['style']['configuration']['pane']['content']['prefix'],
        '#suffix' => $config['style']['configuration']['pane']['content']['suffix'],
      ];
    }
    if ($config_prefix === '' && $config_suffix === '') {
      $build['content'] = [
        '#type' => 'markup',
        '#markup' => $this->renderer->render($render_array),
      ];
    }
    if ($config['label_display'] === 'visible') {
      $build['content'] += [
        '#title' => [
          '#markup' => strip_tags($block->label()),
          '#prefix' => isset($config['style']['configuration']['pane']['title']['prefix']) ? $config['style']['configuration']['pane']['title']['prefix'] : '',
          '#suffix' => isset($config['style']['configuration']['pane']['title']['suffix']) ? $config['style']['configuration']['pane']['title']['suffix'] : '',
        ],
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'region' => [
        'content' => [
          'prefix' => '',
          'suffix' => '',
        ],
      ],
      'pane' => [
        'title' => [
          'prefix' => '',
          'suffix' => '',
        ],
        'content' => [
          'prefix' => '',
          'suffix' => '',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === 'panels.region_edit_style') {
      $form['region']['content'] = [
        '#type' => 'fieldset',
        '#title' => t('Region Content settings'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];
      $form['region']['content']['prefix'] = [
        '#type' => 'textarea',
        '#title' => t('Prefix'),
        '#description' => t('HTML code to go <em>before</em> the content.'),
        '#default_value' => $form_state->getValue('prefix') ?: $config['region']['content']['prefix'],
      ];
      $form['region']['content']['suffix'] = [
        '#type' => 'textarea',
        '#title' => t('Suffix'),
        '#description' => t('HTML code to go <em>after</em> the content.'),
        '#default_value' => $form_state->getValue('suffix') ?: $config['region']['content']['suffix'],
      ];
    }
    else {
      $form['pane']['title'] = [
        '#type' => 'details',
        '#title' => t('Pane Title settings'),
        '#open' => FALSE,
      ];
      $form['pane']['title']['prefix'] = [
        '#type' => 'textarea',
        '#title' => t('Prefix'),
        '#description' => t('HTML code to go <em>before</em> the content.'),
        '#default_value' => $form_state->getValue('prefix') ?: $config['pane']['title']['prefix'],
      ];
      $form['pane']['title']['suffix'] = [
        '#type' => 'textarea',
        '#title' => t('Suffix'),
        '#description' => t('HTML code to go <em>after</em> the content.'),
        '#default_value' => $form_state->getValue('suffix') ?: $config['pane']['title']['suffix'],
      ];

      $form['pane']['content'] = [
        '#type' => 'details',
        '#title' => t('Pane Content settings'),
        '#open' => FALSE,
      ];
      $form['pane']['content']['prefix'] = [
        '#type' => 'textarea',
        '#title' => t('Prefix'),
        '#description' => t('HTML code to go <em>before</em> the content.'),
        '#default_value' => $form_state->getValue('prefix') ?: $config['pane']['content']['prefix'],
      ];
      $form['pane']['content']['suffix'] = [
        '#type' => 'textarea',
        '#title' => t('Suffix'),
        '#description' => t('HTML code to go <em>after</em> the content.'),
        '#default_value' => $form_state->getValue('suffix') ?: $config['pane']['content']['suffix'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'), $container->get('current_route_match'), $container->get('renderer'));
  }

}
