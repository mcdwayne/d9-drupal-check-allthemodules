<?php

namespace Drupal\gridstack;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Render\Element;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerBase;
use Drupal\gridstack\Entity\GridStack;

/**
 * Implements GridStackManagerInterface.
 */
class GridStackManager extends BlazyManagerBase implements GridStackManagerInterface {

  /**
   * Static cache for the skin definition.
   *
   * @var array
   */
  protected $skinDefinition;

  /**
   * Static cache for the skin options.
   *
   * @var array
   */
  protected $skinOptions;

  /**
   * Static cache for the optionset options.
   *
   * @var array
   */
  protected $optionsetOptions;

  /**
   * The GridStack optionset.
   *
   * @var \Drupal\gridstack\Entity\GridStack
   */
  protected $gridStackOptionset;

  /**
   * If should ungridstack, no js/css-driven layouts, just re-use templates.
   *
   * @var bool
   */
  protected $unGridStack = FALSE;

  /**
   * If should optimize the CSS grid classes to remove duplicate rules.
   *
   * @var bool
   */
  protected $isOptimized = FALSE;

  /**
   * Returns defined skins as registered via hook_gridstack_skins_info().
   */
  public function getSkins() {
    if (!isset($this->skinDefinition)) {
      $this->skinDefinition = $this->buildSkins('gridstack', '\Drupal\gridstack\GridStackSkin');
    }

    return $this->skinDefinition;
  }

  /**
   * Returns available skins for select options.
   */
  public function getSkinOptions() {
    if (!isset($this->skinOptions)) {
      $skins = [];
      foreach ($this->getSkins() as $skin => $properties) {
        $skins[$skin] = isset($properties['name']) ? strip_tags($properties['name']) : $skin;
      }
      $this->skinOptions = $skins;
    }

    return $this->skinOptions;
  }

  /**
   * Returns available options for select options.
   */
  public function getOptionsetsByGroupOptions($group = '') {
    if (!isset($this->optionsetOptions[$group])) {
      $optionsets = [];
      foreach ($this->entityLoadMultiple('gridstack') as $key => $entity) {
        // Exludes Boostrap/ Foundation grids which only work for DS, Panels.
        if ($group && $group == 'js' && $entity->getOption('use_framework')) {
          continue;
        }
        $optionsets[$key] = strip_tags($entity->label());
      }
      $this->optionsetOptions[$group] = $optionsets;
    }

    return $this->optionsetOptions[$group];
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $load = parent::attach($attach);

    // Only load libraries if not destroyed.
    if (!$this->unGridStack) {
      // Only load GridStack JS if not using Bootstrap, nor Foundation.
      if (!empty($attach['use_js'])) {
        $customized = $this->configLoad('customized', 'gridstack.settings');
        $load['library'][] = empty($customized) ? 'gridstack/load' : 'gridstack/customized';

        if (!empty($attach['width']) && $attach['width'] < 12) {
          $load['library'][] = 'gridstack/gridstack.' . $attach['width'];
        }

        // Breakpoints: xs sm md lg xl requires separate CSS files.
        if (!empty($attach['breakpoints'])) {
          foreach ($attach['breakpoints'] as $breakpoint) {
            if (!empty($breakpoint['column']) && $breakpoint['column'] < 12) {
              $load['library'][] = 'gridstack/gridstack.' . $breakpoint['column'];
            }
          }
        }

        $load['drupalSettings']['gridstack'] = GridStack::defaultSettings();
      }
    }

    // The CSS framework grid library for admin pages such as layout builder.
    if (!empty($attach['library'])) {
      $load['library'][] = $attach['library'];
    }

    if (!empty($attach['debug'])) {
      $load['library'][] = 'gridstack/debug';
    }

    // Skins may be available for JS, or CSS layouts, or even ungridstack.
    if (isset($attach['skin']) && $skin = $attach['skin']) {
      $skins = $this->getSkins();
      $provider = isset($skins[$skin]['provider']) ? $skins[$skin]['provider'] : 'gridstack';
      $load['library'][] = 'gridstack/' . $provider . '.' . $skin;
    }

    $this->moduleHandler->alter('gridstack_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function boxAttributes(array &$settings, $current = 'grids') {
    // Allows extenders to provide own grid attributes.
    if ($this->unGridStack) {
      return [];
    }

    return $settings['use_js'] ? $this->gridStackOptionset->jsBoxAttributes($settings, $current) : $this->gridStackOptionset->cssBoxAttributes($settings, $current, $this->isOptimized);
  }

  /**
   * {@inheritdoc}
   */
  public function adminAttributes(array &$box, array &$attributes, array &$content_attributes, array $settings, array $regions = [], $rid = NULL) {
    $region = $regions[$rid];

    // Layout Builder integration.
    if (!empty($settings['_ipe_layouts']) && !empty($region['#attributes']) && is_array($region['#attributes'])) {
      $content_attributes = NestedArray::mergeDeep($content_attributes, $region['#attributes']);

      // Provides add block and contextual links.
      if (isset($region['layout_builder_add_block'])) {
        $link = $region['layout_builder_add_block'];
        if (!empty($box)) {
          if (isset($link['link'], $link['link']['#url'])) {
            $params = $link['link']['#url']->getRouteParameters();
            foreach (Element::children($box) as $uuid) {
              $box[$uuid]['#attributes']['class'][] = 'draggable';
              $box[$uuid]['#attributes']['data-layout-block-uuid'] = $uuid;
              $box[$uuid]['#contextual_links'] = [
                'layout_builder_block' => [
                  'route_parameters' => ['uuid' => $uuid] + $params,
                ],
              ];
            }
          }
        }
        $box[] = $link;
      }
    }

    // Panels IPE integration.
    if (!empty($settings['_ipe_panels']) && !empty($region['#prefix'])) {
      $box['#prefix'] = $region['#prefix'];

      foreach (Element::children($box) as $bid) {
        if (isset($region[$bid]['#attributes']['data-block-id'])) {
          $box[$bid]['#attributes']['data-block-id'] = $region[$bid]['#attributes']['data-block-id'];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildItems(array $build, array $regions = []) {
    $settings = $build['settings'];
    $grids    = $this->gridStackOptionset->getEndBreakpointGrids();
    $items    = [];

    foreach ($build['items'] as $delta => $item) {
      $box        = isset($item['box']) ? $item['box'] : [];
      $attributes = isset($item['attributes']) ? $item['attributes'] : [];
      $settings   = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
      $rid        = 'gridstack_' . $delta;
      $clone      = [
        'box'     => $box,
        'caption' => isset($item['caption']) ? $item['caption'] : [],
      ];

      $settings['delta'] = $settings['root_delta'] = $delta;
      $attributes = NestedArray::mergeDeep($attributes, $this->boxAttributes($settings, 'grids'));
      $content_attributes = [];

      if ($grids && !$this->unGridStack) {

        // Skips if more than we can chew, otherwise broken grid anyway.
        // The grids is a fixed layout blueprint. The items is dynamic contents.
        if (!isset($grids[$delta])) {
          continue;
        }

        // Layout Builder or Panels IPE only output for granted users.
        if (!empty($settings['_access_ipe']) && $regions && isset($regions[$rid])) {
          $this->adminAttributes($clone['box'], $attributes, $content_attributes, $settings, $regions, $rid);
        }

        // Node contains the main grids/boxes.
        $nested_grids = $this->gridStackOptionset->getNestedGridsByDelta($delta);
        $is_nested = array_filter($nested_grids);

        // Overrides wrapper via UI, if so configured.
        if (isset($settings['regions']) && !empty($settings['regions'][$rid]['wrapper'])) {
          $settings['wrapper'] = $settings['regions'][$rid]['wrapper'];
        }

        // Nested grids with preserved indices even if empty so to layout.
        // Only Bootstrap/ Foundation has nested grids, not js-driven layouts.
        if ($is_nested && !empty($box) && isset($box[0]['box'])) {
          $attributes['class'][] = 'box--nester';
          $settings['nested'] = TRUE;
          $settings['root'] = FALSE;
          $settings['use_inner'] = FALSE;
          $clone['box'] = $this->buildNestedItems($delta, $item, $nested_grids, $settings, $regions);
        }
        else {
          $settings['nested'] = FALSE;
          $settings['use_inner'] = TRUE;
        }
      }

      $items[] = $this->buildItem($clone, $delta, $attributes, $content_attributes, $settings);
      unset($clone);
    }

    return $items;
  }

  /**
   * Provides nested items if so configured.
   */
  public function buildNestedItems($delta, $item, $grids, $settings, $regions = []) {
    $items = [];
    $nested = $item['box'];

    // The nested elements.
    foreach (array_keys($grids) as $gid) {
      $rid = 'gridstack_' . $delta . '_' . $gid;
      $settings['nested_delta'] = $gid;
      $nested_box = $nested[$gid]['box'];
      $nested_settings = isset($nested[$gid]['settings']) ? array_merge($settings, $nested[$gid]['settings']) : $settings;
      $nested_attributes = isset($nested[$gid]['attributes']) ? $nested[$gid]['attributes'] : [];
      $content_attributes = [];

      $nested_settings['nested_id'] = ($delta + 1) . '-' . ($gid + 1);
      $nested_settings['use_inner'] = TRUE;
      if (isset($settings['regions'][$rid]) && !empty($settings['regions'][$rid]['wrapper'])) {
        $nested_settings['wrapper'] = $settings['regions'][$rid]['wrapper'];
      }

      // Layout Builder or Panels IPE integration only output for granted users.
      if ($settings['_access_ipe'] && $regions && isset($regions[$rid])) {
        $this->adminAttributes($nested_box, $nested_attributes, $content_attributes, $settings, $regions, $rid);
      }

      $nested_attributes = NestedArray::mergeDeep($nested_attributes, $this->boxAttributes($settings, 'nested'));
      $nested_attributes['class'][] = 'box--nested';

      $items[] = $this->buildItem(['box' => $nested_box], $gid, $nested_attributes, $content_attributes, $nested_settings);
    }

    // Provides nested gridstack, gridstack within gridstack, if so configured.
    $box = [];
    $attributes = isset($item['wrapper_attributes']) ? $item['wrapper_attributes'] : [];
    $attributes['class'][] = 'gridstack--nested';

    $box['content'] = [
      '#theme'      => 'gridstack',
      '#items'      => $items,
      '#optionset'  => $this->gridStackOptionset,
      '#settings'   => $settings,
      '#attributes' => $attributes,
    ];

    // Update box with nested boxes.
    return $box;
  }

  /**
   * {@inheritdoc}
   */
  public function buildItem($item, $delta, $attributes, $content_attributes, $settings) {
    return [
      '#theme'              => 'gridstack_box',
      '#item'               => $item,
      '#delta'              => $delta,
      '#attributes'         => $attributes,
      '#content_attributes' => $content_attributes,
      '#settings'           => $settings,
    ];
  }

  /**
   * Provides multi-breakpoint image styles.
   *
   * Overrides fallback breakpoint image_style with grid image_style.
   * This tells theme_blazy() to respect different image style per item.
   */
  public function buildImageStyleMultiple(array &$settings, $delta = 0) {
    foreach ($settings['breakpoints'] as $key => &$breakpoint) {
      if (isset($breakpoint['image_style']) && !empty($breakpoint['grids'][$delta]) && !empty($breakpoint['grids'][$delta]['image_style'])) {
        $breakpoint['image_style'] = $breakpoint['grids'][$delta]['image_style'];
      }

      // Overrides image style to use a defined image style per grid item.
      // This allows each individual box to have different image styles.
      if ($key == 'xl' && !empty($breakpoint['grids'][$delta]['image_style'])) {
        $settings['_dimensions'] = FALSE;
        $settings['image_style'] = $breakpoint['grids'][$delta]['image_style'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    foreach (['attached', 'grids', 'layout'] + GridStackDefault::themeProperties() as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    $settings = &$build['settings'];
    $id = empty($settings['id']) ? 'gridstack-' . $settings['optionset'] : $settings['id'];
    $settings['id'] = Blazy::getHtmlId('gridstack', $id);

    $gridstack = [
      '#theme'      => 'gridstack',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderGridStack']],
      'items'       => [],
    ];

    // Prepare wasted empty children to support Panels, and Layout Builder.
    if ($layout = $build['layout']) {
      foreach (array_keys($layout->getRegions()) as $rid) {
        $gridstack[$rid] = [];
      }
    }

    $this->moduleHandler->alter('gridstack_build', $gridstack, $settings);
    return empty($build['items']) ? [] : $gridstack;
  }

  /**
   * Return the wrapper attributes.
   */
  public function prepareAttributes(array &$build) {
    $settings                 = &$build['settings'];
    $attributes               = $this->gridStackOptionset->prepareAttributes($settings, $this->unGridStack);
    $attributes['id']         = $settings['id'];
    $attributes['class'][]    = 'blazy';
    $attributes['data-blazy'] = empty($settings['blazy_data']) ? '' : Json::encode($settings['blazy_data']);

    if (!empty($settings['debug'])) {
      $attributes['class'][] = 'gridstack--debug';
    }

    if (!empty($settings['media_switch'])) {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $attributes['data-' . $switch . '-gallery'] = TRUE;
    }

    $settings['attributes'] = $settings['wrapper_classes'] = '';
    return $attributes;
  }

  /**
   * Build the HTML settings.
   */
  public function prepareSettings(array &$settings) {
    $defaults = array_merge(GridStackDefault::htmlSettings(), $this->gridStackOptionset->getOptions('settings'));
    $settings += $defaults;

    // Use static grid framework if so configured.
    $settings['framework']     = $this->configLoad('framework', 'gridstack.settings');
    $settings['use_framework'] = !empty($settings['framework']) && $this->gridStackOptionset->getOption('use_framework');

    // Disable background and JS if using a CSS framework.
    if ($settings['use_framework']) {
      $settings['background'] = $settings['use_js'] = FALSE;
      // Admin UI always uses gridstack JS to build layouts regardless of this.
      $settings['use_framework'] = empty($settings['_admin']);
    }
    else {
      $settings['use_js'] = !empty($settings['root']) || !empty($settings['_admin']);
    }

    if (empty($settings['breakpoints'])) {
      $this->gridStackOptionset->gridsJsonToArray($settings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderGridStack($element) {
    $build = $element['#build'];
    unset($element['#build']);

    // Build gridstack elements.
    $settings = &$build['settings'];
    $this->gridStackOptionset = $build['optionset'] ?: GridStack::loadWithFallback($settings['optionset']);
    $this->isOptimized = $this->configLoad('optimized', 'gridstack.settings');

    // Supports Blazy multi-breakpoint images if provided.
    if (!empty($settings['check_blazy']) && !empty($build['items'][0])) {
      $this->isBlazy($settings, $build['items'][0]);
    }

    $this->prepareSettings($settings);

    // Use static grid framework if so configured.
    $settings['ungridstack']  = $this->unGridStack;
    $settings['debug']        = $this->configLoad('debug', 'gridstack.settings');
    $settings['_ipe_layouts'] = !empty($settings['_ipe_layouts']) && isset($element['#attributes']) && isset($element['#attributes']['data-layout-delta']);
    $settings['_ipe_panels']  = !empty($settings['_ipe_panels']) && isset($element['#prefix']) && strpos($element['#prefix'], 'panels-ipe-content') !== FALSE;
    $settings['_access_ipe']  = $is_admin = $settings['_ipe_layouts'] || $settings['_ipe_panels'];
    $settings['library']      = $is_admin ? $this->configLoad('library', 'gridstack.settings') : '';

    // Adds regions for Layout Builder and Panels IPE integration.
    $regions = [];
    $children = Element::children($element);
    if ($is_admin) {
      foreach ($children as $child) {
        if ($child == 'items') {
          continue;
        }
        $regions[$child] = $element[$child];
      }

      $build['attached']['library'][] = 'gridstack/admin_base';
      if (!empty($settings['debug'])) {
        $build['attached']['library'][] = 'gridstack/debug';
      }
    }

    // Adds the required elements for the template.
    $attachments            = $this->attach($settings);
    $attributes             = $this->prepareAttributes($build);
    $element['#attributes'] = empty($element['#attributes']) ? $attributes : NestedArray::mergeDeep($element['#attributes'], $attributes);
    $element['#optionset']  = $build['optionset'] = $this->gridStackOptionset;
    $element['#settings']   = $build['settings'] = $settings;
    $element['#attached']   = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);
    $element['#items']      = $this->buildItems($build, $regions);
    $element['#cache']      = $this->getCacheMetadata($build);

    // Panels IPE, Layout Builder are happy, safe to free up wasted children.
    if ($children) {
      foreach ($children as $child) {
        unset($element[$child]);
      }
    }

    unset($build);
    return $element;
  }

}
