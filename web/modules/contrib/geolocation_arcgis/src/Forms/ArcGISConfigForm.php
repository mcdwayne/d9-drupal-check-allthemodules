<?php

namespace Drupal\geolocation_arcgis\Forms;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

class ArcGISConfigForm extends ConfigFormBase {
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $arcgis = \Drupal::service('geolocation_arcgis.manager');
        $config = $this->configFactory->get('geolocation_arcgis.settings');
        $form['#tree'] = TRUE;
        $form['id'] = [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $this->t('Client ID'),
            '#default_value' => $config->get('id'),
            '#description' => $this->t('Client ID used for generating access tokens. See the <a href=":url" target="_blank">ArcGIS developer site</a> for creating applications.', [
                ':url' => 'https://developers.arcgis.com/'
            ]),
        ];
        $form['secret'] = [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $this->t('Client secret'),
            '#default_value' => $config->get('secret'),
            '#description' => $this->t('Client Secret used for generating access tokens.')
        ];
        $form['map_type'] = [
            '#type' => 'select',
            '#options' => $arcgis->getMapTypes(),
            '#title' => $this->t('Default basemap type'),
            '#default_value' => $config->get('map_type'),
        ];
        $form['home_location'] = [
            '#type' => 'geolocation_map',
            '#prefix' => $this->t('<strong>Home location</strong>'),
            '#default_value' => $config->get('home_location'),
            '#maptype' => 'arcgis_maps',
            '#id' => Html::getUniqueId('home_location'),
            '#settings' => [
                'showGeocoder' => TRUE,
                'geocodeProxyUrl' => $config->get('geocode_proxy_url'),
                'placeholder' => $this->t('Search for a location'),
                'map_type' => $config->get('map_type')
            ],
            '#library' => ['geolocation_arcgis/arcgis_maps.config'],
            '#description' => $this->t('Sets the initial location when showing the ArcGIS map for geocoding. Also allows for more relevant geocoding results.')
        ];
        $home = NULL;
        if (!empty($config->get('home_location.lng') && !empty($config->get('home_location.lat')))) {
            $home = "coords:" . $config->get('home_location.lng') . ',' . $config->get('home_location.lat');
        }
        $form['home_location_details'] = [
            '#type' => 'hidden',
            '#default_value' => $home,
            '#attributes' => [
                'id' => Html::getUniqueId('home_location_details'),
            ]
        ];
        $form['advanced'] = [
            '#type' => 'details',
            '#title' => $this->t('Advanced configuration')
        ];
        $form['advanced']['geocode_proxy_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Geocode Service Proxy URL'),
            '#default_value' => $config->get('geocode_proxy_url'),
            '#description' => $this->t('ArcGIS Online allows the use of service proxies to hide the client ID/secret and to enable rate limiting per API service.')
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'geolocation_arcgis_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'geolocation_arcgis.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $home = $form_state->getValue('home_location_details');
        if (!empty($home)) {
            $service = \Drupal::service('geolocation_arcgis.manager');
            $currentConfig = $this->configFactory->get('geolocation_arcgis.settings');
            if (substr($home, 0, 7) !== "coords:") {
                // geocode the new home location to lat/lng
                $service->setHomeLocation($home);
            }
            else {
                $coords = explode(',', substr($home, 7));
                if ($currentConfig->get('home_location.lng') != $coords[0] && $currentConfig->get('home_location.lat') != $coords[1]) {
                    $service->setHomeLocationCoords($coords[1], $coords[0]);
                }
            }
        }
        $config = $this->configFactory()->getEditable('geolocation_arcgis.settings');
        $config->set('map_type', $form_state->getValue('map_type'));
        $config->set('id', $form_state->getValue('id'));
        $config->set('secret', $form_state->getValue('secret'));
        $config->set('geocode_proxy_url', $form_state->getValue(['advanced', 'geocode_proxy_url']));
        $config->save();
        // Confirmation on form submission.
        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
