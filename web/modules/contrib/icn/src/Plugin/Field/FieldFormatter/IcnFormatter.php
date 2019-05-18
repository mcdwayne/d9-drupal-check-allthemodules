<?php

namespace Drupal\icn\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;


/**
 * Plugin implementation of the 'icn_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "icn_formatter",
 *   label = @Translation("In Content Navigation"),
 *   field_types = {
 *     "icn_field_type"
 *   }
 * )
 */
class IcnFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'hide' => 0,
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        $element['hide'] = [
            '#title' => $this->t('hide title'),
            '#type' => 'checkbox',
            '#default_value' => $this->getSetting('hide'),
        ]
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    
    if($this->getSetting('hide') == 1) {
        $summary[] = $this->t('Title is hide.');            
    }
    else {
        $summary[] = $this->t('Title is show.');
    }

    return $summary;
  }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {
        $elements = [];
        $hide = $this->getSetting('hide');
        
        foreach ($items as $delta => $item) {
            // Print ICN item
            $elements[$delta] = [
                '#theme' => 'icn_item',
                '#value' => $item->title,
                '#anchor' => $item->anchor,
                '#hide' => ($hide == 1) ? 'hide-title' : '',
            ];
        }

        return $elements;
    }
}
