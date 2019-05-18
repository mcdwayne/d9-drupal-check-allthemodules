<?php

namespace Drupal\panels_extra_styles\Plugin\PanelsStyle;

use Drupal\Component\Utility\Html;
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
 *   id = "wrapper_element_style",
 *   title = @Translation("Wrapper: Element"),
 *   description = @Translation("Wrap regions and panes with HTML elements.")
 * )
 */
class WrapperElementStyle extends PanelsStyleBase implements ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * Request.
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $moduleHandler,  CurrentRouteMatch $routeMatch, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $moduleHandler);
    $this->routeMatch = $routeMatch;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'region' => [
        'content' => [
          'element' => '',
          'attributes' => [
            'id' => '',
            'class' => '',
          ],
        ],
      ],
      'pane' => [
        'title' => [
          'element' => '',
          'attributes' => [
            'id' => '',
            'class' => '',
          ],
        ],
        'content' => [
          'element' => '',
          'attributes' => [
            'id' => '',
            'class' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRegion(PanelsDisplayVariant $display, array $build, $region, array $blocks) {
    $config = $this->getConfiguration();
    $build = parent::buildRegion($display, $build, $region, $blocks);
    if (!empty($config['region']['content']['element'])) {
      $build['#prefix'] = sprintf('<%s>', $config['region']['content']['element']);
      $build['#suffix'] = "</{$config['region']['content']['element']}>";
    }
    elseif ($config['region']['content']['element'] == 0) {
      unset($build['#prefix'], $build['#suffix']);
    }
    elseif ($config['region']['content']['element'] === '') {
      return parent::buildRegion($display, $build, $region, $blocks);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function buildBlock(PanelsDisplayVariant $display, BlockPluginInterface $block) {
    $config_element = $block->getConfiguration()['style']['configuration']['pane']['content']['element'];
    $config_element_id = $block->getConfiguration()['style']['configuration']['pane']['content']['attributes']['id'];
    $config_element_class = $block->getConfiguration()['style']['configuration']['pane']['content']['attributes']['class'];

    $build = parent::buildBlock($display, $block);
    $render_array = $block->build() ?: [];
    if (!empty($config_element)) {
      $build['content'] = [
        '#markup' => $this->renderer->render($render_array),
        '#prefix' => sprintf('<%s id="%s" class="%s">', $config_element, $config_element_id, $config_element_class),
        '#suffix' => "</{$config_element}>",
      ];
    }
    elseif ($config_element == 0) {
      $build['content'] = [
        '#markup' => $this->renderer->render($render_array),
      ];
    }
    if ($block->getConfiguration()['label_display'] === 'visible') {
      $build['content'] += [
        '#title' => [
          '#markup' => strip_tags($block->label()),
          '#prefix' => sprintf('<%s id="%s" class="%s">', !empty($config['style']['configuration']['pane']['title']['element']) ?: 'h2',
            $config['style']['configuration']['pane']['title']['attributes']['id'],
            $config['style']['configuration']['pane']['title']['attributes']['class']
          ),
          '#suffix' => !empty($config['style']['configuration']['pane']['title']['element']) ?: '</h2>' ,
        ],
      ];
    }
    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === NULL) {
      $form_state->setRebuild();
    }
    if ($route_name === 'panels.region_edit_style') {
      $form['region']['content']['element'] = [
        '#type' => 'select',
        '#title' => t('Choose wrapper element'),
        '#description' => t('HTML element to wrap around the content.'),
        '#options' => [
          '' => t('- Use default -'),
          '0' => t('- None -'),
          'div' => 'DIV',
          'span' => 'SPAN',
          'h1' => 'H1',
          'h2' => 'H2',
          'h3' => 'H3',
          'h4' => 'H4',
          'h5' => 'H5',
          'h6' => 'H6',
          'p' => 'P',
          'strong' => 'STRONG',
          'em' => 'EM',
        ],
        '#default_value' => $form_state->getValue('element') ?: $config['region']['content']['element'],
      ];

      $form['region']['content']['attributes'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => t('Content Attributes'),
        '#description' => t('This is the class for the element.'),
      ];

      $form['region']['content']['attributes']['id'] = [
        '#type' => 'textfield',
        '#title' => t('id'),
        '#description' => t('CSS id to apply to the element, without the hash.'),
        '#default_value' => $form_state->getValue('id') ?: $config['region']['content']['attributes']['id'],
      ];

      $form['region']['content']['attributes']['class'] = [
        '#type' => 'textfield',
        '#title' => t('class'),
        '#description' => t('CSS classes to apply to the element, separated by spaces.'),
        '#default_value' => $form_state->getValue('class') ?: $config['region']['content']['attributes']['class'],
      ];
    }
    else {
      $form['pane']['content']['element'] = [
        '#type' => 'select',
        '#title' => t('Choose content wrapper element'),
        '#description' => t('HTML element to wrap around the content.'),
        '#options' => [
          '' => t('- Use default -'),
          '0' => t('- None -'),
          'div' => 'DIV',
          'span' => 'SPAN',
          'h1' => 'H1',
          'h2' => 'H2',
          'h3' => 'H3',
          'h4' => 'H4',
          'h5' => 'H5',
          'h6' => 'H6',
          'p' => 'P',
          'strong' => 'STRONG',
          'em' => 'EM',
        ],
        '#default_value' => $form_state->getValue('element') ?: $config['pane']['content']['element'],
      ];

      $form['pane']['content']['attributes'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => t('Content Attributes'),
        '#description' => t('This is the class for the element.'),
      ];

      $form['pane']['content']['attributes']['id'] = [
        '#type' => 'textfield',
        '#title' => t('id'),
        '#description' => t('CSS id to apply to the element, without the hash.'),
        '#default_value' => $form_state->getValue('id') ?: $config['pane']['content']['attributes']['id'],
      ];

      $form['pane']['content']['attributes']['class'] = [
        '#type' => 'textfield',
        '#title' => t('class'),
        '#description' => t('CSS classes to apply to the element, separated by spaces.'),
        '#default_value' => $form_state->getValue('class') ?: $config['pane']['content']['attributes']['class'],
      ];

      $form['pane']['title']['element'] = [
        '#type' => 'select',
        '#title' => t('Choose title wrapper element'),
        '#description' => t('HTML element to wrap around the title.'),
        '#options' => [
          '' => t('- Use default -'),
          '0' => t('- None -'),
          'div' => 'DIV',
          'span' => 'SPAN',
          'h1' => 'H1',
          'h2' => 'H2',
          'h3' => 'H3',
          'h4' => 'H4',
          'h5' => 'H5',
          'h6' => 'H6',
          'p' => 'P',
          'strong' => 'STRONG',
          'em' => 'EM',
        ],
        '#default_value' => $form_state->getValue('element') ?: $config['pane']['title']['element'],
      ];

      $form['pane']['title']['attributes'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => t('Title Attributes'),
        '#description' => t('This is the class for the element.'),
      ];

      $form['pane']['title']['attributes']['id'] = [
        '#type' => 'textfield',
        '#title' => t('id'),
        '#description' => t('CSS id to apply to the element, without the hash.'),
        '#default_value' => $form_state->getValue('id') ?: $config['pane']['title']['attributes']['id'],
      ];

      $form['pane']['title']['attributes']['class'] = [
        '#type' => 'textfield',
        '#title' => t('class'),
        '#description' => t('CSS classes to apply to the element, separated by spaces.'),
        '#default_value' => $form_state->getValue('class') ?: $config['pane']['title']['attributes']['class'],
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
   *
   * @throws \LogicException
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
    $form_state->setCached(FALSE);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('module_handler'), $container->get('current_route_match'), $container->get('renderer'));
  }

}
