<?php

namespace Drupal\cshs\Plugin\Field\FieldFormatter;

use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the "Flexible hierarchy" formatter.
 *
 * @FieldFormatter(
 *   id = "cshs_flexible_hierarchy",
 *   label = @Translation("Flexible hierarchy"),
 *   description = @Translation("Allows to specify the output with tokens."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class CshsFlexibleHierarchyFormatter extends CshsFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => '[term:name]',
      'clear' => TRUE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Format'),
      '#description' => $this->t('Specify a format for each field item by using tokens.'),
      '#default_value' => $this->getSetting('format'),
    ];

    $element['clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear Tokens'),
      '#description' => $this->t('Remove token from final text if no replacement value is generated'),
      '#default_value' => $this->getSetting('clear'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token_help'] = [
        '#type' => 'markup',
        '#token_types' => ['term'],
        '#theme' => 'token_tree_link',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Format: @format', ['@format' => $this->getSetting('format')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $linked = $this->getSetting('linked');
    $format = $this->getSetting('format');
    $clear = $this->getSetting('clear');

    $token = \Drupal::token();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $term) {
      $text = $token->replace($format, ['term' => $term], ['clear' => $clear]);

      if ($linked) {
        $text = Link::fromTextAndUrl($text, $term->toUrl())->toString();
      }

      $elements[$delta]['#markup'] = $text;
    }

    return $elements;
  }

}
