<?php

namespace Drupal\barcodes\Plugin\Field\FieldFormatter;

use Com\Tecnick\Barcode\Barcode as BarcodeGenerator;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'barcode' formatter.
 *
 * @FieldFormatter(
 *   id = "barcode",
 *   label = @Translation("Barcode"),
 *   field_types = {
 *     "email",
 *     "integer",
 *     "link",
 *     "string",
 *     "telephone",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class Barcode extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => 'QRCODE',
      'color' => '#000000',
      'height' => 100,
      'width' => 100,
      'padding_top' => 0,
      'padding_right' => 0,
      'padding_bottom' => 0,
      'padding_left' => 0,
      'show_value' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $generator = new BarcodeGenerator();
    $settings['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Barcode Type'),
      '#description' => $this->t('The Barcode type.'),
      '#options' => array_combine($generator->getTypes(), $generator->getTypes()),
      '#default_value' => $this->getSetting('type'),
    ];
    $settings['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#default_value' => $this->getSetting('color'),
      '#description' => $this->t('The color code.'),
    ];
    $settings['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#size' => 10,
      '#default_value' => $this->getSetting('height'),
      '#description' => $this->t('The height in pixels.'),
    ];
    $settings['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#size' => 10,
      '#default_value' => $this->getSetting('width'),
      '#description' => $this->t('The width in pixels'),
    ];
    $settings['padding_top'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding-Top'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $this->getSetting('padding_top'),
      '#description' => $this->t('The top padding in pixels'),
    ];
    $settings['padding_right'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding-Right'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $this->getSetting('padding_right'),
      '#description' => $this->t('The right padding in pixels'),
    ];
    $settings['padding_bottom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding-Bottom'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $this->getSetting('padding_bottom'),
      '#description' => $this->t('The bottom padding in pixels'),
    ];
    $settings['padding_left'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding-Left'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $this->getSetting('padding_left'),
      '#description' => $this->t('The left padding in pixels'),
    ];
    $settings['show_value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show value'),
      '#default_value' => $this->getSetting('show_value'),
      '#description' => $this->t('Show the actual value in addition to the barcode'),
    ];
    return $settings + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = t('Type: %type', ['%type' => $this->getSetting('type')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $token_service = \Drupal::token();
    $generator = new BarcodeGenerator();
    foreach ($items as $delta => $item) {
      $suffix = str_replace(
        '+', 'plus', strtolower($this->getSetting('type'))
      );

      // Get information about the currently loaded entity from the route.
      $routeParameters = \Drupal::routeMatch()->getParameters();
      if ($routeParameters->count() > 0) {
        // We use the first parameter as dynamic indicator for the entity type.
        $entity_type_indicator = $routeParameters->keys()[0];
        $entity = \Drupal::routeMatch()->getParameter($entity_type_indicator);
        $has_entity = FALSE;
        if (is_object($entity) && $entity instanceof ContentEntityInterface) {
          $has_entity = TRUE;
        }
      }

      $elements[$delta] = [
        '#theme' => 'barcode__' . $suffix,
        '#attached' => [
          'library' => [
            'barcodes/' . $suffix,
          ],
        ],
        '#type' => $this->getSetting('type'),
        '#value' => $token_service->replace(
          $this->viewValue($item),
          $has_entity ? [$entity->getEntityType()->id() => $entity] : []
        ),
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
        '#color' => $this->getSetting('color'),
        '#padding_top' => $this->getSetting('padding_top'),
        '#padding_right' => $this->getSetting('padding_right'),
        '#padding_bottom' => $this->getSetting('padding_bottom'),
        '#padding_left' => $this->getSetting('padding_left'),
        '#show_value' => $this->getSetting('show_value'),
      ];

      try {
        $barcode = $generator->getBarcodeObj(
          $this->getSetting('type'),
          $this->viewValue($item),
          $this->getSetting('width'),
          $this->getSetting('height'),
          $this->getSetting('color'),
          [
            $this->getSetting('padding-top'),
            $this->getSetting('padding-right'),
            $this->getSetting('padding-bottom'),
            $this->getSetting('padding-left'),
          ]
        );
        $elements[$delta]['#svg'] = $barcode->getSvgCode();
      }
      catch (\Exception $e) {
        /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
        $logger = \Drupal::service('logger.factory')->get('barcodes');
        $logger->error(
          'Error: @error, given: @value',
          [
            '@error' => $e->getMessage(),
            '@value' => $this->viewValue($item),
          ]
        );
      }
    }
    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    return Html::escape($item->value);
  }

}
