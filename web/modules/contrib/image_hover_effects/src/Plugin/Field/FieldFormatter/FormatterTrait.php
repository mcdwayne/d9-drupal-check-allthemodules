<?php

namespace Drupal\image_hover_effects\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;

/**
 * Helper methods for image formatters.
 */
trait FormatterTrait {

  /**
   * {@inheritdoc}
   */
  protected function buildSettingsForm(&$element) {
    $settings = $this->getSettings();

    // Hover effects can be only applied to links.
    $states_selector = sprintf('select[name="fields[%s][settings_edit_form][settings][image_link]"]', $this->fieldDefinition->getName());
    $states['invisible'][][$states_selector]['value'] = '';
    $element['hover_effect'] = [
      '#type' => 'select',
      '#title' => t('Image Hover Effect'),
      '#options' => ['' => t('- None -')] + self::getEffects(),
      '#default_value' => $settings['hover_effect'],
      '#states' => $states,
    ];
    $element['hover_text'] = [
      '#type' => 'textarea',
      '#title' => t('Hover text'),
      '#default_value' => $settings['hover_text'],
      '#rows' => 3,
      '#attributes' => ['style' => 'max-width: 180px;'],
      '#states' => $states,
      '#description' => t('You may use tokens to display entity data. Example: [node:title]'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettingsSummary() {
    $settings = $this->getSettings();
    $summary = [];
    $image_link = $settings['image_link'];
    if ($image_link) {
      if ($settings['hover_effect']) {
        $effects = self::getEffects();
        $summary[] = t('Hover effect: %effect', ['%effect' => $effects[$settings['hover_effect']]]);
      }
      $summary[] = t('Hover text: %text', ['%text' => $settings['hover_text']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function updateElements(array &$elements, EntityInterface $entity, $theme) {
    $token = \Drupal::token();
    $settings = $this->getSettings();
    $entity_type = $entity->getEntityTypeId();

    foreach ($elements as $delta => $element) {
      $elements[$delta]['#theme'] = $theme;
      $elements[$delta]['#link_attributes'] = [
        'class' => ['ihe-overlay', Html::getClass('ihe-overlay--' . $settings['hover_effect'])],
        'data-hover' => $token->replace($settings['hover_text'], [$entity_type => $entity]),
      ];
    }

    $elements['#attached']['library'][] = 'image_hover_effects/image_hover_effects';
    return $elements;
  }

  /**
   * Returns list of supported effects.
   */
  protected static function getEffects() {
    return [
      'zoom' => t('Zoom'),
      'default' => t('Overlay'),
      'fade_in' => t('Overlay fade in'),
      'zoom_in' => t('Overlay zoom in'),
      'fade_in_down' => t('Overlay fade in down'),
      'fade_in_up' => t('Overlay fade in up'),
      'fade_in_left' => t('Overlay fade in left'),
      'fade_in_right' => t('Overlay fade in right'),
    ];
  }

}
