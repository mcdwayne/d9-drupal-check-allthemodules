<?php

namespace Drupal\single_format_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\text\Plugin\Field\FieldWidget\TextfieldWidget;

/**
 * Plugin implementation of the 'text_single_textfield' widget.
 *
 * @FieldWidget(
 *   id = "text_single_textfield",
 *   label = @Translation("Text field with single format"),
 *   field_types = {
 *     "text",
 *     "string_long",
 *     "text_long",
 *   },
 * )
 */
class SingleFormatTextField extends TextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format'] = 'plain_text';
    $settings['hide_tips'] = FALSE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $options = [];
    foreach (FilterFormat::loadMultiple() as $id => $format) {
      if ($format->status()) {
        $options[$id] = $format->label();
      }
    }
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format to limit'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $options,
    ];
    $elements['hide_tips'] = [
      '#title' => $this->t('Hide Filter Tips'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('hide_tips'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $build = parent::formElement($items, $delta, $element, $form, $form_state);
    $build['#allowed_formats'] = [$this->getSetting('format')];
    if ($this->getSetting('hide_tips')) {
      $build['#after_build'][] = [static::class, 'removeFilterTips'];
    }
    $build['#base_type'] = 'textarea';
    return $build;
  }

  /**
   * Remove the filter tips.
   *
   * @param array $element
   *   An element to alter after building.
   *
   * @return array
   *   The modified element.
   */
  public static function removeFilterTips($element) {
    unset($element['format']['help']);
    unset($element['format']['guidelines']);
    unset($element['format']['#attributes']['class']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = t('Limited to the @format format', [
      '@format' => FilterFormat::load($this->getSetting('format'))->label(),
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $format = FilterFormat::load($this->getSetting('format'));
    $dependencies[$format->getConfigDependencyKey()][] = $format->getConfigDependencyName();
    return $dependencies;
  }

}
