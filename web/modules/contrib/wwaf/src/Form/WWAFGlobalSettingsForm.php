<?php
namespace Drupal\wwaf\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;

class WWAFGlobalSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wwaf_global_settings';
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wwaf.settings',
    ];
  }
  

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    
    $form_state->disableCache();
    
    $config = $this->config('wwaf.settings');
    
    $form['#attached']['library'][] = 'wwaf/admin';
    
    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API'),
      '#open' => TRUE,
    ];
    
    $api_key = \Drupal::config('geolocation.settings')->get('google_map_api_key');
    if (!$api_key) {
      $link = Link::createFromRoute('Configure Geolocation →', 'geolocation.settings')->toString();
      $form['api']['api_key'] = [
        '#prefix' => '<div class="api-title error">',
        '#markup' => '<h4>Configure the Google API Key first: </h4>' . $link,
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['api']['api_key'] = [
        '#prefix' => '<div class="api-title success">',
        '#markup' => '<h4>Google API Key is configured correctly: <small>('.$api_key.')</small></h4>',
        '#suffix' => '</div>',
      ];


      $form['api']['api_version'] = [
        '#type' => 'textfield',
        '#title' => $this->t('GoogleMaps Javascript API version'),
        '#default_value' => $config->get('api_version'),
        '#description' => $this->t('Leave empty for the latest stable Release of 3.x version'),
      ];


      $form['styling'] = [
        '#type' => 'details',
        '#title' => $this->t('Styling'),
        '#open' => TRUE,
      ];

      $def_marker___ = '/' . drupal_get_path('module', 'wwaf') .'/images/marker.svg';
      $form['styling']['marker_default'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Default marker file'),
        '#description' => 'Leave empty for default module\'s <img src="'.$def_marker___.'" width="15" /> <br /> (*.png, *.svg Files accepted)',
        '#accept' => 'image/png, image/svg+xml',
        '#default_value' => $config->get('marker_default'),
        '#upload_location' => 'public://wwaf',
        '#upload_validators' => [
          'file_validate_extensions' => ['png svg'],
          'file_validate_image_resolution' => ['38x38'],
        ],
      ];

      $form['styling']['marker_active_enable'] = [
        '#type' => 'checkbox',
        '#title' => 'Use active marker',
        '#description' => 'Enables the use ACTIVE marker when you click it',
        '#default_value' => $config->get('marker_active_enable'),
      ];

      $def_marker_on = '/' . drupal_get_path('module', 'wwaf') .'/images/marker-on.svg';
      $form['styling']['marker_active'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Default Active marker file'),
        '#description' => 'Leave empty for default module\'s <img src="'.$def_marker_on.'" width="15" /> <br /> (*.png, *.svg Files accepted)',
        '#accept' => 'image/png, image/svg+xml',
        '#default_value' => $config->get('marker_active'),
        '#upload_location' => 'public://wwaf',
        '#upload_validators' => [
          'file_validate_extensions' => ['png svg'],
          'file_validate_image_resolution' => ['38x38'],
        ],
        '#states' => [
          'visible' => ['input#edit-marker-active-enable' => array('checked' => TRUE)],
        ],
      ];

      $form['styling']['gmap_snazzy_style'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Snazzymap JSON'),
        '#description' => 'This is a Google maps style array in JSON format provided by snazzymaps.com',
        '#default_value' => $config->get('gmap_snazzy_style'),
      ];



      $form['clusters'] = [
        '#type' => 'details',
        '#title' => $this->t('Clusters'),
        '#open' => TRUE,
      ];

      $form['clusters']['gmap_clusters'] = [
        '#type' => 'checkbox',
        '#title' => 'Use clusters',
        '#description' => 'Enables the use of clusters on Markers of the google map',
        '#default_value' => $config->get('gmap_clusters'),
      ];

      $cl_64 = '/' . drupal_get_path('module', 'wwaf') .'/images/cluster-64.png';
      $form['clusters']['cluster_64'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Small cluster PNG file'),
        '#description' => 'Leave empty for default module\'s <img src="'.$cl_64.'" width="15" /> (64x64 px) <br> (*.png, *.svg Files accepted)',
        '#accept' => 'image/png, image/svg+xml',
        '#default_value' => $config->get('cluster_64'),
        '#upload_location' => 'public://wwaf',
        '#upload_validators' => [
          'file_validate_extensions' => ['png svg'],
          'file_validate_image_resolution' => ['64x64'],
        ],
        '#states' => [
          'visible' => [':input[name="gmap_clusters"]' => array('checked' => TRUE)],
        ],
      ];

      $cl_128 = '/' . drupal_get_path('module', 'wwaf') .'/images/cluster-64.png';
      $form['clusters']['cluster_128'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Medium cluster PNG file'),
        '#description' => 'Leave empty for default module\'s <img src="'.$cl_128.'" width="15" /> (128x128 px) <br> (*.png, *.svg Files accepted)',
        '#accept' => 'image/png, image/svg+xml',
        '#default_value' => $config->get('cluster_64'),
        '#upload_location' => 'public://wwaf',
        '#upload_validators' => [
          'file_validate_extensions' => ['png svg'],
          'file_validate_image_resolution' => ['128x128'],
        ],
        '#states' => [
          'visible' => [':input[name="gmap_clusters"]' => array('checked' => TRUE)],
        ],
      ];

      $cl_256 = '/' . drupal_get_path('module', 'wwaf') .'/images/cluster-64.png';
      $form['clusters']['cluster_256'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Large cluster PNG file'),
        '#description' => 'Leave empty for default module\'s <img src="'.$cl_256.'" width="15" /> (256x256 px) <br> (*.png, *.svg Files accepted)',
        '#accept' => 'image/png, image/svg+xml',
        '#default_value' => $config->get('cluster_64'),
        '#upload_location' => 'public://wwaf',
        '#upload_validators' => [
          'file_validate_extensions' => ['png svg'],
          'file_validate_image_resolution' => ['256x256'],
        ],
        '#states' => [
          'visible' => [':input[name="gmap_clusters"]' => array('checked' => TRUE)],
        ],
      ];


      $form['rendering'] = [
        '#type' => 'details',
        '#title' => $this->t('Rendering options'),
        '#open' => TRUE,
      ];

      $form['rendering']['location_markup'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Render the location info markup inside the list'),
        '#default_value' => $config->get('location_markup'),
      ];

      $form['rendering']['location_info'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use separate (default) sidebar window for InfoBox'),
        '#default_value' => $config->get('location_info'),
      ];

      $form['rendering']['hide_map'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide map by default'),
        '#description' => $this->t('Starts the WWAF with hidden GMap'),
        '#default_value' => $config->get('hide_map'),
      ];
      
      $form['rendering']['enable_countries'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable countries search'),
        '#description' => $this->t('This makes it possible to search whole country instead of radius related to the point of geocoder result.'),
        '#default_value' => $config->get('enable_countries'),
      ];



      $form['tracking'] = [
        '#type' => 'details',
        '#title' => $this->t('Tracking events'),
        '#open' => TRUE,
      ];

      $form['tracking']['track'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable Tracking'),
        '#description' => $this->t('<strong>WARNING:</strong> Requires Google analytics tracking events configured for the website.'),
        '#default_value' => $config->get('track'),
      ];

      $form['tracking']['track_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Event Category'),
        '#description' => $this->t('Google Analytics event is built like this: <code>event(<strong>eventCategory</strong>, eventAction, eventLabel, eventValue[, optionals])</code>'),
        '#default_value' => $config->get('track_name'),
      ];


      $form['debug'] = [
        '#type' => 'checkbox',
        '#title' => 'Debug JavaScript',
        '#description' => 'Enables for more console output inside JS',
        '#default_value' => $config->get('debug'),
      ];
    }
    
    return parent::buildForm($form, $form_state);
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state); 
    
    $config = $this->config('wwaf.settings');
    
    $values = $form_state->getValues();
    
    $config->set('debug',  $values['debug']);
    
    $config->set('gmap_snazzy_style', $values['gmap_snazzy_style']);
    $config->set('gmap_clusters',     $values['gmap_clusters']);
    
    
    $config->set('marker_default',    $values['marker_default']);
    if (!empty($values['marker_default'])) {
      $this->addFileUsage($values['marker_default'][0]);
    }

    
    $config->set('marker_active_enable', $values['marker_active_enable']);
    $config->set('marker_active',  $values['marker_active']);
    $config->set('cluster_64',     $values['cluster_64']);
    $config->set('cluster_128',    $values['cluster_128']);
    $config->set('cluster_256',    $values['cluster_256']);
    
    $config->set('location_markup',  $values['location_markup']);
    $config->set('location_info',    $values['location_info']);
    $config->set('hide_map',         $values['hide_map']);
    $config->set('enable_countries', $values['enable_countries']);
    
    $config->set('track',       $values['track']);
    $config->set('track_name',  $values['track_name']);
    
    
    $config->save();
  }

  private function addFileUsage($fid) {
    $file_usage = \Drupal::service('file.usage');
    $file = \Drupal\file\Entity\File::load($fid);
    $file_usage->add($file, 'wwaf', 'global', 1);
  }
}

