<?php

/**
 * @file
 * Contains methods to add visualn common settings to visualn fields formatters settings forms.
 */

namespace Drupal\visualn\Plugin;

use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\core\form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides common elements for VisualN fields formatters
 * settings forms.
 */
trait VisualNFormatterSettingsTrait {

  /**
   * defaultSettings()
   */
  public static function visualnDefaultSettings() {
    return array(
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
    ) + parent::defaultSettings();
  }

  /**
   * settingsForm()
   */
  public function visualnSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // @todo: use $element instead of $form
    $visualn_styles = visualn_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );

    // prepare data for #process callback
    $initial_config_item = new \StdClass();
    $initial_config_item->visualn_style_id = $this->getSetting('visualn_style_id');
    $serialize_data = [
      'drawer_config' => $this->getSetting('drawer_config'),
      'drawer_fields' => $this->getSetting('drawer_fields'),
    ];
    // @todo: maybe using just an array instead of object would be a better option to avoid taking it for something
    //    different than a standard object for data storage
    // @todo: no need to serialize settings here, see VisualNImageFormatter for example
    $initial_config_item->visualn_data = serialize($serialize_data);


    $form['visualn_style_id'] = [
      '#title' => t('VisualN style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('visualn_style_id'),
      '#empty_option' => t('None (raw data)'),
      '#options' => $visualn_styles,
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer image styles')
        '#access' => TRUE
      ],
      // https://www.drupal.org/docs/8/creating-custom-modules/create-a-custom-field-formatter
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'formatter-drawer-config-form-ajax',  // @todo: use a more explicit wrapper
      ],
      '#required' => TRUE,
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="formatter-drawer-config-form-ajax">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];
    // @todo: $item is needed in the #process callback to access drawer_config from field configuration,
    //    maybe there is a better way
    $form['drawer_container']['#item'] = $initial_config_item;

    return $form;
  }

  // @todo: may be this should be static
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $item = $element['#item'];
    $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
    $visualn_data['resource_format'] = !empty($visualn_data['resource_format']) ? $visualn_data['resource_format'] : '';
    $visualn_data['drawer_config'] = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
    $visualn_data['drawer_fields'] = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];

    $configuration = $visualn_data;
    $configuration['visualn_style_id'] = $item->visualn_style_id ?: '';
    // @todo: add visualn_style_id = "" to widget default config (check) to avoid "?:" check

    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    return $element;
  }


  /**
   * {@inheritdoc}
   *
   * @todo: Add into an interface or add description
   *
   * return drawerConfigForm via ajax at style change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }


  /**
   * settingsSummary()
   */
  public function visualnSettingsSummary() {
    $summary = array();

    $visualn_styles = visualn_style_options(FALSE);
    // Unset possible 'No defined styles' option.
    unset($visualn_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    $visualn_style_setting = $this->getSetting('visualn_style_id');
    if (isset($visualn_styles[$visualn_style_setting])) {
      $summary[] = t('VisualN style: @style', array('@style' => $visualn_styles[$visualn_style_setting]));
    }
    else {
      $summary[] = t('Raw data');
    }

    /*
    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );
    // Display this setting only if image is linked.
    $image_link_setting = $this->getSetting('image_link');
    if (isset($link_types[$image_link_setting])) {
      $summary[] = $link_types[$image_link_setting];
    }
*/

    return $summary;
  }

  /**
   * viewElements()
   */
  public function visualnViewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);

    // @todo: since this can be cached it could not take style changes (i.e. made in style
    //   configuration interface) into consideration, so a cache tag may be needed.


    // @todo: if visualn style not selected (e.g. user didn't select or not allowed to)
    //   use style from formatter settings

    // get default visualn style from formatter settings
    $default_visualn_style_id = $this->getSetting('visualn_style_id');
    if (!empty($default_visualn_style_id)) {
      $visualn_style = $this->visualNStyleStorage->load($default_visualn_style_id);
      $drawer_plugin = $visualn_style->getDrawerPlugin();
      // @todo: why not get configuration for getSetting() without loading style?
      //   useful only if there is an option to not override config in formatter settings
      $default_drawer_config = $this->getSetting('drawer_config');
      //$default_drawer_config = $drawer_plugin->getConfiguration() + $this->getSetting('drawer_config');
      $default_drawer_fields = $this->getSetting('drawer_fields');
    }

    // create drawing for each delta
    foreach ($elements as $delta => $element) {
      $item = $items[$delta];
      $visualn_data = !empty($item->visualn_data) ? unserialize($item->visualn_data) : [];
      $raw_resource_format_id = $visualn_data['resource_format'];
      if (empty($raw_resource_format_id)) {
        continue;
      }

      if ($items[$delta]->visualn_style_id) {
        $visualn_style_id = $items[$delta]->visualn_style_id;
        $drawer_config = !empty($visualn_data['drawer_config']) ? $visualn_data['drawer_config'] : [];
        $drawer_fields = !empty($visualn_data['drawer_fields']) ? $visualn_data['drawer_fields'] : [];
      }
      elseif (!empty($default_visualn_style_id)) {
        $visualn_style_id = $default_visualn_style_id;
        $drawer_config = $default_drawer_config;
        // @todo: user could still override drawer fields even if visualn style override is not allowed
        $drawer_fields = $default_drawer_fields;
      }
      else {
        $visualn_style_id = '';
        $drawer_config = [];
        $drawer_fields = [];
      }

      // Get drawing build
      if ($visualn_style_id) {
        $raw_input = $this->getRawInput($element, $item);
        // @todo: config may be required for some formats, though then a subform should be shown
        //   and configuration saved in visualn_data (e.g. quotes type for csv files)
        $raw_resource_format_plugin
          = $this->visualNResourceFormatManager->createInstance($raw_resource_format_id, []);
        $resource = $raw_resource_format_plugin->buildResource($raw_input);
        // @todo: get the service into trait implementing classes
        $build = \Drupal::service('visualn.builder')->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields);
      }
      else {
        $build = [];
      }

      // Drawing build can't just be attached to #suffix as rendered markup
      // i.e. using \Drupal::service('renderer')->render($build) since it may do
      // unwanted preprocessing, i.e. vue.js tag attributes
      // such as src=":some_var" will be converted to src="some_var".

      // @todo: maybe use a stand-alone template instead of inline template
      // @todo: check for possible security issues of this approach
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => "{{element_build}} {{drawing_build}}",
        '#context' => [
          'element_build' => $elements[$delta],
          'drawing_build' => $build,
        ],
      ];
    }

    return $elements;
  }

}
