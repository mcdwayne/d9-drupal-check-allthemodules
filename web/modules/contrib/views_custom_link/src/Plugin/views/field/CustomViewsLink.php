<?php

namespace Drupal\views_custom_link\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;

/**
 * Custom handler views link.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("custom_views_link")
 */
class CustomViewsLink extends LinkBase
{

  /**
   * {@inheritdoc}
   */
  public function defineOptions()
  {
    $options = parent::defineOptions();

    // Tooltip.
    $options['tooltip_placement'] = ['default' => 'top'];
    $options['tooltip_text'] = ['default' => ''];
    $options['use_text_to_display_as_tooltip'] = ['default' => TRUE];

    // Font Awesome.
    $options['fa_icon_settings'] = ['default' => ''];
    $options['fa_icon_settings']['fa_icon_class_name'] = ['default' => ''];
    $options['fa_icon_settings']['fa_icon_extra_class'] = ['default' => ''];

    // Standard icon.
    $options['icon_class_name'] = ['default' => ''];
    $options['icon_text_position'] = ['default' => ''];

    // Route options.
    $options['route_config']['default'] = NULL;
    $options['route_config']['route_machine_name'] = ['default' => NULL];
    $options['route_config']['params']['content'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state)
  {
    parent::buildOptionsForm($form, $form_state);

    // Detect tooltip provided by bootstrap theme.
    $themeHandler = \Drupal::service('theme_handler');
    $themes = $themeHandler->listInfo();
    $themesName = array_keys($themes);
    if (in_array('bootstrap', $themesName)) {
      $form['tooltip_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Tooltip Settings'),
        '#weight' => 90,
      ];

      $form['use_text_to_display_as_tooltip'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use text to display as tooltip'),
        '#weight' => 97,
        '#default_value' => $this->options['use_text_to_display_as_tooltip'],
        '#fieldset' => 'tooltip_settings',
      ];

      $form['tooltip_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tooltip Text'),
        '#weight' => 98,
        '#default_value' => $this->options['tooltip_text'],
        '#fieldset' => 'tooltip_settings',
      ];

      $form['tooltip_placement'] = [
        '#type' => 'radios',
        '#title' => $this->t('Tooltip Position'),
        '#options' => [
          'top' => 'top',
          'right' => 'right',
          'bottom' => 'bottom',
          'left' => 'left',
        ],
        '#fieldset' => 'tooltip_settings',
        '#default_value' => $this->options['tooltip_placement'],
      ];
    }

    $form['icon_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Icon Settings'),
      '#weight' => 91,
    ];
    $moduleHandler = \Drupal::service('module_handler');
    if($moduleHandler->moduleExists('fontawesome')) {
        $form['fa_icon_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('Font Awesome Settings'),
          '#weight' => 91,
          '#fieldset' => 'icon_settings',
        ];

        $form['fa_icon_settings']['fa_icon_class_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('FontAwesome Class Name'),
          '#size' => 50,
          '#field_prefix' => 'fa-',
          '#default_value' => $this->options['fa_icon_settings']['fa_icon_class_name'],
          '#description' => $this->t('Name of the Font Awesome Icon. See @iconsLink for valid icon names, or begin typing for an autocomplete list.', [
            '@iconsLink' => Link::fromTextAndUrl($this->t('the Font Awesome icon list'), Url::fromUri('https://fontawesome.com/icons'))
              ->toString(),
          ]),
          '#autocomplete_route_name' => 'fontawesome.autocomplete',
          '#element_validate' => [
            [static::class, 'validateIconName'],
          ],
        ];

        $form['fa_icon_settings']['fa_icon_extra_class'] = [
          '#type' => 'textfield',
          '#title' => $this->t('FontAwesome Extra Class'),
          '#size' => 50,
          '#field_prefix' => 'fa-',
          '#default_value' => $this->options['fa_icon_settings']['fa_icon_extra_class'],
        ];
      }


    $form['icon_class_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Class Name'),
      '#default_value' => $this->options['icon_class_name'],
      '#fieldset' => 'icon_settings',
    ];

    $form['icon_text_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Text Position'),
      '#default_value' => $this->options['icon_text_position'],
      '#fieldset' => 'icon_settings',
      '#options' => [
        '' => $this->t('None'),
        'prefix' =>  $this->t('Prefix'),
        'suffix' =>  $this->t('Suffix'),
      ],
      '#description' => $this->t("The icon text is always provided by the field 'Text to display' value. Choose the position (on the left or on the right of the icon) or choose to not display any text.")
    ];

    // Define route configuration.
    $form['route_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Route Config'),
      '#weight' => 92,
    ];
    // Define route machine name.
    $form['route_config']['route_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Route machine name'),
      '#description' => $this->t("Insert route machine name. Example: &lt;front&gt;"),
      '#default_value' => $this->options['route_config']['route_machine_name'],
      '#ajax' => [
        'callback' => 'Drupal\views_custom_link\Plugin\views\field\CustomViewsLink::routeParametersAjaxCallback',
        'progress' => [
          'type' => 'throbber',
        ],
        'wrapper' => 'route_parameters_ajax_form_wrapper',
      ],
    ];

    // Get default options.
    $default_route = $this->options['route_config']['route_machine_name'];
    if (!is_null($default_route)) {
      $this->setDefaultRouteFormField($form, $default_route);
    } else {

      // Route params fieldset.
      $form['route_config']['params'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Route params'),
        '#attributes' => [
          'id' => ['route_parameters_ajax_form_wrapper'],
        ],
      ];

      $form['route_config']['params']['content'] = [
        '#markup' => 'No route params',

      ];
    }
    $this->printHelpToken($form);

  }

  /**
   * {@inheritdoc}
   */
  public function printHelpToken(&$form)
  {

    // Setup the tokens for fields.
    $previous = $this->getPreviousFieldLabels();
    $optgroup_arguments = (string)$this->t('Arguments');
    $optgroup_fields = (string)$this->t('Fields');
    foreach ($previous as $id => $label) {
      $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($label, ":"), 2);
    }
    // Add the field to the list of options.
    $options[$optgroup_fields]["{{ {$this->options['id']} }}"] = substr(strrchr($this->adminLabel(), ":"), 2);

    foreach ($this->view->display_handler->getHandlers('argument') as $arg => $handler) {
      $options[$optgroup_arguments]["{{ arguments.$arg }}"] = $this->t('@argument title', ['@argument' => $handler->adminLabel()]);
      $options[$optgroup_arguments]["{{ raw_arguments.$arg }}"] = $this->t('@argument input', ['@argument' => $handler->adminLabel()]);
    }

    $this->documentSelfTokens($options[$optgroup_fields]);

    // Default text.S
    $output = [];
    $output[] = [
      '#markup' => '<p>' . $this->t("You must add some additional fields to this display before using this field. These fields may be marked as <em>Exclude from display</em> if you prefer. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.") . '</p>',
    ];
    // We have some options, so make a list.
    if (!empty($options)) {
      $output[] = [
        '#markup' => '<p>' . $this->t("The following replacement tokens are available for this field. Note that due to rendering order, you cannot use fields that come after this field; if you need a field not listed here, rearrange your fields.") . '</p>',
      ];
      foreach (array_keys($options) as $type) {
        if (!empty($options[$type])) {
          $items = [];
          foreach ($options[$type] as $key => $value) {
            $items[] = $key . ' == ' . $value;
          }
          $item_list = [
            '#theme' => 'item_list',
            '#items' => $items,
          ];
          $output[] = $item_list;
        }
      }
    }
    // This construct uses 'hidden' and not markup because process doesn't
    // run. It also has an extra div because the dependency wants to hide
    // the parent in situations like this, so we need a second div to
    // make this work.
    $form['route_config']['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Replacement patterns'),
      '#value' => $output,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultRouteFormField(&$form, $default_route)
  {
    $route_provider = \Drupal::service('router.route_provider');

    try {
      $route = $route_provider->getRouteByName($default_route);
    } catch (\Exception $exception) {
      // No route found. Return message error.
      $form['route_config']['params'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Route params'),
        '#attributes' => [
          'id' => ['route_parameters_ajax_form_wrapper'],
        ],
      ];
      $form['route_config']['params']['content'] = [
        '#markup' => 'No route params',
      ];
      return;
    }

    // Get all parameters of route.
    $parameters = $route->getOptions('parameters')['parameters'];

    if (isset($parameters) && !empty($parameters)) {
      $form['route_config']['params'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Route params'),
        '#attributes' => [
          'id' => ['route_parameters_ajax_form_wrapper'],
        ],
      ];

      // Print textfield for each route params.
      foreach ($parameters as $param_name => $param) {
        $form['route_config']['params']['content'][$param_name] = [
          '#type' => 'textfield',
          '#title' => $this->t("Insert param @param_name", ['@param_name' => $param_name]),
          '#description_display' => 'after',
          '#description' => $this->t("Type: @param_type", ['@param_type' => $param['type']]),
          '#default_value' => $this->options['route_config']['params']['content'][$param_name],
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function routeParametersAjaxCallback(&$form, FormStateInterface $form_state): array {

    // Define empty element.
    $elem = [];

    $translation = \Drupal::translation();

    // Get route name inserted.
    $route_name = $form_state->getValue('options')['route_config']['route_machine_name'];

    // Demo route $route_name = 'p_newborn.history'.
    $route_provider = \Drupal::service('router.route_provider');

    try {
      $route = $route_provider->getRouteByName($route_name);
    } catch (\Exception $exception) {
      // No route found. Return message error.
      $elem['route_config']['params'] = [
        '#type' => 'fieldset',
        '#title' => $translation->translate('Route params'),
        '#attributes' => [
          'id' => ['route_parameters_ajax_form_wrapper'],
        ],
      ];
      $elem['route_config']['params']['content'] = [
        '#markup' => 'No route params',
      ];
      return $elem;
    }

    // Get all parameters of route.
    $parameters = $route->getOptions('parameters')['parameters'];

    if (isset($parameters) && !empty($parameters)) {
      $elem['route_config']['params'] = [
        '#type' => 'fieldset',
        '#title' => $translation->translate('Route params'),
        '#attributes' => [
          'id' => ['route_parameters_ajax_form_wrapper'],
        ],
      ];

      // Print textfield for each route params.
      foreach ($parameters as $param_name => $param) {
        $elem['route_config']['params']['content'][$param_name] = [
          '#type' => 'textfield',
          '#title' => $translation->translate("Insert param @param_name", ['@param_name' => $param_name]),
          '#description_display' => 'after',
          '#description' => $translation->translate("Type: @param_type", ['@param_type' => $param['type']]),
        ];
      }
    }

    return $elem;
  }

  /**
   * Render markup link.
   *
   * @param Drupal\views\ResultRow $row
   *   Return rown of item with full data.
   *
   * @return string
   *   Return markup of link.
   */
  protected function renderLink(ResultRow $row)
  {
    parent::renderLink($row);

    // Define renderable array.
    $renderable = [
      '#theme' => 'views_custom_link',
    ];

    // Define tooltip.
    $renderable['#text'] = $this->options['text'];
    if ($this->options['use_text_to_display_as_tooltip']) {
      $renderable['#tooltip']['#attributes']['title'] = $this->options['text'];
      $renderable['#text'] = '';
    } else {
      $renderable['#tooltip']['#attributes']['title'] = $this->options['tooltip_text'];
    }

    if ($this->options['tooltip_placement'] !== '') {
      $renderable['#tooltip']['#attributes']['placement'] = $this->options['tooltip_placement'];
    }

    // Define icon.
    $icon_class = '';
    if ((!empty($this->options['fa_icon_settings']['fa_icon_class_name'])) || (!empty($this->options['fa_icon_settings']['fa_icon_extra_class']))) {

      $icon_class = 'fa ';
      $icon_class .= (!empty($this->options['fa_icon_settings']['fa_icon_class_name'])) ? 'fa-'.$this->options['fa_icon_settings']['fa_icon_class_name'] : '';
      $icon_class .= (!empty($this->options['fa_icon_settings']['fa_icon_extra_name'])) ? ' fa-'.$this->options['fa_icon_settings']['fa_icon_extra_name'] : '';

    }
    else {
      $icon_class = $this->options['icon_class_name'];
    }

    $renderable['#icon']['#attributes']['class'] = $icon_class;

    switch ($this->options['icon_text_position']) {

      case 'prefix':
        // Define icon prefix.
        $renderable['#icon']['#attributes']['prefix'] = $this->options['text'];
        break;

      case 'suffix':
        // Define icon suffix.
        $renderable['#icon']['#attributes']['suffix'] = $this->options['text'];

        break;

      default:
    }

    $rendered = \Drupal::service('renderer')->render($renderable);

    return $rendered;
  }

  /**
   * Get url info.
   *
   * @param Drupal\views\ResultRow $row
   *   Return row of item with full data.
   *
   * @return object
   *   Return full url.
   */
  protected function getUrlInfo(ResultRow $row)
  {
    $parameters = [];

    // Foreach params, get relative value with replacing token.
    foreach ($this->options['route_config']['params']['content'] as $paramName => $paramValue) {
      $parameters[$paramName] = $this->tokenizeValue($paramValue);
    }

    // Return the link.
    return Url::fromRoute($this->options['route_config']['route_machine_name'], $parameters);
  }

}
