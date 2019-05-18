<?php

/**
 * @file
 * Contains \Drupal\jquery_ui_filter\Form\jQueryUiFilterSettingsForm.
 */

namespace Drupal\jquery_ui_filter\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jquery_ui_filter\Plugin\Filter\jQueryUiFilter;

/**
 * Defines a form that configures jQuery UI filter settings.
 */
class jQueryUiFilterSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jquery_ui_filter_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jquery_ui_filter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $option_descriptions = [
      'headerTag' => $this->t("<b>headerTag</b>: Header tag for widget's element"),
      'mediaType' => $this->t("<b>mediaType</b>: Media type (all, screen, or print) that widget should be render to. Setting <code>mediaType</code> to <code>screen</code> will limit the display of the widget to the browser and when the page is printed the widget will default back to simple HTML markup. Setting <code>mediaType</code> to <code>all</code> allows the widget to be printed."),
      'scrollTo' => $this->t('<b>scrollTo</b>: Scrolls to bookmarked widget when a page is loaded.'),
      'scrollToDuration' => $this->t('<b>scrollToDuration</b>: The speed at which the page scrolls to a bookmarked widget.'),
      'scrollToOffset' => $this->t("<b>scrollToOffset</b>: The offset from the top of the page that a bookmarked widget scrolls to. Setting to <code>auto</code> will include the page's body top margin and top padding when scrolling to a bookmarked widget."),
    ];

    $config = $this->config('jquery_ui_filter.settings');
    $form['jquery_ui_filter'] = [
      '#tree' => TRUE,
    ];

    foreach (jQueryUiFilter::$widgets as $name => $widget) {
      $t_args = [
        '@name' => $name,
        '@api' => $widget['api'],
      ];

      $form['jquery_ui_filter'][$name] = [
        '#type' => 'details',
        '#title' => $widget['title'],
        '#description' => t('Learn more about <a href="@api">jQuery UI @name options</a>.', $t_args),
        '#open' => TRUE,
      ];
      $options_description = [
        'description' => [
          '#markup' => $this->t('Custom options:'),
        ],
        'options' => [
          '#theme' => 'item_list',
          '#items' => $option_descriptions,
        ],
      ];
      $form['jquery_ui_filter'][$name]['options']  = [
        '#type' => 'textarea',
        '#title' => $this->t('Options (YAML)'),
        '#description' => \Drupal::service('renderer')->render($options_description),
        '#default_value' => Yaml::encode(($config->get($name . '.options') ?: []) + $widget['options']),
        "#rows" => 6,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getValue('jquery_ui_filter');
    foreach (jQueryUiFilter::$widgets as $name => $label) {
      try {
        Yaml::decode($data[$name]['options']);
      }
      catch (\Exception $exception) {
        $message = [
          'message' => ['#markup' => t('%title is not valid.', ['%title' => $label])],
          'errors' => ['#theme' => 'item_list', '#items' => [$exception->getMessage()]],
        ];
        $form_state->setError($form['jquery_ui_filter'][$name]['options'], $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('jquery_ui_filter.settings');

    $data = $form_state->getValue('jquery_ui_filter');
    foreach (jQueryUiFilter::$widgets as $name => $widget) {
      $data[$name]['options'] = (Yaml::decode($data[$name]['options']) ?: []) + $widget['options'];
    }
    $config->setData($data);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
