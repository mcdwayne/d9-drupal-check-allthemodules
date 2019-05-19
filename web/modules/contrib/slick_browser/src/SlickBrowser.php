<?php

namespace Drupal\slick_browser;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\NestedArray;
use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\blazy\BlazyEntity;
use Drupal\blazy\Dejavu\BlazyVideoTrait;
use Drupal\slick\Form\SlickAdminInterface;
use Drupal\slick\SlickFormatterInterface;

/**
 * Defines a Slick Browser.
 */
class SlickBrowser implements SlickBrowserInterface {

  use StringTranslationTrait;
  use BlazyVideoTrait;

  /**
   * The slick admin.
   *
   * @var \Drupal\slick\Form\SlickAdminInterface
   */
  protected $slickAdmin;

  /**
   * The slick field formatter manager.
   *
   * @var \Drupal\slick\SlickFormatterInterface
   */
  protected $formatter;

  /**
   * The slick field formatter manager.
   *
   * @var \Drupal\blazy\BlazyEntity
   */
  protected $blazyEntity;

  /**
   * Constructs a SlickBrowser instance.
   */
  public function __construct(BlazyEntity $blazy_entity, SlickAdminInterface $slick_admin, SlickFormatterInterface $formatter) {
    $this->blazyEntity = $blazy_entity;
    $this->slickAdmin = $slick_admin;
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('blazy.entity'),
      $container->get('slick.admin'),
      $container->get('slick.formatter')
    );
  }

  /**
   * Returns the slick admin service.
   */
  public function slickAdmin() {
    return $this->slickAdmin;
  }

  /**
   * Returns the blazy admin service.
   */
  public function blazyAdmin() {
    return $this->slickAdmin->blazyAdmin();
  }

  /**
   * Returns the slick manager.
   */
  public function manager() {
    return $this->slickAdmin->manager();
  }

  /**
   * Returns the slick formatter.
   */
  public function formatter() {
    return $this->formatter;
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->formatter;
  }

  /**
   * Add wrappers around the self-closed input element for styling, or iconing.
   *
   * JS is easier, but hence to avoid FOUC.
   */
  public static function wrapButton(array &$input, $key = '') {
    $css = str_replace('_button', '', $key);
    $css = str_replace('_', '-', $css);

    $attributes = new Attribute();
    $attributes->setAttribute('title', t('@css', ['@css' => $css]));
    $attributes->addClass(['button-wrap', 'button-wrap--' . $css]);

    $content = '';
    if ($key == 'remove' || $key == 'remove_button') {
      // @todo: Use JS, not AJAX, for removal - button--sb data-target="remove".
      $content .= '<span class="button--wrap__mask">&nbsp;</span><span class="button--wrap__confirm">' . t('Confirm') . '</span>';
      $attributes->addClass(['button-wrap--confirm']);
    }

    $input['#prefix'] = '<span' . $attributes . '>' . $content;
    $input['#suffix'] = '</span>';
    $input['#attributes']['class'][] = 'button--' . $css;
  }

  /**
   * Implements hook_form_alter().
   */
  public function formAlter(&$form, FormStateInterface &$form_state, $form_id) {
    // @todo: Make it flexible enough to support non-slick_browser plugins.
    if (strpos($form_id, 'slick_browser') === FALSE) {
      return;
    }

    $form['#attached']['library'][] = 'slick_browser/form';
    $form['#attributes']['class'][] = 'sb form form--sb clearfix';
    $form['#prefix'] = '<a id="sb-target" tabindex="-1"></a>';

    // Adds header, and footer wrappers to hold navigations and thumbnails.
    foreach (['header', 'footer'] as $key) {
      $form[$key] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['sb__' . $key],
        ],
        '#weight' => $key == 'header' ? -9 : -8,
      ];

      // Adds Blazy marker for lazyloading selection thumbnails.
      if ($key == 'footer') {
        $form['footer']['#attributes']['data-blazy'] = '';
      }
    }

    // "Select entities" button.
    $view = FALSE;
    $select_title = 'Select';
    if (isset($form['widget'])) {
      if (isset($form['widget']['actions'])) {
        $form['widget']['actions']['#weight'] = -7;
        if (!empty($form['widget']['actions']['submit']['#value'])) {
          $select_title = $form['widget']['actions']['submit']['#value'];
        }
        $form['widget']['actions']['#attributes']['class'][] = 'button-group button-group--select button-group--text';
        $form['header']['actions'] = $form['widget']['actions'];
        unset($form['widget']['actions']);
      }

      // Adds relevant classes for the current step identified by active widget.
      foreach (Element::children($form['widget']) as $widget) {
        $view = $widget == 'view';
        $widget_css = str_replace('_', '-', $widget);
        $form['#attributes']['class'][] = 'form--' . $widget_css;
        $form['#attributes']['data-dialog'] = $widget_css;
      }
    }

    // Selected items.
    if (!empty($form['selection_display'])) {
      // Attach Blazy here once to avoid multiple Blazy invocationa.
      $form['#attached']['library'][] = 'blazy/load';

      // Wraps self-closed input elements for easy styling, or iconing.
      foreach (['show_selection', 'use_selected'] as $key) {
        if (isset($form['selection_display'][$key])) {
          $input = &$form['selection_display'][$key];

          // Enforces visibility for dynamic items, and let JS takes care of it.
          if ($key == 'show_selection') {
            static::wrapButton($input, $key);
            $form['selection_display'][$key]['#access'] = TRUE;
          }
          if ($key == 'use_selected') {
            $form['selection_display'][$key]['#weight'] = -9;
            $form['selection_display'][$key]['#attributes']['class'][] = 'button--primary';
            $form['header']['actions'][$key] = $form['selection_display'][$key];
            unset($form['selection_display'][$key]);
          }
        }
      }

      if (isset($form['selection_display']['selected'])) {
        $form['selection_display']['selected']['#weight'] = 200;

        // Wraps self-closed input elements for easy styling, or iconing.
        foreach (Element::children($form['selection_display']['selected']) as $key) {
          if (isset($form['selection_display']['selected'][$key]['remove_button'])) {
            $input = &$form['selection_display']['selected'][$key]['remove_button'];
            static::wrapButton($input, 'remove');
          }
        }
      }

      $form['footer']['selection_display'] = $form['selection_display'];
      unset($form['selection_display']);
    }
    else {
      $form['#attributes']['class'][] = 'is-no-selection';
    }

    // Adds Slick Browser library, and helper data attributes or classes.
    $storage = $form_state->getStorage();
    if (isset($storage['entity_browser']['validators']['cardinality']['cardinality'])) {
      $cardinality = $storage['entity_browser']['validators']['cardinality']['cardinality'];
      if (!empty($cardinality)) {
        $form['#attributes']['data-cardinality'] = $cardinality;
        $hint = '<p class="sb__cardinality">';
        if ($cardinality != -1) {
          $hint .= $this->formatPlural($cardinality, 'One item allowed.', '@count items allowed.');
          $hint .= ' ' . $this->t('Remove one to select another.');
        }
        else {
          $hint .= $this->t('Hit <strong>@select</strong> button to temporarily store selection.', ['@select' => $select_title]);
        }
        $hint .= '</p>';

        $form['footer']['cardinality_hint'] = [
          '#markup' => $hint,
          '#weight' => -9,
        ];
      }
    }

    // Extracts SB plugin IDs from the form ID. Any better?
    $id = str_replace(['entity_browser_', '_form'], '', $form_id);

    // Adds contextual classes based on SB entity browser entity.
    if ($eb = EntityBrowser::load($id)) {
      // Modal, iframe, etc.
      $form['#attributes']['class'][] = 'form--' . str_replace('_', '-', $eb->getDisplay()->getPluginId());
      $form['#attributes']['class'][] = 'form--' . str_replace('_', '-', $eb->getWidgetSelector()->getPluginId());

      // Entity display plugins: slick_browser_file, slick_browser_media, etc.
      // Has selection_position.
      $config_display = $eb->getSelectionDisplay()->getConfiguration();

      // Has buttons_position, tabs_position.
      $config_widget = $eb->getWidgetSelector()->getConfiguration();

      $selections = '';
      if (!empty($config_display) && !empty($config_display['display_settings']['selection_position'])) {
        $selections = $config_display['display_settings']['selection_position'];
        $form['#attributes']['class'][] = 'form--selection-' . $selections;
        $form['#attributes']['class'][] = in_array($selections, ['left', 'right']) ? 'form--selection-v' : 'form--selection-h';
      }

      if (!empty($config_widget['tabs_position'])) {
        $buttons = $config_widget['buttons_position'];
        $tabs = $config_widget['tabs_position'];
        $tabs_pos = '';

        // Tabs at sidebars within selection display.
        if (($tabs == 'left' && $selections == 'left') || ($tabs == 'right' && $selections == 'right')) {
          $tabs_pos = 'footer';
        }
        // Tabs at header along with navigation buttons/ arrows.
        elseif (($tabs == 'bottom' && $buttons == 'bottom') || ($tabs == 'top' && $buttons == 'top')) {
          $tabs_pos = 'header';
        }

        $form['#attributes']['data-tabs-pos'] = $tabs_pos;

        // Adds classes to identify the amount of tabs, etc.
        if ($view) {
          if (!empty($form['widget_selector'])) {
            $count = count(Element::children($form['widget_selector']));
            $form['#attributes']['class'][] = $count > 2 ? 'form--tabs-stacked' : 'form--tabs-inline';
          }
        }
      }
    }

    if (empty($storage['entity_browser']['selected_entities'])) {
      $form['#attributes']['class'][] = 'is-empty';
    }
  }

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function widgetEntiyBrowserFormAlter(&$element, FormStateInterface $form_state, $context) {
    $plugin_id = $context['widget']->getPluginId();
    $widget_settings = $context['widget']->getSettings();
    $field = $context['items']->getFieldDefinition();

    // Chances are SB browsers within iframes/modals, even if no SB widgets.
    // Or using any of SB field_widget_display.
    // Only EntityReferenceBrowserWidget has field_widget_display, not FBW.
    $widget = empty($widget_settings['field_widget_display']) ? [] : $widget_settings['field_widget_display'];
    if (!empty($widget) && ($widget == 'slick_browser_file' || $widget == 'slick_browser_media')) {
      $element['#attached']['library'][] = 'slick_browser/media';
    }

    // Load relevant assets based on the chosen SB browsers plugins.
    if (!empty($widget_settings['entity_browser'])) {
      $id = $widget_settings['entity_browser'];
      if ($eb = EntityBrowser::load($id)) {
        // Selection displays: modal, iframe, etc.
        if ($eb->getDisplay()->getPluginId() == 'modal') {
          $element['#attached']['library'][] = 'slick_browser/modal';
        }

        // @todo: Check for iframe, etc.
        // Entity display plugins: slick_browser_file, slick_browser_media, etc.
        // $config_display = $eb->getSelectionDisplay()->getConfiguration();
        // $config_widget = $eb->getWidgetSelector()->getConfiguration();
      }
    }

    // Always assumes no "Display style" of SB widgets is enabled.
    $element['#slick_browser'] = FALSE;
    $field_settings = $field->getSettings();
    $config = [];
    foreach (['alt_field', 'title_field', 'target_type'] as $key) {
      $config[$key] = isset($field_settings[$key]) ? $field_settings[$key] : FALSE;
    }

    $config['cardinality'] = $field->getFieldStorageDefinition()->getCardinality();
    $config['field_name'] = $context['items']->getName();
    $config['field_type'] = $field->getType();
    $config['entity_type_id'] = $field->getEntityTypeId();
    $config['plugin_id'] = $plugin_id;

    switch ($plugin_id) {
      case 'entity_browser_entity_reference':
        $this->widgetEntityBrowserEntityReferenceFormAlter($element, $form_state, $context, $config);
        break;

      case 'entity_browser_file':
        $this->widgetEntityBrowserFileFormAlter($element, $form_state, $context, $config);
        break;

      default:
        break;
    }

    // Ony proceed if we are conciously allowed via "Display style" option.
    if (empty($element['#slick_browser'])) {
      return;
    }

    // We are here because we are allowed to.
    // Build common settings to all supported plugins.
    // The non-image field type has 'display_field', 'description'.
    $settings = array_merge($config, $element['#slick_browser']);

    // Enforce Blazy to work with hidden element such as with EB selection.
    // Prevents collapsed details from breaking lazyload.
    $load['drupalSettings']['blazy']['loadInvisible'] = TRUE;
    $load['library'][] = 'slick_browser/widget';

    $attachments = [];
    // Disable tabledrag, including FBW table CSS, for Slick/ CSS grid.
    if ($plugin_id == 'entity_browser_file') {
      $attachments = $load;
    }
    else {
      $attachments = isset($element['current']) && isset($element['current']['#attached']) ? NestedArray::mergeDeep($element['current']['#attached'], $load) : $load;
    }

    // Adds own theme to style entity browser elements.
    // Build the SB widgets, nothing to do with SB browsers here on.
    // This used to be "Slick Widget", moved into "Slick Browser".
    $element['#attributes']['class'][] = 'sb sb--details';
    if (empty($element['#open'])) {
      $element['#open'] = TRUE;
      $element['#attributes']['class'][] = 'sb--details-hidden';
    }

    $element['current']['#theme']          = 'slick_browser';
    $element['current']['#attached']       = $attachments;
    $element['current']['#settings']       = $settings;
    $element['current']['#attributes']     = [];
    $element['current']['#theme_wrappers'] = [];

    if ($plugin_id == 'entity_browser_file' && Element::children($element['current'])) {
      unset($element['current']['#type'], $element['current']['#header'], $element['current']['#tabledrag']);
    }
  }

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function widgetEntityBrowserEntityReferenceFormAlter(&$element, FormStateInterface $form_state, $context, $config) {
    $widget_settings = $context['widget']->getSettings();
    if (empty($widget_settings['field_widget_display'])) {
      return;
    }

    if (strpos($widget_settings['field_widget_display'], 'slick_browser') === FALSE) {
      return;
    }

    $settings = $widget_settings['field_widget_display_settings'] + $config;
    if (empty($settings['style'])) {
      return;
    }

    $settings['plugin_id_sb'] = $widget_settings['field_widget_display'];
    $element['#slick_browser'] = array_merge(SlickBrowserDefault::entitySettings(), $settings);
  }

  /**
   * Implements hook_field_widget_WIDGET_TYPE_form_alter().
   */
  public function widgetEntityBrowserFileFormAlter(&$element, FormStateInterface $form_state, $context, $config) {
    $widget_settings = $context['widget']->getSettings();

    if (empty($widget_settings['entity_browser'])) {
      return;
    }

    if (strpos($widget_settings['entity_browser'], 'slick_browser') === FALSE) {
      return;
    }

    $settings = self::buildThirdPartySettings($context['widget']) + $config;
    if (empty($settings['style'])) {
      return;
    }

    // Allows Slick Browser to remove File Browser empty table.
    $element['#slick_browser'] = $settings;

    // Yet only proceed if entities are given.
    $entities = isset($element['entity_browser']) ? $element['entity_browser']['#default_value'] : [];
    if (empty($entities)) {
      // Supports cardinality 1, or single image.
      foreach (Element::children($element['current']) as $entity_id) {
        if (isset($element['current'][$entity_id]['display']['#style_name']) && !empty($settings['image_style'])) {
          $element['current'][$entity_id]['display']['#style_name'] = $settings['image_style'];
        }
      }
      return;
    }

    foreach ($entities as $entity) {
      /** @var \Drupal\file\Entity\File $entity */
      // @todo: Only provides custom image, if no URI available.
      $data = $this->getImageItem($entity);
      $data['settings'] = isset($data['settings']) ? array_merge($settings, $data['settings']) : $settings;

      $display = $this->blazyEntity->build($data, $entity, $entity->getFilename());
      $display['#settings'] = isset($display['#settings']) ? array_merge($data['settings'], $display['#settings']) : $data['settings'];
      $element['current'][$entity->id()]['display'] = $display;
    }
  }

  /**
   * Implements hook_field_widget_third_party_settings_form().
   */
  public function widgetThirdPartySettingsForm(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state) {
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $definition = [
      'breakpoints'      => SlickBrowserDefault::getConstantBreakpoints(),
      'cardinality'      => $cardinality,
      'image_style_form' => TRUE,
      'thumb_positions'  => TRUE,
      'nav'              => TRUE,
      'settings'         => self::buildThirdPartySettings($plugin),
      'style'            => TRUE,
    ] + self::scopedFormElements();

    $element = [];
    $this->buildSettingsForm($element, $definition);

    if (isset($element['image_style']['#description'])) {
      $element['image_style']['#description'] = $this->t('Overrides the above Preview image style.');
    }
    return $element;
  }

  /**
   * Prepare settings.
   */
  public static function buildThirdPartySettings($plugin, $defaults = []) {
    $defaults = $defaults ?: array_merge(SlickBrowserDefault::widgetSettings(), $plugin->getSettings());
    $settings = [];
    foreach ($defaults as $key => $default) {
      $settings[$key] = $plugin->getThirdPartySetting('slick_browser', $key, $default);
    }

    // No need to load library per item, the top-level element does it once.
    $settings['_basic']    = TRUE;
    $settings['_detached'] = TRUE;

    return array_merge(SlickBrowserDefault::entitySettings(), $settings);
  }

  /**
   * Defines common widget form elements.
   */
  public function buildSettingsForm(array &$element, $definition) {
    $cardinality = isset($definition['cardinality']) ? $definition['cardinality'] : '';

    // Build form elements.
    $this->slickAdmin->buildSettingsForm($element, $definition);
    unset($element['preserve_keys']);

    $element['closing']['#attached']['library'][] = 'slick_browser/admin';

    // Slick Browser can display a plain static grid or slick carousel.
    if (isset($element['style'])) {
      $element['style']['#description'] = $this->t('Either <strong>CSS3 Columns</strong> (experimental pure CSS Masonry) or <strong>Grid Foundation</strong> requires Grid. Difference: <strong>Columns</strong> is best with irregular image sizes. <strong>Grid</strong> with regular ones. Both do not carousel unless choosing <strong>Slick carousel</strong>. Requires the above relevant "Entity browser" plugin containing "Slick Browser" in the name, otherwise useless. Leave empty to disable Slick Browser widget.');
      $element['style']['#options']['slick'] = $this->t('Slick Carousel');

      // Single image preview should only have one option.
      if ($cardinality && $cardinality == 1) {
        $element['style']['#options'] = [];
        $element['style']['#options']['single'] = $this->t('Single Preview');
      }
    }

    // Use a specific widget group skins to avoid conflict with frontend.
    if (isset($element['skin'])) {
      $element['skin']['#options'] = $this->slickAdmin->getSkinsByGroupOptions('widget');
    }

    if (isset($element['grid'])) {
      $element['grid']['#description'] .= '<br />' . $this->t('<strong>Note</strong>: The above is only true for <strong>Widget display</strong> Slick carousel, not static grid/ columns.');
    }

    if (isset($element['sizes'])) {
      $element['sizes']['#enforced'] = TRUE;
    }

    // Removes Grid Browser which is dedicated for the browser, not widget.
    if (isset($element['optionset']) && isset($element['optionset']['#options']['grid_browser'])) {
      unset($element['optionset']['#options']['grid_browser']);
    }

    unset($element['layout'], $element['box_style']);
  }

  /**
   * Defines the scope for the form elements.
   */
  public static function scopedFormElements() {
    return [
      '_browser'             => TRUE,
      'caches'               => FALSE,
      'grid_form'            => TRUE,
      'responsive_image'     => FALSE,
      'form_opening_classes' => 'form--slick form--half form--wide has-tooltip',
    ];
  }

  /**
   * Adds theme suggestions.
   */
  public static function addThemeSuggestions(array &$suggestions, array $variables, $hook) {
    $settings = isset($variables['element']['#settings']) ? $variables['element']['#settings'] : [];

    if (!empty($settings['display']) && $settings['display'] == 'main') {
      foreach (['slick', 'slick_grid', 'slick_slide', 'slick_vanilla'] as $item) {
        if ($hook == $item) {
          // Uses the same template for slide and vanilla.
          $suggestions[] = $hook == 'slick_slide' ? 'slick_vanilla__browser' : $hook . '__browser';
        }
      }
    }
  }

  /**
   * Implements hook_entity_browser_field_widget_display_info_alter().
   *
   * @todo: Move into annotation for non-optional when it has proper uninstall.
   */
  public function entityBrowserFieldWidgetDisplayInfoAlter(array &$displays) {
    $widgets = [
      'file'            => 'File',
      'label'           => 'Label',
      'rendered_entity' => 'RenderedEntity',
    ];

    foreach ($widgets as $key => $widget) {
      $title = $key == 'rendered_entity' ? 'Rendered Entity' : $widget;
      $displays['slick_browser_' . $key] = [
        'id'          => 'slick_browser_' . $key,
        'label'       => $this->t('Slick Browser: @title', ['@title' => $title]),
        'description' => $this->t('Displays a preview of a file or entity using Blazy, if applicable.'),
        'class'       => 'Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay\SlickBrowserFieldWidgetDisplay' . $widget,
        'provider'    => 'slick_browser',
      ];
    }

    // Only supports Media Entity via VEM VEF to avoid complication by now.
    $displays['slick_browser_media'] = [
      'id'          => 'slick_browser_media',
      'label'       => $this->t('Slick Browser: Media'),
      'description' => $this->t('Displays a preview of a Media using Blazy, if applicable.'),
      'class'       => 'Drupal\slick_browser\Plugin\EntityBrowser\FieldWidgetDisplay\SlickBrowserFieldWidgetDisplayMedia',
      'provider'    => 'slick_browser',
    ];
  }

}
