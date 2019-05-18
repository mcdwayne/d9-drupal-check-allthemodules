<?php
/**
 * @file
 * Contains \Drupal\accessibility_tool\Form\AccessibilityToolSettingsForm.
 */

namespace Drupal\accessibility_tool\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AccessibilityToolSettingsForm.
 *
 * @package Drupal\accessibility_tool\Form
 */
class AccessibilityToolSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'accessibility_tool_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('accessibility_tool.settings');

    // Positioning.
    $form['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => [
        'right' => $this->t('Right'),
        'left' => $this->t('Left'),
      ],
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $config->get('accessibility_tool.position'),
    ];

    // Tool color.
    $form['tool_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Tool color'),
      '#default_value' => $config->get('accessibility_tool.tool_color'),
    ];

    // Contrast colors.
    $contrast_color_count = $min_contrast_color = 1;
    $max_contrast_color = 10;
    $form_state->set('min_contrast_color', $contrast_color_count);
    $form_state->set('max_contrast_color', $max_contrast_color);

    if (!empty($form_state->get('contrast_color_count'))) {
      $contrast_color_count = $form_state->get('contrast_color_count');
    }
    else if ($config->get('accessibility_tool.contrast_color_count')) {
      $contrast_color_count = $config->get('accessibility_tool.contrast_color_count');
      $form_state->set('contrast_color_count', $contrast_color_count);
    }
    else {
      $form_state->set('contrast_color_count', $contrast_color_count);
    }

    $form['contrast_color'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Contrast colors'),
      '#attributes' => [],
      '#prefix' => '<div id="contrast-row-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 1; $i <= $contrast_color_count; $i++) {
      $form['contrast_color']['color_' . $i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Contrast @num', ['@num' => $i]),
        '#attributes' => [],
        'color_background_' . $i => [
          '#type' => 'color',
          '#title' => $this->t('Background color'),
          '#default_value' => $config->get(
            'accessibility_tool.color_background_' . $i
          ),
        ],
        'color_foreground_' . $i => [
          '#type' => 'color',
          '#title' => $this->t('Foreground color'),
          '#default_value' => $config->get(
            'accessibility_tool.color_foreground_' . $i
          ),
        ],
      ];
    }

    if ($contrast_color_count < $max_contrast_color) {
      $form['contrast_color']['add_contrast'] = [
        '#type' => 'submit',
        '#value' => t('Add contrast'),
        '#submit' => array('::addContrastSubmit'),
      ];
    }
    if ($contrast_color_count > $min_contrast_color) {
      $form['contrast_color']['remove_contrast'] = [
        '#type' => 'submit',
        '#value' => t('Remove contrast'),
        '#submit' => array('::removeContrastSubmit'),
      ];
    }

    // Selectors.
    $form['selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Added selectors'),
      '#description' => $this->t('Add selectors that will change the selected' .
        ' element to the background and foreground colors e.g. ".class div". ' .
        'This will be addition to the "at-contrast". Add each selector on a ' .
        'new line.'),
      '#default_value' => $config->get('accessibility_tool.selectors'),
    ];
    $form['alt_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Added alterative selectors'),
      '#description' => $this->t('Add alterative selectors that will change ' .
        'the selected element to the opposite background and foreground ' .
        'colors e.g. ".class div". This will be addition to the ' .
        '"at-alt-contrast" class. Add each selector on a new line.'),
      '#default_value' => $config->get('accessibility_tool.alt_selectors'),
    ];

    // Help url.
    $form['help_link'] = [
      '#type' => 'url',
      '#title' => $this->t('Help link'),
      '#default_value' => $config->get('accessibility_tool.help_link'),
    ];

    $form_state->setCached(FALSE);

    return $form;
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addContrastSubmit(array &$form, FormStateInterface $form_state) {
    $max_contrast_color = $form_state->get('max_contrast_color');
    $contrast_color_count = $form_state->get('contrast_color_count');

    if ($max_contrast_color > $contrast_color_count) {
      $contrast_color_count++;
      $form_state->set('contrast_color_count', $contrast_color_count);
    }

    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function removeContrastSubmit(array &$form, FormStateInterface $form_state) {
    $min_contrast_color = $form_state->get('min_contrast_color');
    $contrast_color_count = $form_state->get('contrast_color_count');

    if ($min_contrast_color < $contrast_color_count) {
      $contrast_color_count--;
      $form_state->set('contrast_color_count', $contrast_color_count);
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('accessibility_tool.settings');
    $contrast_color_count = $form_state->get('contrast_color_count');

    $config->set(
      'accessibility_tool.position',
      $form_state->getValue('position'));

    $config->set(
      'accessibility_tool.tool_color',
      $form_state->getValue('tool_color'));

    $config->set(
      'accessibility_tool.contrast_color_count',
      $contrast_color_count);

    for ($i = 1;
         $i <= $config->get('accessibility_tool.contrast_color_count');
         $i++) {

      $config->set(
        'accessibility_tool.color_background_' . $i,
        $form_state->getValue('color_background_' . $i));
      $config->set(
        'accessibility_tool.color_foreground_' . $i,
        $form_state->getValue('color_foreground_' . $i)
      );
    }

    $config->set('accessibility_tool.selectors',
      $form_state->getValue('selectors'));
    $config->set('accessibility_tool.alt_selectors',
      $form_state->getValue('alt_selectors'));

    $config->set('accessibility_tool.help_link',
      $form_state->getValue('help_link'));

    $config->save();

    // Clear all caches.
    drupal_flush_all_caches();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'accessibility_tool.settings',
    ];
  }
}
