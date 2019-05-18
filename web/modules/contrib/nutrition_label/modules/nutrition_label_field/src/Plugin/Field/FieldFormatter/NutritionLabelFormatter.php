<?php

namespace Drupal\nutrition_label_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\NutritionLabelItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for 'NutritionLabel Field formatter' plugin implementation.
 *
 * @FieldFormatter(
 *   id = "nutrition_label",
 *   label = @Translation("Nutrition Label"),
 *   field_types = {
 *     "nutrition_label"
 *   }
 * )
 */
class NutritionLabelFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'allowFDARounding' => true,
      'allowNoBorder' => false,
      'hideTextboxArrows' => false,
      'scrollLongItemNamePixel' => 34,
      'showServingUnitQuantity' => 'textbox',
      'showServingUnitQuantityTextbox' => true,
      'width' => 'auto',
      'scrollHeightComparison' => NULL,
      'scrollLongItemNamePixel' => NULL,
      'scrollDisclaimerHeightComparison' => NULL,
      'ingredientLabel' => NULL,
      'valueDisclaimer' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['allowNoBorder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide border'),
      '#default_value' => $this->getSetting('allowNoBorder'),
    ];

    $form['allowFDARounding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use FDA Rounding'),
      '#default_value' => $this->getSetting('allowFDARounding'),
    ];

    $form['showServingUnitQuantity'] = [
      '#type' => 'select',
      '#title' => $this->t('Serving Unit Quantity Display'),
      '#description' => $this->t('With the textbox options, the user can change the quantity to see dynamic nutrition values. The Accessible Text option causes the quantity to be visually hidden, but it is read by screen readers.'),
      '#options' => [
        'textbox' => $this->t('Textbox with arrows'),
        'textbox_only' => $this->t('Textbox without arrows'),
        'text' => $this->t('Text'),
        'accessible' => $this->t('Accessible Text'),
        'hidden' => $this->t('Hidden'),
      ],
      '#default_value' => $this->getSetting('showServingUnitQuantity'),
    ];

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('The width in pixels, or <em>auto</em>.'),
      '#default_value' => $this->getSetting('width'),
    ];

    $form['scrollHeightComparison'] = [
      '#type' => 'number',
      '#title' => $this->t('Scroll Ingredients If Height Exceeds'),
      '#description' => $this->t('The height in pixels.'),
      '#min' => 0,
      '#max' => 9999,
      '#step' => 1,
      '#default_value' => $this->getSetting('scrollHeightComparison'),
    ];

    $form['scrollLongItemNamePixel'] = [
      '#type' => 'number',
      '#title' => $this->t('Scroll Item Name If Height Exceeds'),
      '#description' => $this->t('Set to 0 to disable.'),
      '#description' => $this->t('The height in pixels.'),
      '#min' => 0,
      '#max' => 9999,
      '#step' => 1,
      '#default_value' => $this->getSetting('scrollLongItemNamePixel'),
      '#attributes' => [ 'placeholder' => '34' ],
    ];

    $form['scrollDisclaimerHeightComparison'] = [
      '#type' => 'number',
      '#title' => $this->t('Scroll Disclaimer If Height Exceeds'),
      '#description' => $this->t('The height in pixels.'),
      '#min' => 0,
      '#max' => 9999,
      '#step' => 1,
      '#default_value' => $this->getSetting('scrollDisclaimerHeightComparison'),
    ];

    $form['ingredientLabel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Override Ingredients Label'),
      '#default_value' => $this->getSetting('ingredientLabel'),
      '#attributes' => [ 'placeholder' => 'INGREDIENTS:' ],
    ];

    $form['valueDisclaimer'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Disclaimer Text'),
      '#default_value' => $this->getSetting('valueDisclaimer'),
      '#attributes' => [ 'placeholder' => 'Please note that these nutrition values are estimated based on our standard serving portions.' ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('allowNoBorder')) {
      $summary[] = $this->t('Hide border');
    }
    if ($this->getSetting('allowFDARounding')) {
      $summary[] = $this->t('Use FDA Rounding');
    }
    if ($showServingUnitQuantity = $this->getSetting('showServingUnitQuantity')) {
      // @todo: refactor
      $options = [
        'textbox' => $this->t('Textbox with arrows'),
        'textbox_only' => $this->t('Textbox without arrows'),
        'text' => $this->t('Text'),
        'accessible' => $this->t('Accessible Text'),
        'hidden' => $this->t('Hidden'),
      ];
      $summary[] = $this->t('Serving Unit Quantity Display: @display', ['@display' => $options[$showServingUnitQuantity]]);
    }
    if ($setting = $this->getSetting('width')) {
      $summary[] = $this->t('Width: @setting', ['@setting' => $setting . ($setting === 'auto' ? '' : ' px')]);
    }
    if ($setting = $this->getSetting('scrollHeightComparison')) {
      $summary[] = $this->t('Scroll Ingredients If Height Exceeds @setting px', ['@setting' => $setting]);
    }
    if ($setting = $this->getSetting('scrollLongItemNamePixel')) {
      $summary[] = $this->t('Scroll Item Name If Height Exceeds @setting px', ['@setting' => $setting]);
    }
    if ($setting = $this->getSetting('scrollDisclaimerHeightComparison')) {
      $summary[] = $this->t('Scroll Disclaimer If Height Exceeds @setting px', ['@setting' => $setting]);
    }
    if ($setting = $this->getSetting('ingredientLabel')) {
      $summary[] = $this->t('Override Ingredients Label: @setting', ['@setting' => $setting]);
    }
    if ($setting = $this->getSetting('valueDisclaimer')) {
      $summary[] = $this->t('Custom disclaimer');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // @todo: #attach settings? Or render as table?
    // @todo: #attach library
    $elements['#attached']['library'][] = 'nutrition_label/drupal.nutrition_label';
    foreach ($items as $delta => $item) {
      $settings = array_merge(array_map(function ($prop) {return $prop->getValue();}, $item->getProperties(true)), $this->getSettings());
      if (!empty($settings['showServingUnitQuantity'])) {
        switch ($settings['showServingUnitQuantity']) {
          case 'textbox':
          default:
            break;
          case 'textbox_only':
            $settings['showServingUnitQuantityTextbox'] = true;
            $settings['hideTextboxArrows'] = true;
            break;
          case 'text':
            $settings['showServingUnitQuantity'] = true;
            $settings['showServingUnitQuantityTextbox'] = false;
            break;
          case 'accessible':
            $settings['showServingUnitQuantity'] = true;
            $settings['showServingUnitQuantityAccessible'] = true;
            $settings['showServingUnitQuantityTextbox'] = false;
            break;
          case 'hidden':
            $settings['showServingUnitQuantity'] = false;
            $settings['showServingUnitQuantityTextbox'] = false;
            break;
        }
      }
      $elements[$delta] = [
        '#theme' => 'nutrition_label',
        '#settings' => $settings,
      ];
    }

    return $elements;
  }


}
