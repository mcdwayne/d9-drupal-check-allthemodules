<?php

namespace Drupal\elevatezoomplus_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\slick_ui\Form\SlickFormBase;
use Drupal\elevatezoomplus\Entity\ElevateZoomPlus;

/**
 * Extends base form for elevatezoomplus instance configuration form.
 */
class ElevateZoomPlusForm extends SlickFormBase {

  /**
   * A state that represents the zoom type is inner.
   */
  const ZOOM_TYPE_INNER = 1;

  /**
   * A state that represents the zoom type is lens.
   */
  const ZOOM_TYPE_LENS = 2;

  /**
   * A state that represents the zoom type is window.
   */
  const ZOOM_TYPE_WINDOW = 3;

  /**
   * A state that represents the lens is shown.
   */
  const SHOW_LENS = 4;

  /**
   * Defines the nice anme.
   *
   * @var string
   */
  protected static $niceName = 'ElevateZoomPlus';

  /**
   * Defines machine name.
   *
   * @var string
   */
  protected static $machineName = 'elevatezoomplus';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $optionset = $this->entity;
    $tooltip   = ['class' => ['is-tooltip']];
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');

    $form['#attributes']['class'][] = 'form--elevatezoomplus';
    if ($admin_css) {
      $form['#attached']['library'][] = 'elevatezoomplus/admin';
    }

    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#default_value' => $optionset->label(),
      '#maxlength'     => 255,
      '#required'      => TRUE,
      '#description'   => $this->t("Label for the ElevateZoomPlus optionset."),
      '#attributes'    => $tooltip,
    ];

    // Keep the legacy CTools ID, i.e.: name as ID.
    $form['name'] = [
      '#type'          => 'machine_name',
      '#default_value' => $optionset->id(),
      '#maxlength'     => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name'  => [
        'source' => ['label'],
        'exists' => '\Drupal\elevatezoomplus\Entity\ElevateZoomPlus::load',
      ],
      '#attributes'    => $tooltip,
      '#disabled'      => !$optionset->isNew(),
    ];

    // Main JS options.
    $form['options'] = [
      '#type'    => 'vertical_tabs',
      '#tree'    => TRUE,
      '#parents' => ['options'],
    ];

    // Main JS options.
    $form['settings'] = [
      '#type'       => 'details',
      '#tree'       => TRUE,
      '#open'       => TRUE,
      '#title'      => $this->t('Settings'),
      '#attributes' => ['class' => ['details--settings']],
      '#group'      => 'options',
      '#parents'    => ['options', 'settings'],
    ];

    $settings = $optionset->getSettings();
    $form['settings'] += $this->attachSettingsForm($settings);

    // Responsive JS options.
    $responds = $optionset->getSetting('respond') ?: [];
    $form['respond'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Responsive display'),
      '#open'        => TRUE,
      '#tree'        => TRUE,
      '#attributes'  => ['class' => ['details--respond', 'has-tooltip']],
      '#group'       => 'options',
      '#parents'     => ['options', 'settings', 'respond'],
      '#description' => $this->t('Containing breakpoints and settings objects.'),
    ];

    $form['respond']['settings'] = [
      '#type'        => 'table',
      '#tree'        => TRUE,
      '#header'      => [
        $this->t('Delta'),
        $this->t('Range'),
        $this->t('Enabled'),
        $this->t('Settings'),
        $this->t('Ops'),
      ],
      '#attributes'  => ['class' => ['form-wrapper--table', 'form-wrapper--table-respond']],
      '#prefix'      => '<div id="edit-respond-settings-wrapper">',
      '#suffix'      => '</div>',
      '#group'       => 'options',
      '#parents'     => ['options', 'settings', 'respond'],
    ];

    $num_responds = $form_state->get('num_responds') ?: count($responds);
    if (empty($num_responds)) {
      $num_responds = 1;
    }

    $form_state->set('num_responds', $num_responds);

    $excludes = ['cursor', 'loadingIcon', 'tint'];
    for ($i = 0; $i <= $num_responds; $i++) {
      $form['respond']['settings'][$i]['delta'] = [
        '#markup' => $i,
      ];

      $form['respond']['settings'][$i]['range'] = [
        '#type'          => 'textfield',
        '#title'         => $this->t('Range'),
        '#title_display' => 'invisible',
        '#default_value' => isset($responds[$i]['range']) ? $responds[$i]['range'] : '',
        '#size'          => 40,
        '#max_length'    => 120,
        '#description'   => $this->t('The window range to activate the responsive settings, e.g.: 600-799.'),
      ];

      $form['respond']['settings'][$i]['enabled'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Enabled'),
        '#title_display' => 'invisible',
        '#default_value' => isset($responds[$i]['enabled']) ? $responds[$i]['enabled'] : TRUE,
        '#size'          => 40,
        '#max_length'    => 120,
        '#description'   => $this->t('Uncheck to disable the zoom at this range.'),
      ];

      if ($admin_css) {
        $form['respond']['settings'][$i]['enabled']['#field_suffix'] = '&nbsp;';
        $form['respond']['settings'][$i]['enabled']['#title_display'] = 'invisible';
      }

      $form['respond']['settings'][$i]['settings'] = [
        '#type'          => 'details',
        '#open'          => FALSE,
        '#title'         => $this->t('Settings'),
        '#title_display' => 'invisible',
        '#attributes'    => ['class' => ['form-wrapper--responsive']],
        '#group'         => $i,
        '#parents'       => ['options', 'settings', 'respond', $i],
      ];

      $settings = isset($responds[$i]) ? $responds[$i] : [];
      $form['respond']['settings'][$i]['settings'] += $this->attachSettingsForm($settings, $excludes);

      $form['respond']['settings'][$i]['remove_respond'] = [
        '#type'   => 'submit',
        '#value'  => $this->t('x'),
        '#name'   => 'remove_respond_' . $i,
        '#submit' => [[$this, 'removeRespond']],
        '#ajax'   => [
          'callback' => [$this, 'removeRespondCallback'],
          'wrapper'  => 'edit-respond-settings-wrapper',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    $form['respond']['actions'] = [
      '#type' => 'actions',
    ];

    $form['respond']['actions']['add_respond'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add another respond'),
      '#name'   => 'add_respond',
      '#submit' => [[$this, 'addRespond']],
      '#ajax'   => [
        'callback' => [$this, 'addRespondCallback'],
        'wrapper'  => 'edit-respond-settings-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Attach the settings form.
   */
  protected function attachSettingsForm($settings = [], $excludes = []) {
    $admin_css = $this->manager->configLoad('admin_css', 'blazy.settings');
    $form      = [];

    foreach ($this->getFormElements() as $name => $element) {
      if ($excludes && in_array($name, $excludes)) {
        continue;
      }

      $element['default'] = isset($element['default']) ? $element['default'] : '';
      $form[$name] = [
        '#title'         => isset($element['title']) ? $element['title'] : '',
        '#default_value' => isset($settings[$name]) ? $settings[$name] : $element['default'],
      ];

      if (isset($element['type'])) {
        $form[$name]['#type'] = $element['type'];
        if ($element['type'] != 'hidden') {
          $form[$name]['#attributes']['class'][] = 'is-tooltip';
        }
        else {
          // Ensures hidden element doesn't screw up the states.
          unset($element['states']);
        }

        if ($element['type'] == 'textfield') {
          $form[$name]['#size'] = 20;
          $form[$name]['#maxlength'] = 255;
        }
      }

      $items = [
        'access',
        'description',
        'field_suffix',
        'options',
        'empty_option',
        'states',
      ];
      foreach ($items as $key) {
        if (isset($element[$key])) {
          $form[$name]['#' . $key] = $element[$key];
        }
      }

      if (is_int($element['default'])) {
        $form[$name]['#maxlength'] = 60;
        $form[$name]['#attributes']['class'][] = 'form-text--int';
      }

      if ($admin_css && is_bool($element['default'])) {
        $form[$name]['#field_suffix'] = '&nbsp;';
        $form[$name]['#title_display'] = 'before';
      }
    }

    return $form;
  }

  /**
   * Handles adding the responds.
   */
  public function addRespond(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_responds') + 1;

    $form_state->set('num_responds', $num);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for adding the responds.
   */
  public function addRespondCallback(array &$form, FormStateInterface &$form_state) {
    return $form['respond']['settings'];
  }

  /**
   * Handles removing the responds.
   */
  public function removeRespond(array &$form, FormStateInterface &$form_state) {
    $num = $form_state->get('num_responds');
    if ($num > 0) {
      $form_state->set('num_responds', $num - 1);
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback for removing the responds.
   */
  public function removeRespondCallback(array &$form, FormStateInterface &$form_state) {
    return $form['respond']['settings'];
  }

  /**
   * Defines available options.
   *
   * @return array
   *   All available options.
   */
  public function getFormElements() {
    if (!isset($this->formElements)) {
      $elements = [];

      // @todo provide relevant respond options.
      $elements['responsive'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('Responsive'),
        'description' => $this->t('Set to true to activate responsivenes. If you have a theme which changes size, or tablets which change orientation this is needed to be on.'),
        'access'      => FALSE,
      ];

      $options = ['inner' => 'Inner', 'lens' => 'Lens', 'window' => 'Window'];
      $elements['zoomType'] = [
        'type'        => 'select',
        'title'       => $this->t('zoomType'),
        'options'     => $options,
        'description' => $this->t('The zoom type.'),
      ];

      $elements['zoomWindowWidth'] = [
        'type'        => 'textfield',
        'title'       => $this->t('zoomWindowWidth'),
        'description' => $this->t('Width of the zoomWindow (Note: zoomType must be "window").'),
        // @todo 'states' => $this->getState(static::ZOOM_TYPE_WINDOW),
      ];

      $elements['zoomWindowHeight'] = [
        'type'        => 'textfield',
        'title'       => $this->t('zoomWindowHeight'),
        'description' => $this->t('Height of the zoomWindow (Note: zoomType must be "window").'),
        // @todo 'states' => $this->getState(static::ZOOM_TYPE_WINDOW),
      ];

      $elements['zoomWindowOffsetX'] = [
        'type'        => 'textfield',
        'title'       => $this->t('zoomWindowOffsetX'),
        'description' => $this->t('The x-axis offset of the zoom window.'),
      ];

      $elements['zoomWindowOffsetY'] = [
        'type'        => 'textfield',
        'title'       => $this->t('zoomWindowOffsetY'),
        'description' => $this->t('The y-axis offset of the zoom window.'),
      ];

      $elements['zoomWindowPosition'] = [
        'type'        => 'textfield',
        'title'       => $this->t('zoomWindowPosition'),
        'description' => $this->t('Accepts a position, a selector or an element Id. For positions, once positioned, use zoomWindowOffsetX and zoomWindowOffsetY to adjust. Possible values: 1-16.'),
      ];

      $elements['zoomWindowFadeIn'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('zoomWindowFadeIn'),
        'description' => $this->t('Set as a number e.g 200 for speed of Window fadeIn.'),
      ];

      $elements['zoomWindowFadeOut'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('zoomWindowFadeOut'),
        'description' => $this->t('Set as a number e.g 200 for speed of Window fadeOut.'),
      ];

      $elements['zoomTintFadeIn'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('zoomTintFadeIn'),
        'description' => $this->t('Set as a number e.g 200 for speed of Tint fadeIn.'),
      ];

      $elements['zoomTintFadeOut'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('zoomTintFadeOut'),
        'description' => $this->t('Set as a number e.g 200 for speed of Tint fadeOut.'),
      ];

      $elements['scrollZoom'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('scrollZoom'),
        'description' => $this->t('Set to true to activate zoom on mouse scroll.'),
      ];

      $elements['imageCrossfade'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('imageCrossfade'),
        'description' => $this->t('Set to true to activate simultaneous crossfade of images on gallery change.'),
      ];

      $elements['easing'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('easing'),
        'description' => $this->t('Set to true to activate easing.'),
      ];

      $elements['easingType'] = [
        'type'        => 'textfield',
        'title'       => $this->t('easingType'),
        'description' => $this->t('Default easing type is <code>easeOutExpo, (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b</code>. Extend jquery with other easing types before initiating the plugin and pass the easing type as a string value.'),
      ];

      $elements['easingDuration'] = [
        'type'        => 'textfield',
        'title'       => $this->t('easingDuration'),
        'description' => $this->t('The easing duration.'),
      ];

      $elements['showLens'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('showLens'),
        'description' => $this->t('Set to false to hide the Lens.'),
      ];

      $elements['lensSize'] = [
        'type'        => 'textfield',
        'title'       => $this->t('lensSize'),
        'description' => $this->t('Used when zoomType set to lens, when zoom type is set to window, then the lens size is auto calculated.'),
      ];

      $elements['lensFadeIn'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('lensFadeIn'),
        'description' => $this->t('Set as a number e.g 200 for speed of Lens fadeIn.'),
      ];

      $elements['lensFadeOut'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('lensFadeOut'),
        'description' => $this->t('Set as a number e.g 200 for speed of Lens fadeOut.'),
      ];

      $elements['borderSize'] = [
        'type'        => 'textfield',
        'title'       => $this->t('borderSize'),
        'description' => $this->t('Border Size of the ZoomBox - Must be set here as border taken into account for plugin calculations.'),
      ];

      $elements['borderColour'] = [
        'type'        => 'textfield',
        'title'       => $this->t('borderColour'),
        'description' => $this->t('Border Color.'),
      ];

      $elements['lensBorder'] = [
        'type'         => 'textfield',
        'title'        => $this->t('lensBorder'),
        'description'  => $this->t('Width in pixels of the lens border.'),
        'field_suffix' => 'px',
      ];

      $elements['lensShape'] = [
        'type'        => 'textfield',
        'title'       => $this->t('lensShape'),
        'description' => $this->t('Can also be round (note that only modern browsers support round, will default to square in older browsers).'),
      ];

      $elements['containLensZoom'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('containLensZoom'),
        'description' => $this->t('For use with the Lens Zoom Type. This makes sure the lens does not fall outside the outside of the image.'),
      ];

      $elements['lensColour'] = [
        'type'        => 'textfield',
        'title'       => $this->t('lensColour'),
        'description' => $this->t('Color of the lens background.'),
      ];

      $elements['lensOpacity'] = [
        'type'        => 'textfield',
        'title'       => $this->t('lensOpacity'),
        'description' => $this->t('Used in combination with lensColour to make the lens see through. When using tint, this is overrided to 0.'),
      ];

      $elements['lenszoom'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('lenszoom'),
        'description' => $this->t('The lens zoom.'),
      ];

      $elements['tint'] = [
        'type'        => 'checkbox',
        'title'       => $this->t('tint'),
        'description' => $this->t('Enable a tint overlay.'),
      ];

      $elements['tintColour'] = [
        'type'        => 'textfield',
        'title'       => $this->t('tintColour'),
        'description' => $this->t('Color of the tint, can be #hex, word (red, blue), or rgb(x, x, x).'),
      ];

      $elements['tintOpacity'] = [
        'type'        => 'textfield',
        'title'       => $this->t('tintOpacity'),
        'description' => $this->t('Opacity of the tint.'),
      ];

      $elements['constrainType'] = [
        'type'        => 'textfield',
        'title'       => $this->t('constrainType'),
        'description' => $this->t('Constraint type.'),
      ];

      $elements['constrainSize'] = [
        'type'        => 'textfield',
        'title'       => $this->t('constrainSize'),
        'description' => $this->t('Constraint size.'),
      ];

      // @todo exclude below from responsive options.
      $elements['cursor'] = [
        'type'        => 'textfield',
        'title'       => $this->t('cursor'),
        'description' => $this->t('The default cursor is usually the arrow, if using a lightbox, then set the cursor to pointer so it looks clickable - Options are default, cursor, crosshair.'),
      ];

      // This has inconsistent schema, boolean vs string, adjust at front-end.
      $elements['loadingIcon'] = [
        'type'        => 'textfield',
        'title'       => $this->t('loadingIcon'),
        'description' => $this->t('Set to the url of the spinner icon to activate, e.g, http://www.example.com/spinner.gif.'),
      ];

      // Defines the default values if available.
      $defaults = ElevateZoomPlus::defaultSettings();
      foreach ($elements as $name => $element) {
        $fallback = $element['type'] == 'checkbox' ? FALSE : '';
        $elements[$name]['default'] = isset($defaults[$name]) ? $defaults[$name] : $fallback;
      }

      $this->formElements = $elements;
    }
    return $this->formElements;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Cleanups unused/ empty settings.
    $form_state->unsetValue(['options', 'options__active_tab']);
    $form_state->unsetValue(['options', 'settings', 'respond', 'actions']);
    $responds = $form_state->getValue(['options', 'settings', 'respond']);
    $responds = $form_state->hasValue(['options', 'settings', 'respond']) ? $responds : [];
    if ($responds) {
      foreach ($responds as $key => $respond) {
        if (empty($respond['range'])) {
          $form_state->unsetValue(['options', 'settings', 'respond', $key]);
        }
      }

      $responds = $form_state->getValue(['options', 'settings', 'respond']);
      if (count($responds) > 0) {
        $form_state->setValue(['options', 'settings', 'responsive'], 1);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Optimize optimize options to free up some bytes.
    $entity = $this->entity;
    $default = $entity->id() == 'default';
    if (!$default) {
      $defaults = ElevateZoomPlus::defaultSettings();
      $settings = $form_state->getValue(['options', 'settings']);

      // Cast the values.
      $this->typecastOptionset($settings);
      $optimized_values = array_diff_assoc($settings, $defaults);

      if (isset($optimized_values['respond'])) {
        foreach ($optimized_values['respond'] as &$respond) {
          $respond = array_diff_assoc($respond, $defaults);
        }
      }

      $entity->setSettings($optimized_values);
    }
  }

  /**
   * Returns the typecast values.
   *
   * @param array $settings
   *   An array of Optionset settings.
   */
  public function typecastOptionset(array &$settings = []) {
    if (empty($settings)) {
      return;
    }

    $defaults = ElevateZoomPlus::defaultSettings();

    foreach ($defaults as $name => $value) {
      if (isset($settings[$name])) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($defaults[$name]);
        $type = $type == 'double' ? 'float' : $type;

        // Change float to integer if value is no longer float.
        if ($name == 'lensOpacity' || $name == 'tintOpacity') {
          $type = ($settings[$name] == '0' || $settings[$name] == '1') ? 'integer' : 'float';
        }

        settype($settings[$name], $type);
      }
    }

    if (isset($settings['respond'])) {
      foreach ($settings['respond'] as &$respond) {
        if (isset($respond['enabled'])) {
          settype($respond['enabled'], 'boolean');
        }

        foreach ($defaults as $name => $value) {
          if (isset($respond[$name])) {
            $type = gettype($defaults[$name]);
            $type = $type == 'double' ? 'float' : $type;

            // Change float to integer if value is no longer float.
            if ($name == 'lensOpacity' || $name == 'tintOpacity') {
              $type = ($respond[$name] == '0' || $respond[$name] == '1') ? 'integer' : 'float';
            }

            settype($respond[$name], $type);
          }
        }
      }
    }
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state) {
    $states = [
      static::ZOOM_TYPE_INNER => [
        'visible' => [
          'select[name$="[zoomType]"]' => ['value' => 'inner'],
        ],
      ],
      static::ZOOM_TYPE_LENS => [
        'visible' => [
          'select[name$="[zoomType]"]' => ['value' => 'lense'],
        ],
      ],
      static::ZOOM_TYPE_WINDOW => [
        'visible' => [
          'select[name$="[zoomType]"]' => ['value' => 'window'],
        ],
      ],
      static::SHOW_LENS => [
        'visible' => [
          ':input[name$="[showLens]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $states[$state];
  }

}
