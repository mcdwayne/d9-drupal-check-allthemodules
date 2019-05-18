<?php

namespace Drupal\gridstack\Form;

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\blazy\Form\BlazyAdminInterface;
use Drupal\gridstack\GridStackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides resusable admin functions or form elements.
 */
class GridStackAdmin implements GridStackAdminInterface {

  use StringTranslationTrait;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Form\BlazyAdminInterface
   */
  protected $blazyAdmin;

  /**
   * The gridstack manager service.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * Constructs a GridStackAdmin object.
   *
   * @param \Drupal\blazy\Form\BlazyAdminInterface $blazy_admin
   *   The blazy admin service.
   * @param \Drupal\gridstack\GridStackManagerInterface $manager
   *   The gridstack manager service.
   */
  public function __construct(BlazyAdminInterface $blazy_admin, GridStackManagerInterface $manager) {
    $this->blazyAdmin = $blazy_admin;
    $this->manager    = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('blazy.admin.extended'), $container->get('gridstack.manager'));
  }

  /**
   * Returns the blazy admin formatter.
   */
  public function blazyAdmin() {
    return $this->blazyAdmin;
  }

  /**
   * Returns the slick manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns all settings form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition['namespace']  = 'gridstack';
    $definition['skins']      = $this->getSkinOptions();
    $definition['style']      = isset($definition['style']) ? $definition['style'] : FALSE;
    $definition['grid_form']  = isset($definition['grid_form']) ? $definition['grid_form'] : FALSE;
    $definition['optionsets'] = $this->manager->getOptionsetsByGroupOptions('js');

    foreach (['background', 'caches', 'fieldable_form', 'vanilla'] as $key) {
      $definition[$key] = isset($definition[$key]) ? $definition[$key] : TRUE;
    }

    $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts']) : $this->getLayoutOptions();

    $this->openingForm($form, $definition);
    $this->mainForm($form, $definition);
    $this->closingForm($form, $definition);
  }

  /**
   * Returns the opening form elements.
   */
  public function openingForm(array &$form, &$definition = []) {
    $path    = drupal_get_path('module', 'gridstack');
    $is_ui   = $this->manager()->getModuleHandler()->moduleExists('gridstack_ui');
    $is_help = $this->manager()->getModuleHandler()->moduleExists('help');
    $route   = ['name' => 'gridstack_ui'];
    $readme  = $is_ui && $is_help ? Url::fromRoute('help.page', $route)->toString() : Url::fromUri('base:' . $path . '/README.md')->toString();

    if (!isset($form['optionset'])) {
      $this->blazyAdmin->openingForm($form, $definition);

      if ($is_ui) {
        $route_name = 'entity.gridstack.collection';
        $form['optionset']['#description'] = $this->t('Manage optionsets at <a href=":url" target="_blank">the optionset admin page</a>.', [':url' => Url::fromRoute($route_name)->toString()]);
      }
    }

    if (isset($form['skin'])) {
      $form['skin']['#description'] = $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. Leave empty to DIY. Or use hook_gridstack_skins_info() and implement \Drupal\gridstack\GridStackSkinInterface to register ones.', [':url' => $readme]);
    }

    if (isset($form['background'])) {
      $form['background']['#description'] = $this->t('If trouble with image sizes not filling the given box, check this to turn the image into CSS background instead. To assign different image style per grid/box, edit the working optionset.');
      $form['background']['#weight'] = -40;
    }
  }

  /**
   * Returns the main form elements.
   */
  public function mainForm(array &$form, $definition = []) {
    if (!empty($definition['image_style_form'])) {
      $this->blazyAdmin->imageStyleForm($form, $definition);
    }

    if (!empty($definition['media_switch_form'])) {
      $this->blazyAdmin->mediaSwitchForm($form, $definition);
    }

    if (!empty($definition['fieldable_form'])) {
      $this->blazyAdmin->fieldableForm($form, $definition);

      if (!empty($definition['links'])) {
        $form['category'] = [
          '#title'         => $this->t('Category'),
          '#type'          => 'select',
          '#options'       => $definition['links'],
          '#description'   => $this->t('The category to display at the top of box.'),
        ];
      }
    }

    if (!empty($definition['stamps'])) {
      $form['stamp'] = [
        '#title'         => $this->t('Stamp'),
        '#type'          => 'select',
        '#options'       => $definition['stamps'],
        '#description'   => $this->t('Stamp is _just a unique list, <b>Html list</b>, such as Latest news, blogs, testimonials, etc. replacing one of the other boring boxes, including rich ones. Leave empty to not use stamp.'),
        '#weight'        => -67,
      ];

      $form['stamp_index'] = [
        '#title'         => $this->t('Stamp index'),
        '#type'          => 'textfield',
        '#description'   => $this->t('Which index, a stamp should be inserted into.'),
        '#weight'        => -66,
      ];
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#title'] = $this->t('Rich box');
      $form['overlay']['#description'] = $this->t('Replace <b>Main stage/ image</b> if a node has one. It can be any entity reference, like Block, etc. Use block_field.module for ease of block additions. How? Create a sticky (or far future created) node or two containing a Slick carousel, video, weather, time, donations, currency, ads, or anything as a block field, and put it here. Be sure different from the <b>Main stage</b>. While regular boxes are updated with new contents, these rich boxes may stay the same, and sticky (Use Views with sort by sticky or desc by creation).');
      $form['overlay']['#weight'] = -65;
    }

    if (!empty($definition['breakpoints'])) {
      $this->blazyAdmin->breakpointsForm($form, $definition);
    }
  }

  /**
   * Returns the closing ending form elements.
   */
  public function closingForm(array &$form, $definition = []) {
    if (!isset($form['cache'])) {
      $this->blazyAdmin->closingForm($form, $definition);
    }

    $form['#attached']['library'][] = 'gridstack/admin';
  }

  /**
   * Returns available skins for select options.
   */
  public function getSkinOptions() {
    return $this->manager->getSkinOptions();
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions() {
    return [
      'bottom' => $this->t('Caption bottom'),
      'center' => $this->t('Caption center'),
      'top'    => $this->t('Caption top'),
    ];
  }

  /**
   * Return the field formatter settings summary.
   */
  public function getSettingsSummary($definition = []) {
    return $this->blazyAdmin->getSettingsSummary($definition);
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions($target_bundles = [], $allowed_field_types = [], $entity_type_id = 'media', $target_type = '') {
    return $this->blazyAdmin->getFieldOptions($target_bundles, $allowed_field_types, $entity_type_id, $target_type);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, $definition = []) {
    $this->blazyAdmin->finalizeForm($form, $definition);
  }

}
