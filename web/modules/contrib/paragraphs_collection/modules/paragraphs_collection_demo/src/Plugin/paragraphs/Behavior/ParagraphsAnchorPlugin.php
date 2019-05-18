<?php

namespace Drupal\paragraphs_collection_demo\Plugin\paragraphs\Behavior;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Paragraphs Anchor plugin.
 *
 * @ParagraphsBehavior(
 *   id = "anchor",
 *   label = @Translation("Anchor"),
 *   description = @Translation("Allows to set ID attribute that can be used as jump position in URLs."),
 *   weight = 3
 * )
 */
class ParagraphsAnchorPlugin extends ParagraphsBehaviorBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ParagraphsSliderPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('config.factory')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if ($anchor = $paragraph->getBehaviorSetting($this->getPluginId(), 'anchor')) {
      $build['#attributes']['id'] = 'scrollto-' . $anchor;
      $build['#attributes']['class'][] = 'paragraphs-anchor-link';
      // @todo Make UI for global configuration.
      // @see https://www.drupal.org/node/2856912.
      if ($this->configFactory->get('paragraphs_collection_demo.settings')->get('anchor')['show_permalink']) {
        $build['#attached']['library'][] = 'paragraphs_collection_demo/anchor';
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form['anchor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Anchor'),
      '#description' => $this->t('Sets an ID attribute prefixed with "scrollto-" in the Paragraph so that it can be used as a jump-to link.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'anchor'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = [];
    if ($anchor = $paragraph->getBehaviorSetting($this->getPluginId(), 'anchor')) {
      $summary = [
        [
          'label' => $this->t('Anchor'),
          'value' => 'scrollto-' . $anchor
        ]
      ];
    }
    return $summary;
  }

}
