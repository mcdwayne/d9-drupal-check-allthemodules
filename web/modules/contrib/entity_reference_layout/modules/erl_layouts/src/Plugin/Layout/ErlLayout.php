<?php

namespace Drupal\erl_layouts\Plugin\Layout;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;

/**
 * Layout class with various formatting options for custom ERL layouts.
 */
class ErlLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration += [
      'layout_classes' => '',
      'layout_bg_color' => '',
    ];
    foreach ($this->getPluginDefinition()->getRegions() as $region => $region_info) {
      $configuration[$region]['classes'] = '';
      $configuration[$region]['bg_color'] = '';
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $configuration = $this->getConfiguration();
    $build = parent::build($regions);

    if (!empty($configuration['layout_classes'])) {
      $layout_classes = is_array($configuration['layout_classes']) ? implode(' ', $configuration['layout_classes']) : $configuration['layout_classes'];
      $build['#attributes']['class'][] = $layout_classes;
    }

    if (!empty($configuration['layout_bg_color'])) {
      $build['#attributes']['style'] = 'background-color: ' . $configuration['layout_bg_color'];
    }

    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      if (array_key_exists($region_name, $regions)) {
        if ($configuration[$region_name]['classes']) {
          $region_classes = is_array($configuration[$region_name]['classes']) ? implode(' ', $configuration[$region_name]['classes']) : $configuration[$region_name]['classes'];
          $build[$region_name]['#attributes']['class'][] = $region_classes;
        }
        if ($configuration[$region_name]['bg_color']) {
          $build[$region_name]['#attributes']['style'] = 'background-color: ' . $configuration[$region_name]['bg_color'];
        }
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Add a process callback so $form[#parents] are correctly populated.
   *
   * If we were adding options that did not depend on
   * third party widget settings, using #process would be unnecessary.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#process'] = [[$this, 'processConfigurationForm']];
    return $form;
  }

  /**
   * Add the options.
   *
   * @param array $form_item
   *   The Form Item.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Current state of the form.
   */
  public function processConfigurationForm(array $form_item, FormStateInterface $form_state) {

    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof ContentEntityFormInterface) {
      return NULL;
    }

    $display = $form_object->getFormDisplay($form_state);
    $component = $display->getComponent($form_item['#parents'][0]);
    // @todo There must be a better way to set third_party_settings defaults!
    $settings = [
      'layout_class_mode' => 'manual',
      'layout_color_mode' => 'manual',
      'region_class_mode' => 'manual',
      'region_color_mode' => 'manual',
    ];
    if (isset($component['third_party_settings']['erl_layouts'])) {
      $settings = $component['third_party_settings']['erl_layouts'];
    }

    $config = $this->getConfiguration();

    switch ($settings['layout_class_mode']) {
      case 'manual':
        $form_item['layout_classes'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Classes'),
          '#default_value' => !empty($config['layout_classes']) ? $config['layout_classes'] : '',
          '#description' => $this->t('Add classes to this layout. The classes must be separated by a space.'),
          '#size' => '100',
        ];
        break;

      case 'select':
        $classes_available = $this->getSelectOptions($settings['layout_class_select']);
        $default_value = !empty($config['layout_classes']) ? $config['layout_classes'] : '';
        $form_item['layout_classes'] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#title' => $this->t('Classes'),
          '#options' => $classes_available,
          '#default_value' => $default_value,
        ];
        break;

      case 'force':
        $form_item['layout']['layout_classes'] = [
          '#type' => 'value',
          '#value' => $settings['layout_class_force'],
        ];
        break;
    }

    switch ($settings['layout_color_mode']) {
      case 'manual':
        $form_item['layout_bg_color'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Background color'),
          '#default_value' => !empty($config['layout_bg_color']) ? $config['layout_bg_color'] : '',
          '#description' => $this->t('Background color (hex code) for this layout.'),
          '#attributes' => [
            'placeholder' => '#ffffff',
          ],
          '#size' => '12',
        ];
        break;

      case 'select':
        $colors_available = $this->getSelectOptions($settings['layout_color_select']);
        $default_value = !empty($config['layout_bg_color']) ? $config['layout_bg_color'] : '';
        $form_item['layout_bg_color'] = [
          '#type' => 'select',
          '#title' => $this->t('Background color'),
          '#options' => $colors_available,
          '#default_value' => $default_value,
        ];
        break;

      case 'force':
        $form_item['layout_bg_color'] = [
          '#type' => 'value',
          '#value' => $settings['layout_color_force'],
        ];
        break;
    }

    foreach ($this->getPluginDefinition()->getRegions() as $region => $region_info) {
      $region_label = $region_info['label'];
      $form_item[$region] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@region region', ['@region' => $region_label]),
      ];
      $visible_items = FALSE;

      switch ($settings['region_class_mode']) {
        case 'manual':
          $form_item[$region]['classes'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Classes'),
            '#default_value' => !empty($config[$region]['classes']) ? $config[$region]['classes'] : '',
            '#description' => $this->t('Add classes to this region. The classes must be separated by a space.'),
            '#size' => '100',
          ];
          $visible_items = TRUE;
          break;

        case 'select':
          $classes_available = $this->getSelectOptions($settings['region_class_select']);
          $default_value = !empty($config[$region]['classes']) ? $config[$region]['classes'] : [];
          $form_item[$region]['classes'] = [
            '#type' => 'select',
            '#multiple' => TRUE,
            '#title' => $this->t('Classes'),
            '#options' => $classes_available,
            '#default_value' => $default_value,
          ];
          $visible_items = TRUE;
          break;

        case 'force':
          $form_item[$region]['classes'] = [
            '#type' => 'value',
            '#value' => $settings['region_class_force'],
          ];
          break;
      }

      switch ($settings['region_color_mode']) {
        case 'manual':
          $form_item[$region]['bg_color'] = [
            '#type' => 'textfield',
            '#title' => $this->t('@region_label region background color', ['@region_label' => $region_label]),
            '#default_value' => !empty($config[$region]['bg_color']) ? $config[$region]['bg_color'] : '',
            '#description' => $this->t('Background color (hex code) for this region.'),
            '#attributes' => [
              'placeholder' => '#ffffff',
            ],
            '#size' => '12',
          ];
          $visible_items = TRUE;
          break;

        case 'select':
          $colors_available = $this->getSelectOptions($settings['region_color_select']);
          $default_value = !empty($config[$region]['bg_color']) ? $config[$region]['bg_color'] : '';
          $form_item[$region]['bg_color'] = [
            '#type' => 'select',
            '#title' => $this->t('Background color'),
            '#options' => ['' => $this->t('- None -')] + $colors_available,
            '#default_value' => $default_value,
          ];
          $visible_items = TRUE;
          break;

        case 'force':
          $form_item[$region]['bg_color'] = [
            '#type' => 'value',
            '#value' => $settings['region_color_force'],
          ];
          break;
      }

      if (!$visible_items) {
        $form_item[$region]['#access'] = FALSE;
      }
    }

    return $form_item;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Any additional form validation that is required.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['layout_classes'] = $form_state->getValue('layout_classes');
    $this->configuration['layout_bg_color'] = $form_state->getValue('layout_bg_color');
    $this->configuration['column_priority'] = $form_state->getValue('column_priority');
    foreach ($this->getPluginDefinition()->getRegions() as $region => $region_info) {
      $this->configuration[$region]['classes'] = $form_state->getValue([$region, 'classes']);
      $this->configuration[$region]['bg_color'] = $form_state->getValue([$region, 'bg_color']);
    }
  }

  /**
   * Convert textarea lines into an array.
   *
   * @param string $string
   *   The textarea lines to explode.
   * @param bool $summary
   *   A flag to return a formatted list of classes available.
   *
   * @return array
   *   An array keyed by the classes.
   */
  protected function getSelectOptions($string, $summary = FALSE) {
    $options = [];
    $lines = preg_split("/\\r\\n|\\r|\\n/", trim($string));
    $lines = array_filter($lines);

    foreach ($lines as $line) {
      list($class, $label) = explode('|', trim($line));
      $label = $label ?: $class;
      $options[$class] = $label;
    }

    if ($summary) {
      return implode(', ', array_keys($options));
    }

    return $options;

  }

}
