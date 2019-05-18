<?php

namespace Drupal\geolocation_arcgis\Plugin\geolocation\Geocoder;

use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\geolocation\GeocoderBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\geolocation_arcgis\ArcGISManager;

/**
 * Geocoder using the ArcGIS API.
 *
 * @Geocoder(
 *   id = "arcgis_api",
 *   name = @Translation("Google Places API"),
 *   description = @Translation("This plugin requires an API token to store geocoded results."),
 *   locationCapable = true,
 *   boundaryCapable = true,
 * )
 */
class ArcGISAPI extends GeocoderBase {
    /**
     * {@inheritdoc}
     */
    public function attachments($input_id) {
        $attachments = parent::$attachments($input_id);
        $attachments = BubbleableMetadata::mergeAttachments($attachments, [
            'library' => [
                'geolocation_arcgis/geocoder.arcgisapi',
            ],
        ]);
        return $attachments;
    }

    public function formAttachGeocoder(array &$render_array, $element_name) {
        $settings = $this->getSettings();

        $render_array['geolocation_geocoder_arcgis_api'] = [
            '#type' => 'search',
            '#title' => $settings['label'],
            '#placeholder' => $settings['description'],
            '#description' => $settings['description'],
            '#description_display' => 'after',
            '#size' => '25',
            '#attributes' => [
                'class' => [
                    'form-autocomplete',
                    'geolocation-geocoder-arcgis-api',
                ],
                'data-source-identifier' => $element_name,
            ],
        ];
        $render_array['geolocation_geocoder_arcgis_api_state'] = [
            '#type' => 'hidden',
            '#default_value' => 1,
            '#attributes' => [
                'class' => [
                    'geolocation-geocoder-arcgis-api-state',
                ],
                'data-source-identifier' => $element_name,
            ],
        ];
        $render_array['#attached'] = $this->attachments($element_name);
    }

    /**
     * {@inheritdoc}
     */
    public function formValidateInput(FormStateInterface $form_state) {
        $validate = parent::formValidateInput($form_state);
        $input = $form_state->getUserInput();
        if (!empty($input['geolocation_geocoder_arcgis_api']) && empty($input['geolocation_geocoder_arcgis_api_state'])) {
            $location_data = $this->geocode($input['geolocation_geocoder_arcgis_api']);
            if (empty($location_data)) {
                $form_state->setErrorByName('geolocation_geocoder_arcgis_api', $this->t('Failed to geocode %input.', ['%input' => $input['geolocation_geocoder_arcgis_api']]));
                return FALSE;
            }
        }
        return $validate;
    }

    /**
     * {@inheritdoc}
     */
    public function formProcessInput(array &$input, $element_name) {
        $return = parent::formProcessInput($input, $element_name);
        if (!empty($input['geolocation_geocoder_arcgis_api']) && empty($input['geolocation_geocoder_arcgis_api_state'])) {
            $location_data = $this->geocode($input['geolocation_geocoder_arcgis_api']);
            if (empty($location_data)) {
                $input['geolocation_geocoder_arcgis_api_state'] = 0;
                return FALSE;
            }
            $input['geolocation_geocoder_arcgis_api'] = $location_data['address'];
            $input['geolocation_geocoder_arcgis_api_state'] = 1;
            return $location_data;
        }
        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function geocode($address) {
        if (empty($address)) {
            return FALSE;
        }
        $arcgis = \Drupal::service('geolocation_arcgis.manager');
        $token = $arcgis->getApiToken();
        $url = $arcgis->getGeocodeApiUrl();
        $url .= "/findAddressCandidates?f=json&SingleLine=" . $address;
        if (!empty($token)) {
            $url .= "&forStorage=true&token=" . $token;
        }
        $home = $arcgis->getHomeLocation();
        if (!empty($home)) {
            $url .= "&location=" . $home;
        }
        try {
            $opts = [
                'headers' => [
                    'Referer' => \Drupal::request()->getSchemeAndHttpHost()
                ]
            ];
            $result = Json::decode(\Drupal::httpClient()->request('GET', $url, $opts)->getBody());
        }
        catch (RequestException $e) {
            watchdog_exception('geolocation_arcgis', $e);
            return FALSE;
        }
        if (empty($result['candidates'])) {
            return FALSE;
        }

        return [
            'location' => [
              'lat' => $result['candidates'][0]['location']['y'],
              'lng' => $result['candidates'][0]['location']['x'],
            ],
            'boundary' => [
              'lat_north_east' => empty($result['candidates'][0]['extent']) ? $result['candidates'][0]['location']['y'] + 0.005 : $result['candidates'][0]['extent']['ymax'],
              'lng_north_east' => empty($result['candidates'][0]['extent']) ? $result['candidates'][0]['location']['x'] + 0.005 : $result['candidates'][0]['extent']['xmax'],
              'lat_south_west' => empty($result['candidates'][0]['extent']) ? $result['candidates'][0]['location']['y'] - 0.005 : $result['candidates'][0]['extent']['ymin'],
              'lng_south_west' => empty($result['candidates'][0]['extent']) ? $result['candidates'][0]['location']['x'] - 0.005 : $result['candidates'][0]['extent']['xmin'],
            ],
            'address' => empty($result['candidates'][0]['address']) ? '' : $result['candidates'][0]['address'],
        ];
    }
}
