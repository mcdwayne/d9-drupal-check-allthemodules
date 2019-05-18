<?php

namespace Drupal\aggrid\Plugin\Field\FieldFormatter;

use Drupal\aggrid\Entity;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'aggrid_formatter_type' formatter.
 *
 * @FieldFormatter(
 *   id = "aggrid_formatter_type",
 *   label = @Translation("ag-Grid view mode"),
 *   field_types = {
 *     "aggrid"
 *   }
 * )
 */
class AggridFormatterType extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $config = $config = \Drupal::config('aggrid.general');
      $field_name = $this->fieldDefinition->getName();

      if (empty($items[$delta]->aggrid_id)) {

        $elements[$delta]['container'] = [
          '#plain_text' => $this->t('Missing ag-Grid Config Entity'),
          '#prefix' => '<div class="aggrid-widget-missing">',
          '#suffix' => '</div>',
        ];

      }
      else {

        $item_id = Html::getUniqueId("ht-$field_name-$delta");

        $aggridEntity = Entity\Aggrid::load($items[$delta]->aggrid_id);
        $aggridDefault = json_decode($aggridEntity->get('aggridDefault'));

        if (empty($items[$delta]->value) || $items[$delta]->value == '{}') {
          $aggridValue = json_encode($aggridDefault->rowData);
        }
        else {
          $aggridValue = $items[$delta]->value;
        }

        $elements[$delta]['container'] = [
          '#suffix' => '<div class="aggrid-widget ag-theme-balham" id="' . $item_id . '_aggrid"
            data-edit="false" data-target="' . $item_id . '"></div>',
          '#attributes' => ['class' => ['aggrid-widget']],
          '#attached' => [
            'library' => [
              'aggrid/widget',
            ],
          ],
        ];

        // Load the js... either local or cdn depending on configuration.
        if ($config->get('version') == "Enterprise") {
          if ($config->get('source') == "local") {
            array_push($elements[$delta]['container']['#attached']['library'],
              'aggrid/ag-grid-enterprise');
          }
          else {
            array_push($elements[$delta]['container']['#attached']['library'],
              'aggrid/ag-grid-enterprise.cdn');
          }
        }
        else {
          if ($config->get('source') == "local") {
            array_push($elements[$delta]['container']['#attached']['library'],
              'aggrid/ag-grid-community');
          }
          else {
            array_push($elements[$delta]['container']['#attached']['library'],
              'aggrid/ag-grid-community.cdn');
          }
        }

        // Check if rowSettings is available.
        if (!empty($aggridDefault->rowSettings)) {
          $aggridRowSettings =json_encode($aggridDefault->rowSettings);
        }
        else {
          $aggridRowSettings = '';
        }

        $elements[$delta]['container']['#attached']['drupalSettings']['aggrid']['settings'][$item_id]['columnDefs'] = json_encode($aggridDefault->columnDefs);
        $elements[$delta]['container']['#attached']['drupalSettings']['aggrid']['settings'][$item_id]['rowSettings'] = $aggridRowSettings;
        $elements[$delta]['container']['#attached']['drupalSettings']['aggrid']['settings'][$item_id]['addOptions'] = $aggridEntity->get('addOptions');

        $elements[$delta]['value'] = [
          '#type' => 'hidden',
          '#attributes' => ['id' => [$item_id . '_rowData']],
          '#value' => Xss::filter($aggridValue),
        ];

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
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
