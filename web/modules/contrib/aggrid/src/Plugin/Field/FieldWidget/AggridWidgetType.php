<?php

namespace Drupal\aggrid\Plugin\Field\FieldWidget;

use Drupal\aggrid\Entity;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'aggrid' widget.
 *
 * @FieldWidget(
 *   id = "aggrid_widget_type",
 *   label = @Translation("ag-Grid edit mode"),
 *   field_types = {
 *     "aggrid"
 *   }
 * )
 */
class AggridWidgetType extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    /*\\
    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
    $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
     */
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('aggrid.general');

    $field_name = $this->fieldDefinition->getName();

    $item_id = Html::getUniqueId("ht-$field_name-$delta");

    if ((isset($form['#parents'][0]) && $form['#parents'][0] == 'default_value_input') || empty($items[$delta]->aggrid_id)) {

      $options = [];

      $aggridEntities = \Drupal::entityTypeManager()->getStorage('aggrid')->loadMultiple();

      foreach ($aggridEntities as $aggridEntity) {
        $options[$aggridEntity->id()] = $aggridEntity->label();
      }

      $element['aggrid_id'] = [
        '#type' => 'select',
        '#empty_option' => ' - ' . $this->t('Select') . ' - ',
        '#options' => $options,
        '#title' => $this->fieldDefinition->label() . ' - ' . $this->t('ag-Grid Config Entity'),
        '#description' => $this->t('Choose an ag-Grid Config Entity. *Once saved, this cannot be modified.'),
        '#default_value' => isset($items[$delta]->aggrid_id) ? $items[$delta]->aggrid_id : NULL,
      ];

      $widget = [];

    }
    else {

      $element['container'] = [
        '#suffix' => $items[$delta]->description . ' <div class="aggrid-widget ag-theme-balham" id="' . $item_id . '_aggrid" data-edit="true" data-target="' . $item_id . '"></div><div id="' . $item_id . '_error" title="Validation Error(s)"  hidden="hidden"></div>',
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
          array_push($element['container']['#attached']['library'],
            'aggrid/ag-grid-enterprise');
        }
        else {
          array_push($element['container']['#attached']['library'],
            'aggrid/ag-grid-enterprise.cdn');
        }
      }
      else {
        if ($config->get('source') == "local") {
          array_push($element['container']['#attached']['library'],
            'aggrid/ag-grid-community');
        }
        else {
          array_push($element['container']['#attached']['library'],
            'aggrid/ag-grid-community.cdn');
        }
      }

      $aggridEntity = Entity\Aggrid::load($items[$delta]->aggrid_id);
      $aggridDefault = json_decode($aggridEntity->get('aggridDefault'));

      if (empty($items[$delta]->value) || $items[$delta]->value == '{}') {
        $aggridValue = json_encode($aggridDefault->rowData);
      }
      else {
        $aggridValue = $items[$delta]->value;
      }

      $widget['aggrid_id'] = [
        '#type' => 'hidden',
        '#default_value' => isset($items[$delta]->aggrid_id) ? $items[$delta]->aggrid_id : NULL,
      ];

      // Check if rowSettings is available.
      if (!empty($aggridDefault->rowSettings)) {
        $aggridRowSettings =json_encode($aggridDefault->rowSettings);
      }
      else {
        $aggridRowSettings = '{}';
      }

      // ag-Grid Settings
      $element['container']['#attached']['drupalSettings']['aggrid']['settings'][$item_id]['columnDefs'] = json_encode($aggridDefault->columnDefs);
      $element['container']['#attached']['drupalSettings']['aggrid']['settings'][$item_id]['rowSettings'] = $aggridRowSettings;
      $element['container']['#attached']['drupalSettings']['aggrid']['settings'][$item_id]['addOptions'] = $aggridEntity->get('addOptions');

      $widget['value'] = [
        '#type' => 'hidden',
        '#attributes' => ['id' => [$item_id . '_rowData']],
        '#default_value' => Xss::filter($aggridValue),
      ];

    }

    $element = $element + $widget;
    return $element;
  }

}
