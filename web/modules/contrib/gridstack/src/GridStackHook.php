<?php

namespace Drupal\gridstack;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\blazy\Blazy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides GridStack utility methods for Drupal hooks.
 */
class GridStackHook {

  use StringTranslationTrait;

  /**
   * The gridstack manager service.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * The library info definition.
   *
   * @var array
   */
  protected $libraryInfoBuild;

  /**
   * Constructs a GridStack object.
   *
   * @param \Drupal\gridstack\GridStackManagerInterface $manager
   *   The gridstack manager service.
   */
  public function __construct(GridStackManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('gridstack.manager'));
  }

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild() {
    if (!isset($this->libraryInfoBuild)) {
      $libraries = [];
      if ($skins = $this->manager->getSkins()) {
        foreach ($skins as $key => $skin) {
          $provider = isset($skin['provider']) ? $skin['provider'] : 'gridstack';
          $id = $provider . '.' . $key;

          foreach (['css', 'js', 'dependencies'] as $property) {
            if (isset($skin[$property]) && is_array($skin[$property])) {
              $libraries[$id][$property] = $skin[$property];
            }
          }
          $libraries[$id]['dependencies'][] = 'gridstack/skin';
        }
      }

      foreach (range(1, 12) as $key) {
        $libraries['gridstack.' . $key] = [
          'css' => [
            'layout' => ['css/layout/grid-stack-' . $key . '.css' => []],
          ],
        ];
      }

      $this->libraryInfoBuild = $libraries;
    }

    return $this->libraryInfoBuild;
  }

  /**
   * Implements hook_config_schema_info_alter().
   *
   * @todo: Also verify widget.module, and revisit if any further change.
   */
  public function configSchemaInfoAlter(array &$definitions) {
    // Panels passes its layout.settings to layout_plugin.settings.
    if (isset($definitions['layout_plugin.settings'])) {
      $this->mapConfigSchemaInfoAlter($definitions['layout_plugin.settings'], 'panelizer');
    }

    // @todo: Remove when DS passes layout.settings to layout_plugin.settings.
    if (isset($definitions['core.entity_view_display.*.*.*.third_party.ds'])) {
      $this->mapConfigSchemaInfoAlter($definitions['core.entity_view_display.*.*.*.third_party.ds']['mapping']['layout']['mapping']['settings']);
    }

    foreach (['gridstack_base', 'gridstack_vanilla'] as $key) {
      if (isset($definitions[$key])) {
        Blazy::configSchemaInfoAlter($definitions, $key, GridStackDefault::extendedSettings());
      }
    }
  }

  /**
   * Maps config schema.
   */
  public function mapConfigSchemaInfoAlter(array &$mappings, $source = '') {
    $common = ['attributes', 'extras', 'skin', 'wrapper', 'wrapper_classes'];
    foreach ($common as $key) {
      $mappings['mapping'][$key]['type'] = 'string';
      $mappings['mapping'][$key]['label'] = ucwords($key);
    }

    $mappings['mapping']['regions']['type'] = 'sequence';
    $mappings['mapping']['regions']['label'] = 'Regions';
    $mappings['mapping']['regions']['sequence'][0]['type'] = 'mapping';
    $mappings['mapping']['regions']['sequence'][0]['label'] = 'Region';

    foreach ($common as $key) {
      $mappings['mapping']['regions']['sequence'][0]['mapping'][$key]['type'] = 'string';
      $mappings['mapping']['regions']['sequence'][0]['mapping'][$key]['label'] = ucwords($key);
    }
  }

  /**
   * Implements hook_field_formatter_info_alter().
   */
  public function fieldFormatterInfoAlter(array &$info) {
    $common = [
      'quickedit' => ['editor' => 'disabled'],
      'provider'  => 'gridstack',
    ];

    // Supports Media Entity via VEM within VEF if available.
    // @todo drop for blazy 2.x with core Media.
    if ($this->manager->getModuleHandler()->moduleExists('video_embed_media')) {
      $info['gridstack_file'] = $common + [
        'id'          => 'gridstack_file',
        'label'       => $this->t('GridStack Image with VEF (deprecated)'),
        'description' => $this->t('Display the images associated to VEM/ME as a simple mix of GridStack image/video (deprecated for GridStack Media).'),
        'class'       => 'Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackFileFormatter',
        'field_types' => ['entity_reference', 'image'],
      ];
    }

    if ($this->manager->getModuleHandler()->moduleExists('paragraphs')) {
      $info['gridstack_paragraphs'] = $common + [
        'id'          => 'gridstack_paragraphs',
        'label'       => $this->t('GridStack Paragraphs'),
        'description' => $this->t('Display the Paragraphs as a GridStack.'),
        'class'       => 'Drupal\gridstack\Plugin\Field\FieldFormatter\GridStackParagraphsFormatter',
        'field_types' => ['entity_reference_revisions'],
      ];
    }
  }

  /**
   * Implements hook_layout_alter().
   */
  public function layoutAlter(&$definitions) {
    $optionsets = $this->manager->entityLoadMultiple('gridstack');
    $framework  = $this->manager->configLoad('framework', 'gridstack.settings');
    $path       = drupal_get_path('module', 'gridstack');

    foreach ($optionsets as $key => $optionset) {
      if ($key == 'default') {
        continue;
      }

      $static     = !empty($framework) && $optionset->getOption('use_framework');
      $id         = $optionset->id();
      $layout_id  = 'gridstack_' . $id;
      $regions    = $optionset->prepareRegions();
      $default    = isset($regions['gridstack_0']) ? 'gridstack_0' : 'gridstack_0_0';
      $additional = ['optionset' => $id];

      // Defines the layout.
      $definition = [
        'label'          => strip_tags($optionset->label()),
        'category'       => $static ? 'GridStack ' . ucwords($framework) : 'GridStack JS',
        'class'          => '\Drupal\gridstack\Layout\GridStackLayout',
        'default_region' => $default,
        'icon'           => $optionset->getIconUrl(),
        'id'             => $layout_id,
        'provider'       => 'gridstack',
        'additional'     => $additional,
        'regions'        => $regions,
        'theme_hook'     => 'gridstack',
        'path'           => $path,
        'library'        => 'gridstack/layout',
        'config_dependencies' => [
          'config' => ['gridstack.optionset.' . $id],
          'module' => ['gridstack'],
        ],
      ];

      $definitions[$layout_id] = new LayoutDefinition($definition);
    }
  }

}
