<?php

namespace Drupal\pubble_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Outputs an integer field with the necessary data to load Pubble.
 *
 * @FieldFormatter(
 *   id = "pubble_field",
 *   label = @Translation("Pubble formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class PubbleField extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'identifier' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['identifier'] = [
      '#type' => 'textfield',
      '#title' => t('App identifier'),
      '#default_value' => $this->getSetting('identifier'),
      '#weight' => 0,
      '#description' => $this->t('This may be used to add the optional "data-app-identifier" string to the Pubble loader.'),
    ];

    $elements['identifier_tokens'] = [
      '#theme' => 'token_tree_link',
      // @todo Make this entity-generic.
      '#token_types' => ['node'],
      '#show_restricted' => TRUE,
      '#global_types' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Renders a Pubble application');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $token = \Drupal::token();
    // @todo Make this entity-generic.
    $node = \Drupal::routeMatch()->getParameter('node');

    foreach ($items as $delta => $item) {
      // Optional token handling.
      $identifier = $this->getSetting('identifier');
      if ($node) {
        $identifier = $token->replace($identifier, ['node' => $node]);
      }

      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'pubble-app PQAQ_section',
          'data-app-id' => $item->value,
          'data-app-identifier' => $identifier,
        ],
        '#attached' => [
          'library' => [
            'pubble_field/loader',
          ],
        ],
      ];
    }

    return $elements;
  }

}
