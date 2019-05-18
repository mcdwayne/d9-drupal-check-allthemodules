<?php

namespace Drupal\outlayer;

use Drupal\Component\Serialization\Json;
use Drupal\gridstack\GridStackManager;
use Drupal\outlayer\Entity\Outlayer;

/**
 * Implements OutlayerManagerInterface.
 */
class OutlayerManager extends GridStackManager implements OutlayerManagerInterface {

  /**
   * The Outlayer optionset.
   *
   * @var \Drupal\outlayer\Entity\Outlayer
   */
  protected $outLayerOptionset;

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []) {
    $attach['blazy'] = TRUE;

    $load = parent::attach($attach);

    // @todo $load['library'][] = 'outlayer/imagesloaded';
    if (!empty($attach['plugin_id'])) {
      switch ($attach['plugin_id']) {
        case 'outlayer_grid':
          if (!empty($attach['style'])) {
            $load['library'][] = 'outlayer/load.' . $attach['style'];
          }
          break;

        case 'outlayer_isotope':
          if ($layout = $this->getIsotopeExtraLibrary($attach)) {
            $load['library'][] = $layout;
          }

          $load['library'][] = 'outlayer/load.isotope';
          break;

        default:
          break;
      }
    }

    if (!empty($attach['grid_custom'])) {
      $load['library'][] = 'outlayer/ungridstack';
    }

    $js = Outlayer::defaultSettings();
    foreach (['columnWidth', 'gutter', 'rowHeight'] as $key) {
      if (isset($js['layout'][$key]) && is_numeric($js['layout'][$key])) {
        $js['layout'][$key] = (int) $js['layout'][$key];
      }
    }
    $load['drupalSettings']['outLayer'] = $js;

    $this->moduleHandler->alter('outlayer_attach', $load, $attach);
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareAttributes(array &$build) {
    $attributes = parent::prepareAttributes($build);
    $settings = &$build['settings'];

    $js = isset($this->outLayerOptionset) ? $this->outLayerOptionset->getOptions() : [];

    $this->massageOptions($js, $settings);

    // Pass data to template.
    $plugin = 'isotope';
    $layout = $js['layoutMode'];
    if (!empty($settings['plugin_id']) && $settings['plugin_id'] == 'outlayer_grid') {
      $plugin = $settings['style'];
      if (!empty($js[$layout])) {
        foreach ($js[$layout] as $key => $value) {
          $js[$key] = $value;
        }
      }
      unset($js['layoutMode'], $js[$layout]);
    }

    $defaults = Outlayer::defaultSettings();
    $js = array_diff_assoc($js, $defaults);

    $attributes['class'][] = 'outlayer';
    $attributes['class'][] = 'outlayer--' . $plugin;
    $attributes['data-outlayer-' . $plugin] = Json::encode($js);

    // Sync filters, sorters with the main grid display via similar ID.
    if (!empty($settings['instance_id'])) {
      $attributes['data-instance-id'] = $settings['instance_id'];
    }

    return $attributes;
  }

  /**
   * Massages the options.
   */
  public function massageOptions(array &$js, array &$settings) {
    Outlayer::massageOptions($js);
    $layout = $js['layoutMode'];

    // Have no option for this as we don't offer many templates.
    if (empty($js['itemSelector'])) {
      $js['itemSelector'] = '.gridstack__box';
    }

    // If having stamps, provides relevant classes.
    if (!empty($settings['stamp'])) {
      $js['stamp'] = '.box--stamp';
      $js['itemSelector'] = '.gridstack__box:not(.box--stamp)';
    }

    // Provides sorters.
    if (!empty($settings['sorters'])) {
      $sorts = [];
      foreach ($settings['sorters'] as $key) {
        $sorts[$key] = '[data-srtr-' . $key . ']';
      }
      $js['getSortData'] = $sorts;

      // @todo extract correct data.
      if (!empty($settings['sortBy'])) {
        $js['sortBy'] = $settings['sortBy'];
      }
    }

    // Sizing options columnWidth, rowHeight, and gutter can be set with an
    // element. The size of the element is then used as the value of the option.
    foreach (['columnWidth', 'gutter', 'rowHeight'] as $key) {
      if (!empty($js[$layout][$key])) {
        if (is_numeric($js[$layout][$key])) {
          $js[$layout][$key] = (int) $js[$layout][$key];
        }
        else {
          $settings[$key . 'Sizer'] = str_replace('.', '', $js[$layout][$key]);
        }
      }
    }

    foreach (['stagger', 'transitionDuration'] as $key) {
      if (!empty($js[$key]) && is_numeric($js[$key])) {
        $js[$key] = (int) $js[$key];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareSettings(array &$settings) {
    $settings += OutlayerDefault::htmlSettings();

    if (isset($this->outLayerOptionset)) {
      $settings['layoutMode'] = $this->outLayerOptionset->getOption('layoutMode');
      $settings['fluid'] = $this->outLayerOptionset->getOption('percentPosition');
    }

    parent::prepareSettings($settings);
  }

  /**
   * Gets the layout library specific to Isotope layouts.
   */
  public function getIsotopeExtraLibrary(array $attach) {
    $layout = empty($attach['layoutMode']) ? '' : $attach['layoutMode'];
    foreach (OutlayerDefault::extraLayouts() as $name => $id) {
      if ($layout == $name) {
        return 'outlayer/isotope-' . $id;
      }
    }
    return FALSE;
  }

  /**
   * Extracts grid custom.
   */
  public function extractGridCustom(array $settings) {
    $dimensions = [];
    if (!empty($settings['grid_custom'])) {
      $values = array_map('trim', explode(" ", $settings['grid_custom']));
      foreach ($values as $value) {
        if (strpos($value, 'x') !== FALSE) {
          list($width, $height) = array_pad(array_map('trim', explode("x", $value, 2)), 2, NULL);
        }
        else {
          $width = $value;
          $height = 0;
        }

        $dimensions[] = ['width' => $width, 'height' => $height];
      }
    }

    return $dimensions;
  }

  /**
   * {@inheritdoc}
   */
  public function boxAttributes(array &$settings, $current = 'grids') {
    $attributes = parent::boxAttributes($settings, $current);

    // Outlayer Grid and Isotope have custom grids which will disable gridstack.
    if ($this->unGridStack && !empty($settings['dimensions'])) {
      $delta = isset($settings['delta']) ? $settings['delta'] : 0;
      $count = $settings['dimensions_count'] - 1;

      // If not defined, at least skip the first, normally the largest box.
      $index = 0;
      if (isset($settings['dimensions'][$delta])) {
        $index = $delta;
      }
      else {
        $index = $settings['dimensions_count'] == 1 ? 0 : rand(1, $count);
      }

      if (isset($settings['dimensions'][$index]) && !empty($settings['dimensions'][$index]['width'])) {
        $attributes['data-ol-width'] = (int) $settings['dimensions'][$index]['width'];
        if (!empty($settings['dimensions'][$index]['height'])) {
          $attributes['data-ol-height'] = (int) $settings['dimensions'][$index]['height'];
        }
      }
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    $settings = &$build['settings'];

    $this->unGridStack = !empty($settings['grid_custom']);
    $this->outLayerOptionset = Outlayer::loadWithFallback($settings['outlayer']);

    if ($settings['plugin_id'] == 'outlayer_grid') {
      // Tells Gridstack to not load any asset with this.
      $this->unGridStack = TRUE;
    }

    return parent::build($build);
  }

}
