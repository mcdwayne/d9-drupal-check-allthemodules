<?php

namespace Drupal\stacks\WidgetAdmin\Step;

use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\stacks\Entity\WidgetEntityType;
use Drupal\stacks\Entity\WidgetInstanceEntity;
use Drupal\stacks\Widget\WidgetTemplates;
use Drupal\stacks\WidgetAdmin\Button\StepOneNextButton;
use Drupal\stacks\WidgetAdmin\Button\StepExistingFinishButton;
use Drupal\stacks\WidgetAdmin\Validator\ValidatorRequired;
use Drupal\stacks\WidgetAdmin\Validator\ValidatorCustom;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class StepOne.
 * @package Drupal\stacks\WidgetAdmin\Step
 */
class StepOne extends BaseStep {

  /**
   * @inheritDoc.
   */
  public function setStep() {
    return StepsEnum::STEP_ONE;
  }

  /**
   * @inheritDoc.
   */
  public function getButtons() {
    return [
      new StepOneNextButton(),
      new StepExistingFinishButton(),
    ];
  }

  /**
   * @inheritDoc.
   */
  public function buildStepFormElements() {
    // Define whether we are adding or updating.
    $add_entity = TRUE;
    if (isset($_GET['widget_instance_id'])) {
      $add_entity = FALSE;
    }

    $form = [];

    $form['delta'] = ['#type' => 'hidden', '#value' => (int) $_GET['delta']];
    $form['widget_instance_id'] = [
      '#type' => 'hidden',
      '#value' => (isset($_GET['widget_instance_id']) ? (int) $_GET['widget_instance_id'] : 0)
    ];

    if (!$add_entity) {
      $form['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => t('Edit Widget'),
        '#attributes' => [
          'class' => [
            'widget-title'
          ],
        ],
      ];
    }
    else {
      $form['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => t('Add a New Widget'),
        '#attributes' => [
          'class' => [
            'widget-title'
          ],
        ],
      ];
      $form['begin_wrapper'] = ['#markup' => '<div id="tabs-widget-wrapper" class="widget-wrap">'];
      $form['tabs'] = [
        '#theme' => 'item_list',
        '#items' => [
          Link::fromTextAndUrl(t('New Widget'), Url::fromUri('internal:', ['fragment' => 'tabs-new-widget'])),
          Link::fromTextAndUrl(t('Existing Widget'), Url::fromUri('internal:', ['fragment' => 'tabs-existing-widget'])),
        ],
      ];
    }

    $form['tab_new_widget'] = ['#markup' => '<div id="tabs-new-widget" class="widget-wrap">'];

    // Send the default values as a setting to the JS.
    $form['#attached']['drupalSettings']['stacks']['form']['default_widget_type'] = isset($this->getValues()['widget_type']) ? $this->getValues()['widget_type'] : '';
    $form['#attached']['drupalSettings']['stacks']['form']['default_widget_template'] = isset($this->getValues()['widget_template']) ? $this->getValues()['widget_template'] : '';
    $form['#attached']['drupalSettings']['stacks']['form']['default_widget_theme'] = isset($this->getValues()['widget_theme']) ? $this->getValues()['widget_theme'] : '';

    $form['widget_type'] = [
      '#type' => 'select',
      '#title' => t('Widget Type'),
      '#options' => $this->selectWidgetType(),
      '#attributes' => ['id' => 'widget_type_select'],
      '#default_value' => isset($this->getValues()['widget_type']) ? $this->getValues()['widget_type'] : '',
      '#required' => FALSE,
      '#disabled' => isset($this->getValues()['widget_type']) ? TRUE : FALSE,
    ];

//    // Providing an AJAX callback when Automatic Titles is enabled. This function resolves the name for the widget.
//    // Sub 37 Image 1
//    if ($automatic_titles) {
//      $form['widget_type']['#ajax'] = [
//        'callback' => [$this, 'widgetNameChecker'],
//        'event' => 'change',
//        'progress' => [
//          'type' => 'throbber',
//          'message' => t('Filtering') . "...",
//        ],
//      ];
//
//
//      $test = 0;
//    }

    // Add the template message div.
    $form['template-message'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'template-message',
        'class' => [
          'clearfix'
        ],
      ],
      '#value' => '',
    ];

    // Add the template radio options.
    $form['template_radios'] = $this->radiosTemplate();

    $form['div_template'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'clearfix'
        ],
      ],
    ];

    // Add the theme radio options.
    $form['theme_radios'] = $this->radiosTheme();

    $form['div_theme'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'clearfix'
        ],
      ],
    ];

    $widget_instance_entity = null;
    $reusable_disabled = FALSE;
    if (!empty($_GET['widget_instance_id'])) {
      $widget_instance_entity = WidgetInstanceEntity::load($_GET['widget_instance_id']);
      if ($widget_instance_entity->getTimesUsed($_GET['entity-id'])) {
        $reusable_disabled = TRUE;
      }
    }

    // Reusable widget checkbox
    $form['reusable'] = [
      '#type' => 'checkbox',
      '#title' => t('Reuse this widget'),
      '#default_value' => isset($this->getValues()['reusable']) ? $this->getValues()['reusable'] : false,
      '#disabled' => $reusable_disabled,
      '#description' => $reusable_disabled ? t('You cannot edit this because it\'s currently being used on another content.') : '',
    ];

    $form['widget_name'] = [
      '#type' => 'textfield',
      '#title' => t('Widget Name'),
      '#placeholder' => t('Widget Name'),
      '#default_value' => isset($this->getValues()['widget_name']) ? $this->getValues()['widget_name'] : '',
      '#required' => FALSE,
    ];

    // Disabling and hiding Widget name field.
    $form['widget_name']['#states'] = [
      'visible' => [
        ':input[name="reusable"]' => array('checked' => TRUE),
      ],
      'required' => [
        ':input[name="reusable"]' => array('checked' => TRUE),
      ],
      'empty' => [
        ':input[name="reusable"]' => array('checked' => TRUE),
      ],
    ];

    $form['tab_new_widget_end'] = ['#markup' => '</div>'];

    // Existing Stacks.
    if ($add_entity) {
      $form['tab_existing_widget'] = ['#markup' => '<div id="tabs-existing-widget">'];
      $form['existing_stacks'] = $this->existingStacks();
      $form['tab_existing_widget_end'] = ['#markup' => '</div>'];
    }

    $form['end_wrapper'] = ['#markup' => '</div>'];

    return $form;
  }

  /**
   * @inheritDoc.
   */
  public function getFieldNames() {
    return [
      'delta',
      'widget_instance_id',
      'widget_type',
      'widget_name',
      'widget_template',
      'widget_theme',
      'status',
      'bundle',
      'template',
      'has_templates',
      'has_themes',
      'reusable'
    ];
  }

  /**
   * @inheritDoc.
   */
  public function getFieldsValidators() {   
    return [
      'widget_type' => [
        new ValidatorRequired(t("Widget Type field is required.")),
      ],
      'widget_name' => [
        new ValidatorCustom(t("Widget Name field is required."), function ($form_state, $field_value) {
          // We only want to throw an error when the following is true:
          // - widget_name is empty.
          // - reusable is checked.

          // This only requires the widget label field when the reusable option
          // is checked.
          if (empty($field_value) && $form_state->getValue('reusable')) {
            return FALSE;
          }

          return TRUE;

        }),
      ],
      'widget_template' => [
        new ValidatorCustom(t("Widget Template field is required."), function ($form_state, $field_value) {
          // We only want to throw an error when the following is true:
          // - widget_template is empty.
          // - widget_type is not empty.

          // This prevents multiple errors from showing up when the template
          // field is not visible.
          if (empty($field_value) && !empty($form_state->getValue('widget_type'))) {
            return FALSE;
          }

          return TRUE;

        }),
      ],
    ];
  }

  /**
   * Load widget instance entity and stacks entity and then prepopulate the form.
   *
   * @param $entities - The widget instance and widget entities.
   */
  public function editWidgetInstance($entities) {
    $widget_instance = $entities['widget_instance_entity'];
    $widget_entity = $entities['widget_entity'];
    $bundle = $widget_entity->bundle();

    // Define the options we are loading. This needs to be all the available
    // options in the first step of the form.
    $default_values = [
      'delta' => $_GET['delta'],
      'widget_instance_id' => $widget_instance->id(),
      'widget_type' => $widget_entity->getWidgetType(),
      'widget_name' => $widget_instance->getTitle(),
      'widget_template' => $bundle . '--' . $widget_instance->getTemplate(),
      'widget_theme' => $widget_instance->getTheme(),
      'status' => $widget_instance->getStatus(),

      // These values are used to access this info throughout the process.
      'bundle' => $bundle,
      'template' => $widget_instance->getTemplate(),
      'has_themes' => $this->selectTheme($bundle),

      // Reusable/Shared widget
      'reusable' => $widget_instance->isShareable(),
    ];

    // Now save the values for this step. This should pre-populate the form.
    $this->setValues($default_values);
    // has_templates depends on some of the values set above when editing a
    // widget instance
    $default_values += [
      'has_templates' => $this->radiosTemplate($bundle),
    ];
    $this->setValues($default_values);
  }

  /**
   * Return options for the Widget Type dropdown.
   *
   * @return array
   */
  static public function selectWidgetType() {
    // Make sure there is at least one bundle option for this content type.
    $bundles = EntityFormDisplay::load($_GET['entity-type'] . '.' . $_GET['content-type'] . '.default')
      ->getComponent($_GET['field'])['settings'];
    $bundles = (isset($bundles['bundles']) ? $bundles['bundles'] : []);

    // Getting widget groups to wrap template variants.
    $config = \Drupal::service('config.factory')->getEditable('stacks.settings');
    $widget_type_groups = $config->get("widget_type_groups");

    if (count($bundles) < 1) {
      drupal_set_message(t('No widgets available for this content type.'), 'error');
    }

    $widget_type_options = ['' => t('-- Choose a Widget Type --')];

    $widget_type_manager = \Drupal::service('plugin.manager.stacks_widget_type');

    foreach ($bundles as $bundle) {
      if (empty($bundle)) {
        continue;
      }
      $widget_entity_type = WidgetEntityType::load($bundle);
      if (isset($widget_entity_type) && $widget_type_manager->hasDefinition($widget_entity_type->getPlugin())) {
        $id = '';
        $label = '';

        // Checking if the bundle is standalone or grouped with other widget types.
        if (count($widget_type_groups) > 0) {
          foreach ($widget_type_groups as $group_id => $group_label) {
            if(substr($bundle, 0, strlen($group_id)) == $group_id) {
              $id = $group_id;
              $label = $group_label;
            }
          }
        }

        if (empty($id) && empty($label)) {
          $id = $widget_entity_type->id();
          $label = $widget_entity_type->label();
        }

        $widget_type_options[$id] = $label;

      }
    }

    asort($widget_type_options);
    return $widget_type_options;
  }

  /**
   * Return options for the Template radio buttons.
   *
   * @param $templates_by_widget_type string/bool Widget type or FALSE
   *
   * @return bool/array If the widget type is present return whether there are
   * variations for that widget type. If false, return an array of template
   * variations
   */
  public function radiosTemplate($templates_by_widget_type = FALSE) {
    $templates = WidgetTemplates::getTemplatesSelect();
    $bundles_get = \Drupal::entityManager()->getBundleInfo('widget_entity');

    $config = \Drupal::service('config.factory')->getEditable('stacks.settings');
    $widget_type_groups = $config->get("widget_type_groups");

    // Add hidden divs for template options for all widgets.
    $template_options = [];
    $base_dir_stacks = WidgetTemplates::templateDir();
    foreach ($templates as $bundle => $bundle_templates) {

      $widget_group = $bundle;

      // Check if this belongs to a group
      $is_group = FALSE;
      if(is_array($widget_type_groups) && count($widget_type_groups)) {
        foreach($widget_type_groups as $group => $value) {
          if(substr($bundle, 0, strlen($group)) == $group) {
            $widget_group = $group;
            $is_group = TRUE;
            break;
          }
        }
      }

      // If we are targeting a certain widget type, continue if this is not that
      // type.
      if ($templates_by_widget_type && $widget_group != $templates_by_widget_type) {
        continue;
      }

      foreach ($bundle_templates as $bundle_template => $bundle_template_display) {

        if ($is_group) {
          // This is a widget group. We need to make sure to display the bundle
          // label for these cases.
          if ($bundle_template_display != 'Default') {
            $bundle_template_display = $bundles_get[$bundle]['label'] . ' - ' . $bundle_template_display;
          }
          else {
            $bundle_template_display = $bundles_get[$bundle]['label'];
          }

          // If we are editing an instance of a grouped widget type, discard
          // other widget templates except those variations of the same widget
          // type
          $widget_instance_bundle = $this->getValues()['bundle'];
          if (!empty($widget_instance_bundle)) {
            if (!preg_match('/^' . preg_quote($widget_instance_bundle) . '/', $bundle)) {
              continue;
            }
          }
        }

        // Define the preview image.
        $renamed_dir = str_replace(['_', ' '], '-', $bundle);
        $renamed_template = str_replace(['_', ' '], '-', $bundle_template);
        $preview_image_file = DRUPAL_ROOT . '/' . $base_dir_stacks . '/' . $renamed_dir . '/images/' . $renamed_template;

        $preview_images = array_merge(glob($preview_image_file . '.{jpg,jpeg,png,gif}', GLOB_BRACE), glob($preview_image_file . '.{JPG,JPEG,PNG,GIF}', GLOB_BRACE));

        if (!empty($preview_images)) {
          $preview_image_file = substr($preview_images[0], strlen(DRUPAL_ROOT));
          $preview_image_array = [
            '#type' => 'container',
            'img' => [
              '#type' => 'html_tag',
              '#tag' => 'img',
              '#attributes' => [
                'width' => 241,
                'src' => $preview_image_file,
              ],
            ],
          ];
        }
        else {
          $preview_image_array = [
            '#type' => 'container',
            'img' => [
              '#type' => 'html_tag',
              '#tag' => 'img',
              '#attributes' => [
                'width' => 241,
                'height' => 234,
                'title' => '',
                'alt' => '',
                'src' => '/' . drupal_get_path('module', 'stacks') . '/images/no-preview-img.png',
              ],
            ],
          ];
        }

        $preview_image = \Drupal::service('renderer')->render($preview_image_array);

        $machine_name = $bundle . '--' . $bundle_template;

        // If the widget type belongs to a group, then the radio element for this option will have the group attribute.
        if ($is_group) {
          $bundle = $widget_group;
        }

        // Create the radio button.
        $template_options[$machine_name]['widget_template'] = [
          '#type' => 'radio',
          '#title' => $bundle_template_display,
          '#return_value' => $machine_name,
          '#default_value' => '',
          '#field_prefix' => $preview_image,
          '#prefix' => '<div id="template-' . $machine_name . '" class="widget_template_radio" group="' . $bundle . '">',
          '#suffix' => '</div>',
        ];

      }
    }

    // If $templates_by_widget_type is true, return TRUE/FALSE if there are
    // templates for this widget type.
    if ($templates_by_widget_type) {
      return count($template_options) > 1;
    }

    return $template_options;
  }

  /**
   * @return array
   */
  public function radiosTheme() {
    $config = \Drupal::service('config.factory')
      ->getEditable('stacks.settings');
    $themes = $config->get("template_themes_config");

    if (!is_array($themes)) {
      return [];
    }

    // Add hidden divs for template options for all widgets.
    $template_options = [];
    $base_dir_stacks = WidgetTemplates::templateDir();
    foreach ($themes as $bundle => $bundle_themes) {

      foreach ($bundle_themes as $bundle_template => $bundle_theme_options) {

        foreach ($bundle_theme_options as $option_key => $option_value) {

          // Define the preview image.
          $renamed_dir = str_replace(['_', ' '], '-', $bundle);
          $renamed_template = str_replace(['_', ' '], '-', $bundle_template);
          $renamed_option = str_replace(['_', ' '], '-', $option_key);
          $preview_image_file = DRUPAL_ROOT . '/' . $base_dir_stacks . '/' . $renamed_dir . '/images/' . $renamed_template . '--' . $renamed_option;

          $theme_images = array_merge(glob($preview_image_file . '.{jpg,jpeg,png,gif}', GLOB_BRACE), glob($preview_image_file . '.{JPG,JPEG,PNG,GIF}', GLOB_BRACE));

          if (!empty($theme_images)) {
            $preview_image_file = substr($theme_images[0], strlen(DRUPAL_ROOT));
            $preview_image_array = [
              '#type' => 'container',
              'img' => [
                '#type' => 'html_tag',
                '#tag' => 'img',
                '#attributes' => [
                  'width' => 100,
                  'title' => $option_value,
                  'alt' => $option_value,
                  'src' => $preview_image_file,
                ],
              ],
            ];
          }
          else {
            $preview_image_array = [
              '#type' => 'container',
              'img' => [
                '#type' => 'html_tag',
                '#tag' => 'img',
                '#attributes' => [
                  'width' => 100,
                  'title' => $option_value,
                  'alt' => $option_value,
                  'src' => '/' . drupal_get_path('module', 'stacks') . '/images/no-preview-img.png',
                ],
              ],
            ];
          }

          $preview_image = \Drupal::service('renderer')->render($preview_image_array);

          $machine_name = $bundle . '--' . $bundle_template . '--' . $option_key;

          // Create the radio button.
          $template_options[$machine_name]['widget_theme'] = [
            '#type' => 'radio',
            '#title' => $option_value,
            '#return_value' => $option_key,
            '#default_value' => '',
            '#field_prefix' => $preview_image,
            '#prefix' => '<div id="theme-' . $machine_name . '" class="widget_theme_radio" bundle-template="' . $bundle . '--' . $bundle_template . '">',
            '#suffix' => '</div>',
          ];

        }
      }
    }

    return $template_options;

  }

  /**
   * Return options for the Theme select.
   *
   * @param $templates_by_bundle string/bool Widget type or FALSE
   *
   * @return bool/array If the widget type is present return whether there are
   * theme options for that widget type. If false, return an array theme options
   */
  public function selectTheme($templates_by_bundle = FALSE) {
    $config = \Drupal::service('config.factory')
      ->getEditable('stacks.settings');
    $themes = $config->get("template_themes_config");

    $theme_options = [];
    if (is_array($themes)) {
      foreach ($themes as $bundle => $bundle_templates) {

        // If $templates_by_bundle is set, we only want theme options for this
        // bundle.
        if ($templates_by_bundle && $bundle != $templates_by_bundle) {
          continue;
        }

        foreach ($bundle_templates as $template_themes) {
          foreach ($template_themes as $theme => $theme_display) {
            $theme_options[$theme] = $theme_display;
          }
        }
      }
    }

    // If $templates_by_bundle is true, return TRUE/FALSE if there are templates
    // for this widget type.
    if ($templates_by_bundle) {
      return count($theme_options) > 0;
    }

    return $theme_options;
  }

  /**
   *
   * Return a render array.
   */
  public function existingStacks() {
    // Create search filters.
    $form['filter_title_search'] = [
      '#placeholder' => t('Search widgets'),
      '#type' => 'textfield',
      '#default_value' => isset($this->getValues()['filter_title_search']) ? $this->getValues()['filter_title_search'] : '',
      '#attributes' => [
        'placeholder' => t('Search widgets'),
        'class' => ['title-search'],
      ],
      '#ajax' => [
        'callback' => [$this, 'filterExistingWidgets'],
        'event' => 'debounce_filter',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Filtering') . "...",
        ],
      ],
    ];

    // Setting Type array.
    $available_types = [];
    $available_types[""] = t("All Types");

    $widget_types = \Drupal::entityManager()->getBundleInfo('widget_entity');

    foreach ($widget_types as $key => $value) {
      $index = trim($key);
      if (!empty($index)) {
        $available_types[$key] = $value['label'];
      }
    }

    $form['filter_widget_type'] = [
      '#type' => 'select',
      '#options' => $available_types,
      '#default_value' => isset($this->getValues()['widget_type']) ? $this->getValues()['widget_type'] : '',
      '#ajax' => [
        'callback' => [$this, 'filterExistingWidgets'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Filtering') . "...",
        ],
      ],
    ];

    $form['filter_additional'] = [
      '#type' => 'select',
      '#options' => [
        'widget_times_used' => t('Most Used'),
        'title' => t('Title')
      ],
      '#default_value' => '',
      '#ajax' => [
        'callback' => [$this, 'filterExistingWidgets'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Filtering') . "...",
        ],
      ],
    ];

    $form['filter_results_per_page'] = [
      '#type' => 'select',
      '#options' => [
//        10 => 'Results per page',
        10 => '10',
        25 => '25',
        50 => '50',
      ],
      '#default_value' => '',
      '#ajax' => [
        'callback' => [$this, 'filterExistingWidgets'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Filtering') . "...",
        ],
      ],
    ];

    $form['table_pager'] = [
      '#weight' => 1,
      '#type' => 'textfield',
      '#attributes' => ['class' => ['table_pager_element']],
      '#default_value' => isset($this->getValues()['table_pager']) ? $this->getValues()['table_pager'] : '0',
      '#ajax' => [
        'callback' => [$this, 'filterExistingWidgets'],
        'event' => 'change',
      ],
    ];

    // Because the existing stacks table is being replaces via ajax alone and
    // not through in a proper FAPI way, this hacks prevents an "An illegal
    // choice has been detected. Please contact the site administrator." error
    // on the radios. We are querying $_POST details directly so this should
    // probably be address later on.
    $existing_stacks_table_data = [
      'filter_title_search',
      'filter_widget_type',
      'filter_additional',
      'table_pager',
      'filter_results_per_page',
    ];
    $existing_stacks_table_args = [
      '',
      '',
      'widget_times_used',
      0,
      10
    ];
    foreach ($existing_stacks_table_data as $k => $el) {
      $existing_stacks_table_args[$k] = \Drupal::request()->request->get(
        $el,
        $existing_stacks_table_args[$k]
      );
    }
    $form['existing_stacks_table'] = call_user_func_array(
      '_stacks_get_existing_stacks_table',
      $existing_stacks_table_args
    );

    return $form;
  }

  /**
   * @inheritDoc.
   */
  public function filterExistingWidgets($form, &$form_state) {
    // Getting current values.
    $search = trim($form_state->getValue('filter_title_search'));
    $type = $form_state->getValue('filter_widget_type');
    $sort = $form_state->getValue('filter_additional');
    $pager = $form_state->getValue('table_pager');
    $limit = $form_state->getValue('filter_results_per_page');
    $page_number = 0;

    // Getting pager
    if (!empty($pager) && is_numeric($pager) && $pager >= 0) {
      $page_number = $pager;
    }

    $newtable = _stacks_get_existing_stacks_table($search, $type, $sort, $page_number, $limit, TRUE);

    $form = [];

    $form['existing_stacks_table'] = $newtable;

    Tableselect::processTableselect($form['existing_stacks_table'], $form_state, $form);

    // Adjust some attributes. #name is the important one
    foreach ($form['existing_stacks_table'] as $key => &$item) {
      if (is_numeric($key) && !empty($item['#type']) && $item['#type'] == 'radio') {
        $item['#attributes']['data-drupal-selector'] = 'edit-existing-widgets-table-' . $key . '-x';
        $item['#name'] = 'existing_stacks_table';
      }
    }

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(".existing_stacks_dashboard", $form));

    return $response;
  }
}
