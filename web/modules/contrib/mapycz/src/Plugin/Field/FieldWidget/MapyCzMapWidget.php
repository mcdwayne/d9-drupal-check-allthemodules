<?php

namespace Drupal\mapycz\Plugin\Field\FieldWidget;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mapycz\MapyCzCore;

/**
 * Plugin implementation of the 'mapycz_map' widget.
 *
 * @FieldWidget(
 *   id = "mapycz_map",
 *   label = @Translation("Mapy CZ - Map"),
 *   field_types = {
 *     "mapycz"
 *   }
 * )
 */
class MapyCzMapWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];

    $settings['width'] = '100%';
    $settings['height'] = '350px';
    $settings['type'] = 'basic';

    $settings += parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element['width'] = [
      '#title' => 'Šířka',
      '#type' => 'textfield',
      '#default_value' => $settings['width'],
      '#description' => 'Vložte velikost a jednotky, např 200px nebo 100%.',
    ];

    $element['height'] = [
      '#title' => 'Výška',
      '#type' => 'textfield',
      '#default_value' => $settings['height'],
      '#description' => 'Vložte velikost a jednotky, např 200px nebo 100%.',
    ];

    $element['type'] = [
      '#title' => 'Typ mapy',
      '#type' => 'select',
      '#options' => MapyCzCore::getMapTypeOptions(),
      '#default_value' => $settings['type'],
      '#description' => $this->t("Choose default map type to show. If map in a node has it's own type set, it will be used."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];
    $summary[] = $this->t('Map type: @type', ['@type' => $settings['type']]);
    $summary[] = $this->t('Width: @width', ['@width' => $settings['width']]);
    $summary[] = $this->t('Height: @height', ['@height' => $settings['height']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    foreach ($violations as $violation) {
      if ($violation->getMessageTemplate() == 'This value should not be null.') {
        $form_state->setErrorByName($items->getName(), $this->t('No location has been selected yet for required field %field.', ['%field' => $items->getFieldDefinition()->getLabel()]));
      }
    }
    parent::flagErrors($items, $violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    // Place default center somewhere in Czech Republic with some zoom.
    $default_center_lat = 49.790097842758854;
    $default_center_lng = 15.593198915987529;
    $default_center_zoom = 7;

    $element['location_lat'] = [
      '#type' => 'hidden',
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->lat)) ? $items[$delta]->lat : NULL,
      '#maxlength' => 255,
      '#required' => $this->fieldDefinition->isRequired(),
      '#attributes' => [
        'class' => ['mapycz-input-location-lat'],
      ],
    ];

    $element['location_lng'] = [
      '#type' => 'hidden',
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->lng)) ? $items[$delta]->lng : NULL,
      '#maxlength' => 255,
      '#required' => $this->fieldDefinition->isRequired(),
      '#attributes' => [
        'class' => ['mapycz-input-location-lng'],
      ],
    ];

    $element['center_lat'] = [
      '#type' => 'hidden',
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->data['center_lat'])) ? $items[$delta]->data['center_lat'] : $default_center_lat,
      '#maxlength' => 255,
      '#attributes' => [
        'class' => ['mapycz-input-center-lat'],
      ],
    ];

    $element['center_lng'] = [
      '#type' => 'hidden',
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->data['center_lng'])) ? $items[$delta]->data['center_lng'] : $default_center_lng,
      '#maxlength' => 255,
      '#attributes' => [
        'class' => ['mapycz-input-center-lng'],
      ],
    ];

    $element['zoom'] = [
      '#type' => 'hidden',
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->data['zoom'])) ? $items[$delta]->data['zoom'] : $default_center_zoom,
      '#maxlength' => 255,
      '#attributes' => [
        'class' => ['mapycz-input-zoom'],
      ],
    ];

    $element['type'] = [
      '#type' => 'hidden',
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->data['type'])) ? $items[$delta]->data['type'] : $settings['type'],
      '#maxlength' => 255,
      '#attributes' => [
        'class' => ['mapycz-input-type'],
      ],
    ];

    $map_id = uniqid('mapycz-widget-' . $delta);
    $element['map'] = [
      '#theme' => 'mapycz_map',
      '#map_id' => $map_id,
      '#center' => [
        'lat' => $element['center_lat']['#default_value'],
        'lng' => $element['center_lng']['#default_value'],
      ],
      '#zoom' => $element['zoom']['#default_value'],
      '#type' => $element['type']['#default_value'],
      '#markers' => [],
      '#width' => $settings['width'],
      '#height' => $settings['height'],
      '#attached' => [
        'library' => [
          'mapycz/mapycz.backend',
        ],
      ],
    ];

    // Set markers if value is not empty.
    if (isset($items[$delta]->lng) && isset($items[$delta]->lat)) {
      $element['map']['#markers'][] = [
        'lat' => $element['location_lat']['#default_value'],
        'lng' => $element['location_lng']['#default_value'],
      ];
    }

    // Wrap the whole form in a container.
    $element += [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'id' => $map_id . '-wrapper',
            'class' => 'mapycz-wrapper',
          ],
        ],
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as $delta => $item_values) {
      $values[$delta]['lat'] = $item_values['location_lat'];
      $values[$delta]['lng'] = $item_values['location_lng'];
      $values[$delta]['data']['center_lat'] = $item_values['center_lat'];
      $values[$delta]['data']['center_lng'] = $item_values['center_lng'];
      $values[$delta]['data']['zoom'] = $item_values['zoom'];
      $values[$delta]['data']['type'] = $item_values['type'];
    }

    return $values;
  }

}
