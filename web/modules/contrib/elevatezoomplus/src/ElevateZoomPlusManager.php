<?php

namespace Drupal\elevatezoomplus;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\elevatezoomplus\Entity\ElevateZoomPlus;

/**
 * Provides ElevateZoom Plus library methods mainly for hooks.
 */
class ElevateZoomPlusManager {

  use StringTranslationTrait;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $manager;

  /**
   * Static cache for the optionset options.
   *
   * @var array
   */
  protected $optionsetOptions;

  /**
   * Constructs a ElevateZoomPlusManager instance.
   */
  public function __construct(BlazyManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Returns Blazy manager service.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Implements hook_library_info_alter().
   */
  public function libraryInfoAlter(&$libraries, $extension) {
    $library = libraries_get_path('elevatezoom-plus') ?: libraries_get_path('ez-plus');
    if ($library) {
      $ext = is_file($library . '/src/jquery.ez-plus.min.js') ? 'min.js' : 'js';
      $libraries['elevatezoomplus']['js']['/' . $library . '/src/jquery.ez-plus.' . $ext] = ['weight' => -5];
    }
  }

  /**
   * Checks if the requirements are met.
   */
  public function isApplicable(array $settings) {
    return !empty($settings['elevatezoomplus']) && (!empty($settings['first_uri']) || !empty($settings['uri']));
  }

  /**
   * Returns available options for select options.
   *
   * @todo remove if BlazyManager has it.
   */
  public function getOptionsetOptions($entity_type = '') {
    if (!isset($this->optionsetOptions)) {
      $optionsets = [];
      foreach ($this->manager->entityLoadMultiple($entity_type) as $key => $entity) {
        $optionsets[$key] = strip_tags($entity->label());
      }
      $this->optionsetOptions = $optionsets;
    }

    return $this->optionsetOptions;
  }

  /**
   * Implements hook_blazy_form_element_alter().
   */
  public function formElementAlter(array &$form, array $definition = []) {
    $settings = $definition['settings'];
    // Exclude from blazy text formatters, or blazy views grid.
    if (empty($definition['no_image_style']) && !isset($settings['grouping'])) {
      $form['elevatezoomplus'] = [
        '#type'          => 'select',
        '#title'         => $this->t('ElevateZoom Plus'),
        '#options'       => $this->getOptionsetOptions('elevatezoomplus'),
        '#empty_option'  => $this->t('- None -'),
        '#default_value' => isset($settings['elevatezoomplus']) ? $settings['elevatezoomplus'] : '',
        '#description'   => $this->t('Choose an optionset.'),
        '#weight'        => -98.99,
        '#enforce'       => FALSE,
      ];

      // Hooks into Blazy UI to support Blazy Filter.
      if (isset($settings['admin_css'])) {
        $form['extras']['#access'] = TRUE;
        $form['extras']['elevatezoomplus'] = $form['elevatezoomplus'];
        $form['extras']['elevatezoomplus']['#default_value'] = isset($settings['extras']['elevatezoomplus']) ? $settings['extras']['elevatezoomplus'] : '';
        $form['extras']['elevatezoomplus']['#description'] .= ' ' . $this->t('Blazy Filter only. Warning! Not working nicely. This needs extra image styles which are lacking with inline images.');
        unset($form['elevatezoomplus']);
      }
      else {
        $form['elevatezoomplus']['#description'] .= ' ' . $this->t('Requires any lightbox (<b>not: Image to iFrame, Image linked to content, Image rendered</b>) for <b>Media switcher</b> if using Slick Carousel with asNavFor. If not, be sure to choose only <b>Image to Elevatezoomplus</b>.');
        if ($this->manager->configLoad('admin_css', 'blazy.settings')) {
          $form['closing']['#attached']['library'][] = 'elevatezoomplus/admin';
        }
      }
    }
  }

  /**
   * Return the options for the JSON object.
   */
  public function getOptions(array $settings = []) {
    $config = $this->manager->configLoad();
    $fallback = isset($config['extras']['elevatezoomplus']) ? $config['extras']['elevatezoomplus'] : 'default';
    $plugin_id = isset($settings['plugin_id']) ? $settings['plugin_id'] : '';
    $option_id = $plugin_id == 'blazy_filter' ? $fallback : $settings['elevatezoomplus'];
    $optionset = ElevateZoomPlus::load($option_id);
    $options = $optionset->getSettings(TRUE);

    // If not using Slick Carousel, provides a static grid gallery.
    if (empty($settings['nav'])) {
      $options['galleryItem'] = '[data-elevatezoomplus-trigger]';
      $options['galleryActiveClass'] = 'is-active';

      if (isset($settings['gallery_id'])) {
        $options['gallery'] = $settings['gallery_id'];
      }
      else {
        $options['gallerySelector'] = '[data-elevatezoomplus-gallery]';
      }
    }
    if (isset($options['zoomWindowPosition']) && is_numeric($options['zoomWindowPosition'])) {
      $options['zoomWindowPosition'] = (int) $options['zoomWindowPosition'];
    }
    if (empty($options['loadingIcon'])) {
      unset($options['loadingIcon']);
    }

    return $options;
  }

  /**
   * Sets ElevateZoomPlus #pre_render callback.
   */
  public function buildAlter(array &$build, $settings = []) {
    if ($this->isApplicable($settings)) {
      $build['#pre_render'][] = [$this, 'preRenderBuild'];
    }
  }

  /**
   * The #pre_render callback: Provides ElevateZoomPlus related contents.
   */
  public function preRenderBuild($build) {
    $build['#theme_wrappers'][] = 'elevatezoomplus';
    return $build;
  }

  /**
   * Implements hook_blazy_attach_alter().
   */
  public function attachAlter(array &$load, $attach = []) {
    $load['drupalSettings']['elevateZoomPlus'] = ElevateZoomPlus::defaultSettings();
    $load['library'][] = 'elevatezoomplus/load';
  }

  /**
   * Overrides variables for theme_blazy().
   */
  public function preprocessBlazy(&$variables) {
    $settings = $variables['settings'];
    $zoom_url = $variables['url'];

    // Support video thumbnail since `url` points to a provider site.
    if ($settings['type'] == 'video' && !empty($settings['box_url'])) {
      $zoom_url = $settings['box_url'];
    }

    // Re-use thumbnail style for the stage/ preview image.
    $stage_url = empty($variables['attributes']['data-thumb']) ? $zoom_url : $variables['attributes']['data-thumb'];

    // Provides the expected attributes for JS.
    $variables['url_attributes']['data-image'] = $stage_url;
    $variables['url_attributes']['data-zoom-image'] = $zoom_url;

    // If using Slick asNavFor, make the litebox link as a zoom trigger as well.
    $id = Blazy::getHtmlId('elevatezoomplus');
    if (!empty($settings['nav'])) {
      $variables['url_attributes']['class'][] = 'elevatezoomplus';
      $variables['url_attributes']['id'] = $id;
    }
    else {
      $variables['item_attributes']['id'] = $id;
    }
  }

}
