<?php
namespace Drupal\map_object_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\map_object_field\Plugin\Field\TMapOptions;

/**
 * Plugin implementation of the 'map_object_field_default' widget.
 *
 * @FieldWidget(
 *   id = "map_object_field_default",
 *   label = @Translation("Map Object Field default"),
 *   field_types = {
 *     "map_object_field"
 *   }
 * )
 */
class MapObjectFieldDefaultWidget extends WidgetBase {

  use TMapOptions;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'lat' => 0,
      'lng' => 0,
      'zoom' => 1,
      'map_type' => 'terrain',
      'object_types' => \Drupal::config('map_object_field.settings')
        ->get('drawing_object_types'),
      'max_objects_number' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($settings = $this->getSettings()) {
      $summary[] = $this->t(
        'Map type: @maptype',
        ['@maptype' => ucfirst($settings['map_type'])]
      );
      $allowed_object_types = $settings['object_types'];
      $summary[] = $this->t('Allowed object types for drawing:') . implode(', ', $allowed_object_types);
      if (!empty($settings['max_objects_number'])) {
        $summary[] = $this->t('Maximum number of objects: @num', ['@num' => $settings['max_objects_number']]);
      }
      else {
        $summary[] = $this->t('Maximum number of objects: @num', ['@num' => 'unlimited']);
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $settings_form_element['map_default_widget_heading_markup'] = [
      '#type' => 'item',
      '#description' => $this->t('Configure how the map is displayed on the <strong>edit form</strong>. Drag and zoom the map to customize.'),
    ];

    $settings_form_element['map'] = [
      '#type' => 'item',
      '#markup' => '<div class="map_default_widget_settings_map">map</div>',
      '#attached' => [
        'library' => [],
      ],
      '#prefix' => '<section class="map_default_widget_settings">',
      '#suffix' => '</section>',
    ];

    /** @var \Drupal\map_object_field\Service\MapObjectLibInterface $map_bject_field_lib */
    $map_bject_field_lib = \Drupal::service('map_object_field_lib');
    foreach ($map_bject_field_lib->getLibrariesForWidgetConfig() as $lib) {
      $settings_form_element['map']['#attached']['library'][] = $lib;
    }

    $settings_form_element['lat'] = [
      // Dws means default_widget_settings.
      '#id' => 'map_dws_lat',
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#size' => '20',
      '#maxlength' => '50',
      '#default_value' => !empty($settings['lat']) ? $settings['lat'] : 0,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
      '#prefix' => '<section class="map_default_widget_settings">',
    ];
    $settings_form_element['lng'] = [
      '#id' => 'map_dws_lng',
      '#type' => 'textfield',
      '#title' => $this->t('Longtitude'),
      '#size' => '20',
      '#maxlength' => '50',
      '#default_value' => !empty($settings['lng']) ? $settings['lng'] : 0,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
    ];
    $settings_form_element['zoom'] = [
      '#id' => 'map_dws_zoom',
      '#type' => 'textfield',
      '#title' => $this->t('Zoom'),
      '#size' => '20',
      '#maxlength' => '20',
      '#default_value' => !empty($settings['zoom']) ? $settings['zoom'] : 1,
      '#attributes' => [
        'readonly' => 'readonly',
      ],
    ];

    $settings_form_element['map_type'] = [
      '#id' => 'map_dws_map_type',
      '#type' => 'radios',
      '#title' => t('Map Display Type'),
      '#description' => $this->t('<a target="_blank" href="https://developers.google.com/maps/documentation/javascript/maptypes">https://developers.google.com/maps/documentation/javascript/maptypes</a>'),
      '#options' => $this->getMapTypesWithLabels(),
      '#default_value' => !empty($settings['map_type']) ? $settings['map_type'] : $this->getMapTypes()[0],
      '#attributes' => [
        'class' => ['map_type'],
      ],
    ];

    $settings_form_element['object_types'] = [
      '#id' => 'map_dws_object_types',
      '#type' => 'checkboxes',
      '#title' => $this->t('Map object types'),
      '#description' => $this->t('Object types available for drawing. More info: <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/overlays">https://developers.google.com/maps/documentation/javascript/overlays</a>'),
      '#collapsible' => FALSE,
      '#options' => $this->getMapObjectTypesWithLabels(),
      '#default_value' => !empty($settings['object_types']) ? $settings['object_types'] : $this->getMapObjectTypes(),

    ];

    $settings_form_element['max_objects_number'] = [
      '#title' => $this->t('Maximum number of objects allowed for drawing'),
      '#description' => $this->t('Define integer number or leave this field empty if unlimited number of objects is required'),
      '#type' => 'textfield',
      '#default_value' => isset($settings['max_objects_number']) ? $settings['max_objects_number'] : '',
      '#size' => 10,
      '#suffix' => '</section>',
    ];

    return $settings_form_element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $widget_settings = $this->getSettings();

    $element['map_object_name'] = [
      '#title' => $this->t('Map Object Name'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->map_object_name) ? $items[$delta]->map_object_name : '',
      '#prefix' => '<section class="map-object-field-default-widget">',
    ];

    $object_types = array_intersect($this->getMapObjectTypes(), $widget_settings['object_types']);

    if (isset($items[$delta]->map_type)) {
      $map_type = $items[$delta]->map_type;
    }
    elseif (!empty($widget_settings['map_type'])) {
      $map_type = $widget_settings['map_type'];
    }
    else {
      $map_type = 'terrain';
    }
    $element['map_type'] = [
      '#type' => 'hidden',
      '#default_value' => $map_type,
      '#attributes' => [
        'class' => ['map_type'],
      ],
    ];

    // Set default for map_object_data.
    $field_name = $this->fieldDefinition->getName();
    $input_values = $form_state->getUserInput();
    // $form_state->isProcessingInput();
    if (isset($input_values[$field_name][$delta]['map_object_data'])) {
      $map_object_field_default = $input_values[$field_name][$delta]['map_object_data'];
    }
    else {
      /** @var \Drupal\map_object_field\MapObject\MapObjectService $map_object_service */
      $map_object_service = \Drupal::service('map_object.service');
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $items->getEntity();
      $map_object_field_default = $map_object_service->getMapObjectsByFieldDataAsString(
        $entity->getEntityType()->id(),
        $entity->id(),
        $entity->getEntityType()
          ->isRevisionable() ? $entity->getRevisionId() : $entity->id(),
        $delta
      );
    }

    $element['map_object_data'] = [
      '#type' => 'hidden',
      '#default_value' => $map_object_field_default,
      '#attributes' => [
        'class' => ['map_object_data'],
      ],
    ];

    // Fill map init settings.
    if (isset($items[$delta]->map_center_lat)) {
      $map_center_lat = $items[$delta]->map_center_lat;
    }
    elseif (!empty($widget_settings['lat'])) {
      $map_center_lat = $widget_settings['lat'];
    }
    else {
      $map_center_lat = 0;
    }
    $element['map_center_lat'] = [
      '#type' => 'hidden',
      '#default_value' => $map_center_lat,
      '#attributes' => [
        'class' => ['map_center_lat'],
      ],
    ];

    if (isset($items[$delta]->map_center_lng)) {
      $map_center_lng = $items[$delta]->map_center_lng;
    }
    elseif (!empty($widget_settings['lng'])) {
      $map_center_lng = $widget_settings['lng'];
    }
    else {
      $map_center_lng = 0;
    }
    $element['map_center_lng'] = [
      '#type' => 'hidden',
      '#default_value' => $map_center_lng,
      '#attributes' => [
        'class' => ['map_center_lng'],
      ],
    ];

    if (isset($items[$delta]->map_zoom)) {
      $map_zoom = $items[$delta]->map_zoom;
    }
    elseif (!empty($widget_settings['zoom'])) {
      $map_zoom = $widget_settings['zoom'];
    }
    else {
      $map_zoom = 1;
    }
    $element['map_zoom'] = [
      '#title' => $this->t('Map Zoom'),
      '#type' => 'hidden',
      '#default_value' => $map_zoom,
      '#attributes' => [
        'class' => ['map_zoom'],
      ],
    ];

    $element['overlays-list'] = [
      '#theme' => 'map_default_widget_overlay_info_item',
      '#suffix' => '</section>',
    ];

    // Initial display settings.
    $map_options = [];
    if (!empty($items[$delta]->map_center_lat) && !empty($items[$delta]->map_center_lng)) {
      $map_options['init-center-lat'] = $items[$delta]->map_center_lat;
      $map_options['init-center-lng'] = $items[$delta]->map_center_lng;
    }
    else {
      $map_options['init-center-lat'] = $widget_settings['lat'];
      $map_options['init-center-lng'] = $widget_settings['lng'];
    }
    if (!empty($items[$delta]->map_zoom)) {
      $map_options['init-zoom'] = $items[$delta]->map_zoom;
    }
    else {
      $map_options['init-zoom'] = $widget_settings['zoom'];
    }
    $map_options['init-map-type'] = $map_type;
    $map_options['allowed-object-types'] = implode(',', $object_types);
    $map_options['max-objects-number'] = $widget_settings['max_objects_number'];

    $preview = [
      '#theme' => 'map_default_widget_preview',
      '#mapOptions' => $map_options,
    ];
    $element['preview'] = [
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => render($preview),
      '#prefix' => '<section class="map-object-field-default-widget">',
      '#suffix' => '</section>',
    ];

    $element += [
      '#type' => 'fieldset',
      '#title' => $this->t('Map'),
    ];

    /** @var \Drupal\map_object_field\Service\MapObjectLibInterface $map_bject_field_lib */
    $map_bject_field_lib = \Drupal::service('map_object_field_lib');
    foreach ($map_bject_field_lib->getLibrariesForWidget() as $lib) {
      $element['#attached']['library'][] = $lib;
    }

    return $element;
  }

}
