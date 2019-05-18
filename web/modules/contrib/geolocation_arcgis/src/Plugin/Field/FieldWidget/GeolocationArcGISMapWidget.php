<?php

namespace Drupal\geolocation_arcgis\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\Plugin\Field\FieldWidget\GeolocationMapWidgetBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Plugin implementation of the 'geolocation_arcgis' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_arcgis",
 *   label = @Translation("Geolocation ArcGIS API - Geocoding and Map"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationArcGISMapWidget extends GeolocationMapWidgetBase {
    /**
     * {@inheritdoc}
     */
    protected $mapProviderId = 'arcgis_maps';

    /**
     * {@inheritdoc}
     */
    protected $mapProviderSettingsFormId = 'arcgis_maps_settings';

    /**
     * {@inheritdoc}
     */
    public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
        $element = parent::form($items, $form, $form_state, $get_delta);

        $element['#prefix'] = $this->t('<strong>@field</strong>', ['@field' => $this->fieldDefinition->getLabel()]);
        $element['#attributes']['data-widget-type'] = 'arcgis';
        $element['#attributes']['data-field'] = $this->fieldDefinition->getName();

        $element['#attached'] = BubbleableMetadata::mergeAttachments(
            $element['#attached'],
            [
                'library' => [
                    'geolocation_arcgis/arcgis_maps.widget',
                ],
            ]
        );
        $element['map']['#settings']['showGeocoder'] = TRUE;
        $element['map']['#settings']['placeholder'] = t('Search for a location');

        return $element;
    }

}
