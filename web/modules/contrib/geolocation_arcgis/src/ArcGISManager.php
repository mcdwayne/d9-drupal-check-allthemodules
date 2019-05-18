<?php

namespace Drupal\geolocation_arcgis;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\geolocation\GeocoderManager;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;

/**
 * 
 */
class ArcGISManager implements ArcGISManagerInterface {
    protected $configFactory;
    protected $config;
    protected $state;

    public static $GEOCODE_API_URL = 'http://geocode.arcgis.com/arcgis/rest/services/World/GeocodeServer/findAddressCandidates';

    public function __construct(ConfigFactoryInterface $configFactory, GeocoderManager $manager) {
        $this->configFactory = $configFactory;
        $this->config = $this->configFactory->get('geolocation_arcgis.settings');
        $this->state = \Drupal::state();
        $this->geocoderManager = $manager;
    }

    public function getMapTypes() {
        return [
            'streets' => t('Streets'),
            'satellite' => t('Satellite'),
            'hybrid' => t('Hybrid'),
            'topo' => t('Topographic'),
            'gray' => t('Grayscale'),
            'dark-gray' => t('Dark Grayscale'),
            'oceans' => t('Oceans'),
            'national-geographic' => t('National Geographic'),
            'terrain' => t('Terrain'),
            'osm' => t('OpenStreetMap'),
            'dark-gray-vector' => t('Dark Grayscale (Vector)'),
            'gray-vector' => t('Grayscale (Vector)'),
            'streets-vector' => t('Streets (Vector)'),
            'topo-vector' => t('Topographic (Vector)'),
            'streets-night-vector' => t('Streets Night (Vector)'),
            'streets-relief-vector' => t('Streets Relief (Vector)'),
            'streets-navigation-vector' => t('Streets Navigation (Vector)')
        ];
    }

    public function getGeocodeApiUrl() {
        return $this->config->get('geocode_proxy_url', static::$GEOCODE_API_URL);
    }

    public function getApiToken() {
        $token = $this->state->get('geolocation_arcgis.token');
        $expire = $this->state->get('geolocation_arcgis.expire');
        $time = \Drupal::time()->getRequestTime();
        if ($token == NULL || $expire == NULL || ($expire != NULL && $expire <= $time)) {
            $token = $this->generateToken();
            $this->state->set('geolocation_arcgis.token', $token['token']);
            $this->state->set('geolocation_arcgis.expire', $token['expire']);
            $token = $token['token'];
        }
        return $token;
    }

    public function getHomeLocation() {
        $lat = $this->config->get('home_location_coords.lat');
        $lng = $this->config->get('home_location_coords.lng');
        if ($lat !== NULL && $lng !== NULL) {
            return [
                'lat' => $lat,
                'lng' => $lng
            ];
        }
        return NULL;
    }

    public function setHomeLocation($location) {
        // @TODO: turn this into a config setting (home location)
        //-77.433814639881135,37.54082843519533
        try {
            $geocoder = $this->geocoderManager->getGeocoder('arcgis_api');
            if ($geocoder !== NULL && ($result = $geocoder->geocode($location))) {
                if ($result !== FALSE) {
                    $config = $this->configFactory->getEditable('geolocation_arcgis.settings');
                    $config->set('home_location_coords.lat', $result['location']['lat']);
                    $config->set('home_location_coords.lng', $result['location']['lng']);
                    $config->save();
                }
            }
        }
        catch (Exception $e) {
            watchdog_exception('geolocation_arcgis', $e);
        }
    }

    public function setHomeLocationCoords($latitude, $longitude) {
        $config = $this->configFactory->getEditable('geolocation_arcgis.settings');
        $config->set('home_location_coords.lat', $latitude);
        $config->set('home_location_coords.lng', $longitude);
        $config->save();
    }

    public function getBaseMapType() {
        return $this->config->get('map_type', 'streets-navigation-vector');
    }

    private function generateToken() {
        $info = [
            'token' => NULL,
            'expire' => NULL
        ];
        $id = $this->config->get('id');
        $secret = $this->config->get('secret');
        if (empty($id) || empty($secret)) {
            return $info;
        }
        $url = "https://www.arcgis.com/sharing/oauth2/token?client_id=" . $id;
        $url .= "&client_secret=" . $secret;
        $url .= "&grant_type=client_credentials&f=json";
        try {
            $result = Json::decode(\Drupal::httpClient()->request('GET', $url)->getBody());
            if ($result != NULL) {
                $time = \Drupal::time()->getRequestTime();
                $info['token'] = $result['access_token'];
                $info['expire'] = $time + $result['expires_in'];
            }
        }
        catch (RequestException $e) {
            watchdog_exception('geolocation_arcgis', $e);
        }
        return $info;
    }
}
