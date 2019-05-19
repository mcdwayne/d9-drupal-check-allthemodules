<?php

namespace Drupal\spatialfields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'spatialfields_geom' widget.
 *
 * @FieldWidget(
 *   id = "spatialwidget",
 *   module = "spatialfields",
 *   label = @Translation("Spatial Textfield"),
 *   field_types = {
 *     "spatialfields_geom",
 *   }
 * )
 */
class SpatialWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $element += [
      '#type' => 'textarea',
      '#description' =>t('for example "POINT (12.5 54.5)"'),
      '#default_value' => $value,
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];
    return ['value' => $element];
  }

 
  public function validate($element, FormStateInterface $form_state) {
    $fielddef = $this->fieldDefinition;
    $type = $fielddef->getSettings()['type'];
    
    
    $value = $element['#value'];
    
    if (strlen($value) === 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    if($type === 'line') {
      $this->validate_geom($element, $form_state, 'LineString', 'Line');
    }
    elseif ($type === 'point') {
      $this->validate_geom($element, $form_state, 'Point', 'Point');
    }
    elseif($type === 'polygon') {
      $this->validate_geom($element, $form_state, 'Polygon', 'Polygon');   
    }
  }

  private function validate_geom($element, FormStateInterface $form_state, $type, $output) {
    if(\geoPHP::load($element['#value'],'wkt')) {
      $geom = \geoPHP::load($element['#value'],'wkt');
      if($geom->geometryType() === $type) {
        return;
      } else {
        $form_state->setError($element, $this->t("Given geometry isn´t a ".$output));    
      }
    } else {
      $form_state->setError($element, $this->t("It isn't a Well Known Text for a ".$output));    
    } 
  }

}
