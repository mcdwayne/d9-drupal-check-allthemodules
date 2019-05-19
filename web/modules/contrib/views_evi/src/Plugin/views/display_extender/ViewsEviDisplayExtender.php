<?php

namespace Drupal\views_evi\Plugin\views\display_extender;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_evi\ViewsEviFilterWrapper;

/**
 * Default display extender plugin; does nothing.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "views_evi",
 *   title = @Translation("Views EVI"),
 *   help = @Translation("Inject values to exposed filters."),
 * )
 */
class ViewsEviDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * Proxied pre-view hook. Pre-execute is too late.
   *
   * Populate $view->exposed_input before $view->get_exposed_input() does.
   */
  public function viewsEviPreView() {
    $input = $this->view->getExposedInput();

    foreach($this->getViewsEviFilterWrappers() as $filter_wrapper) {
      $input_override = $filter_wrapper->getValue();
      if (isset($input_override)) {
        $input = NestedArray::mergeDeep($input, $input_override);
      }
    }
    $this->view->setExposedInput($input);
    // Also fix sleep deprived merlin https://drupal.org/node/1407044
    if (!isset($this->view->exposed_data)) {
      $this->view->exposed_data = array();
    }
    $this->view->exposed_data = $input + $this->view->exposed_data;
    if (!isset($this->view->exposed_raw_input)) {
      $this->view->exposed_raw_input = array();
    }
    $this->view->exposed_raw_input = $input + $this->view->exposed_raw_input;
  }

  /**
   * Proxied form_alter hook.
   *
   * @param $form
   * @param $form_state
   */
  public function viewsEviExposedFormAlter(&$form, &$form_state) {
    // Go through exposed filters and set exposed widget visibility
    $form_empty = TRUE;
    foreach ($this->getViewsEviFilterWrappers() as $filter_wrapper) {
      $identifier = $filter_wrapper->getIdentifier();
      $id = $filter_wrapper->getId();
      $visibility = $filter_wrapper->getVisibility($form);
      if (isset($visibility) && !$visibility) {
        // This is needed to kill the label too.
        unset($form['#info']["filter-$id"]);
        // This is not enough: $form[$identifier]['#access'] = FALSE;
        // Exposed form rendering builds and submits this, so fake the value
        $input_override = $filter_wrapper->getValue();
        foreach ($input_override as $identifier => $value) {
          $form[$identifier] = array(
            '#type' => 'value',
            '#value' => $value,
          );
        }
      }
      else {
        $form_empty = FALSE;
      }
    }
    if ($form_empty) {
      $form['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptionsAlter(&$options) {
    $options['views_evi_plugins'] = array('default' => array());
    $options['views_evi_settings'] = array('default' => array());
  }

  public function defaultableSections(&$sections, $section = NULL) {
    $sections['views_evi_plugins'] = array('views_evi_plugins', 'views_evi_settings');
    $sections['views_evi_settings'] = array('views_evi_plugins', 'views_evi_settings');
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    $options['views_evi_plugins'] = array(
      'category' => 'other',
      'title' => t('Views EVI'),
      'value' => t('Plugins'),
      'desc' => t('Choose plugins.'),
    );
    $options['views_evi_settings'] = array(
      'category' => 'other',
      'title' => t('Views EVI'),
      'value' => t('Settings'),
      'desc' => t('Configure plugin settings if needed.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    $is_plugins = $section == 'views_evi_plugins';
    $is_settings = $section == 'views_evi_settings';

    if ($is_plugins || $is_settings) {
      views_ui_standard_display_dropdown($form, $form_state, $section);
      $form[$section] = array(
        '#tree' => TRUE,
      );
      $form['#title'] .= $is_settings ? t('Views EVI settings') : t('Views EVI plugins');
    }

    // Iterate filters.
    foreach ($this->getViewsEviFilterWrappers() as $filter_id => $filter_wrapper) {
      $filter_settings_form = array();
      // Iterate plugin types.
      foreach(views_evi_plugin_types() as $plugin_type => $plugin_info) {

        // Plugins: Dropdown,
        if ($is_plugins) {
          $plugin_id = $filter_wrapper->getPluginId($plugin_type);
          $filter_settings_form[$plugin_type]['class'] = array(
            '#type' => 'select',
            '#title' => $plugin_info['label'],
            '#default_value' => $plugin_id,
            '#options' => views_evi_plugin_labels($plugin_type),
          );
        }

        // Settings: Delegate to plugins.
        if ($is_settings) {
          $plugin = $filter_wrapper->getPlugin($plugin_type);
          $settings = $filter_wrapper->getPluginSettings($plugin_type);
          $plugin_settings_form = $plugin->settingsForm($settings, $form);
          if ($plugin_settings_form) {
            $filter_settings_form[$plugin_type] = $plugin_settings_form;
          }
        }

      }

      // We only need a fieldset if there are settings.
      if ($filter_settings_form) {
        // Label is already sanitized.
        $evi_label = t('Settings for @label', array('@label' => $filter_wrapper->getEviLabel()));
        $form[$section]['filters'][$filter_id] = $filter_settings_form + array(
            '#type' => 'fieldset',
            '#title' => $evi_label,
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
          );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    $is_plugins = $section == 'views_evi_plugins';
    $is_settings = $section == 'views_evi_settings';

    // Plugins: Nothing to do.
    // Settings: Delegate to plugins.
    if ($is_settings) {
      $values = &$form_state->getValues();
      // Iterate filters.
      foreach ($this->getViewsEviFilterWrappers() as $filter_id => $filter_wrapper) {
        // Iterate plugin types.
        foreach(views_evi_plugin_types() as $plugin_type => $plugin_info) {
          $plugin = $filter_wrapper->getPlugin($plugin_type);
          $settings = &$filter_wrapper->getPluginSettingsRef($plugin_type, $values);
          $plugin->settingsFormValidate($settings);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $section = $form_state->get('section');
    $is_plugins = $section == 'views_evi_plugins';
    $is_settings = $section == 'views_evi_settings';


    $values = &$form_state->getValues();
    // Iterate filters.
    foreach ($this->getViewsEviFilterWrappers() as $filter_id => $filter_wrapper) {
      // Iterate plugin types.
      foreach(views_evi_plugin_types() as $plugin_type => $plugin_info) {

        // Plugins: Save class.
        if ($is_plugins) {
          $plugin_class = $form_state->getValue($section)['filters'][$filter_id][$plugin_type]['class'];
          $filter_wrapper->setPluginClass($plugin_type, $plugin_class);
        }

        // Settings: Delegate to plugins.
        if ($is_settings) {
          $plugin = $filter_wrapper->getPlugin($plugin_type);
          $plugin_form_values = &$filter_wrapper->getPluginSettingsRef($plugin_type, $values);
          $plugin_settings = $plugin->settingsFormSubmit($plugin_form_values);
          $filter_wrapper->setPluginSettings($plugin_type, $plugin_settings);
        }

      }
    }

  }

  /**
   * Get filter wrappers for all exposed filters of this view.
   *
   * @return \Drupal\views_evi\ViewsEviFilterWrapper[]
   */
  public function getViewsEviFilterWrappers() {
    $all_filter_handlers = $this->displayHandler->getHandlers('filter');

    $filter_is_exposed = function(FilterPluginBase $filter_handler) {
      return !empty($filter_handler->options['exposed']);
    };
    $filter_handlers = array_filter($all_filter_handlers, $filter_is_exposed);

    $wrap_filter = function($filter_handler) {
      return new ViewsEviFilterWrapper($filter_handler);
    };
    $filter_wrappers = array_map($wrap_filter, $filter_handlers);

    return $filter_wrappers;
  }

  /** @var array $cache */
  private $cache;

  /**
   * Allow our friends to get their EVI_global cache values.
   *
   * @see ViewsEviValueToken
   *
   * @param string $class
   * @param string $key
   * @return mixed
   */
  public function getViewsEviCache($class, $key) {
    return @$this->cache["$class-$key"];
  }

  /**
   * Allow our friends to set their EVI_global cache values.
   *
   * @see ViewsEviValueToken
   *
   * @param string $class
   * @param string $key
   * @param mixed $value
   */
  public function setViewsEviCache($class, $key, $value) {
    $this->cache["$class-$key"] = $value;
  }

}
