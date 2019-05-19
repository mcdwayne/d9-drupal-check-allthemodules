<?php

namespace Drupal\wwaf\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\facets\Exception\Exception;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

//use Drupal\wwaf\Plugin\Block\WWAFBlock;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;


class WWAFController extends ControllerBase {

  private $mapReducePattern;

  /**
   * Entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;
  
  /**
   * Constructs a new CustomRestController object.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   * The entity query factory.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  
  public function getMapReducePattern() {
    return $this->mapReducePattern;
  }

  public function setMapReducePattern($pattern) {
    $this->mapReducePattern = $pattern;
  }

  public function addToMapReducePattern($key, $value) {
    $this->mapReducePattern[ $key ] = $value;
  }
  
  private function mapReduce(Array $item) {
    if ($this->getMapReducePattern() == NULL) {
      $pattern = array(
        'nid' => 'id',
        'title' => 't',
        'address'      => [
          '_k' => 'a',
          '_v' => [
            'country_code' => 'c',
            'administrative_area' => 'aa',
            'locality' => 'lc',
            'dependent_locality' => 'dl',
            'postal_code' => 'pc',
            'sorting_code' => 'sc',
            'address_line1' => 'a1',
            'address_line2' => 'a2',
          ],
        ],
        'description'  => 'd',
        'geometry'     => 'g',
      );

      $this->setMapReducePattern($pattern);
    }
    
    $reduced = [
      'id' => floatval($item['id']),
      't' => $item['label'],
      'g' => ['lat' => (float) $item['gps']['lat'], 'lng' => (float) $item['gps']['lng']],
      'd' => $item['description'],
      'a' => [
        'c'  => $item['address']['country_code'],
        'aa' => $item['address']['administrative_area'],
        'lc' => $item['address']['locality'],
        'dl' => $item['address']['dependent_locality'],
        'pc' => $item['address']['postal_code'],
        'sc' => $item['address']['sorting_code'],
        'a1' => $item['address']['address_line1'],
        'a2' => $item['address']['address_line2'],
      ],
    ];

    $used_keys = [];
    foreach($item as $key => $value) {
      if (strpos($key, 'field_') === FALSE)
        continue;

      $arr = explode("_", $key);
      $new_key = substr($arr[0], 0, 1) . substr($arr[1], 0, 1);
      if (in_array($new_key, $used_keys)) {
        $new_key = $new_key.'a';
      }
      $used_keys[] = $new_key;
      
      $this->addToMapReducePattern($key, $new_key);
      
      $reduced[$new_key] = $item[ $key ];
    }
    
    return $reduced;
  }
  
  public static function getMainBuild(Request $request, $custom_suggestion = NULL) {
    $build = [
      '#theme' => 'wwaf_main',
      '#prefix' => '<div id="wwaf" class="wwaf-main">',
      '#suffix' => '</div>',
    ];

    if ($custom_suggestion) {
      $build['#custom_suggestion'] = $custom_suggestion;
    }
    
    // dependency from "geolocation" module
    $api_key  = \Drupal::config('geolocation.settings')->get('google_map_api_key');
    
    $config   = \Drupal::config('wwaf.settings');
    
    $snazzy   = $config->get('gmap_snazzy_style');
    $clusters = $config->get('gmap_clusters');
    $debug    = $config->get('debug');
    
    
    if ($clusters) {
      $build['#attached']['library'][] = 'wwaf/clusters';
    }
    else {
      $build['#attached']['library'][] = 'wwaf/main';
    }
    
    $settings = [
      'api_key'      => $api_key,
      'snazzy_style' => $snazzy,
      'clusters'     => $clusters === 1,
      'debug'        => $debug === 1,
      'feed'         => '/rest/wwaf/list',
      'track'        => [
        'enabled'    => $config->get('track') === 1,
        'name'       => $config->get('track_name'),
      ],
      'use_active'   => $config->get('marker_active_enable') === 1,
      'images'       => [
        'normal'    => file_create_url( drupal_get_path('module', 'wwaf') . '/images/marker.svg'),
        'active'    => file_create_url( drupal_get_path('module', 'wwaf') . '/images/marker-on.svg'),
        'cl_small'  => file_create_url( drupal_get_path('module', 'wwaf') . '/images/cluster-64.png'),
        'cl_medium' => file_create_url( drupal_get_path('module', 'wwaf') . '/images/cluster-128.png'),
        'cl_large'  => file_create_url( drupal_get_path('module', 'wwaf') . '/images/cluster-256.png'),
      ],
      
      'location_info'    => $config->get('location_info') === 1,
      'location_markup'  => $config->get('location_markup') === 1,
      'hide_map'         => $config->get('hide_map') === 1,
      'enable_countries' => $config->get('enable_countries') === 1, 
    ];
    
    $marker_default = $config->get('marker_default');
    if (!empty($marker_default)) {
      $file = \Drupal\file\Entity\File::load($marker_default[0]);
      if ($file)
        $settings['images']['normal'] = file_create_url($file->getFileUri());
    }

    $marker_active = $config->get('marker_active');
    if (!empty($marker_active)) {
      $file = \Drupal\file\Entity\File::load($marker_active[0]);
      if ($file)
        $settings['images']['active'] = file_create_url($file->getFileUri());
    }

    $cluster_64 = $config->get('cluster_64');
    if (!empty($cluster_64)) {
      $file = \Drupal\file\Entity\File::load($cluster_64[0]);
      if ($file)
        $settings['images']['cl_small'] = file_create_url($file->getFileUri());
    }

    $cluster_128 = $config->get('cluster_128');
    if (!empty($cluster_128)) {
      $file = \Drupal\file\Entity\File::load($cluster_128[0]);
      if ($file)
        $settings['images']['cl_medium'] = file_create_url($file->getFileUri());
    }

    $cluster_256 = $config->get('cluster_256');
    if (!empty($cluster_256)) {
      $file = \Drupal\file\Entity\File::load($cluster_256[0]);
      if ($file)
        $settings['images']['cl_large'] = file_create_url($file->getFileUri());
    }
    
    // Altering with hooks if any present:
    // ---------------------------------------------------
    \Drupal::moduleHandler()->alter('wwaf_js_settings', $settings);
    \Drupal::theme()->alter('wwaf_js_settings', $settings);
    // ---------------------------------------------------
    
    $build['#attached']['drupalSettings']['wwaf'] = $settings;

    // Get params:
    $search = $request->query->get('search');
    $radius = $request->query->get('radius');
    
    $rads = array(
      '10'  => '10 km',
      '20'  => '20 km',
      '30'  => '30 km',
      '40'  => '40 km',
      '50'  => '50 km',
      '100' => '100 km',
    );
    $current_radius = $radius? $rads[$radius] : $rads['30'];
    
    $build['#radiuses'] = $rads;
    $build['#curr_radius'] = $current_radius;
    $build['#search'] = $search;
    $build['#enable_countries'] = $config->get('enable_countries') === 1;
    
    return $build;
  }
  
  /**
   * Main build for route
   */
  public function main(Request $request) {
    return $this->getMainBuild($request);
  }
  
  /**
   * List method - gets the list of the WWAF Points as json.
   * @param Request $request
   * @return JsonResponse
   */
  public function rest_list(Request $request) {

    $data = [];
    $response = null;
    
    $config   = \Drupal::config('wwaf.settings');
    $module_handler = \Drupal::moduleHandler();
    
    try {
      
      $query = $this->entityQuery->get('wwaf_entity');
      $query->addTag("wwaf_rest_list"); // adding Tag so that it can be altered

      $country = $request->query->get('country');
      if ($country) {
        $query->condition('address.country_code', $country );
      }
      else {
        $query->condition('address.country_code', 'IT');  //Fallback Italy
      }

      $data['country'] = $country;


      // Altering the query with hooks if any present
      // ---------------------------------------------------
      $module_handler->invokeAll('wwaf_rest_query_alter', [ &$query ]);
      \Drupal::theme()->alter('wwaf_rest_query', $query);
      // ---------------------------------------------------

      $result = $query->execute();
      
      $data['total'] = count($result);
      $data['records'] = [];
      
      $wwaf_storage = \Drupal::entityManager()->getStorage('wwaf_entity');
      $entities = $wwaf_storage->loadMultiple($result);
      
      $data['records'] = [];
      foreach ($entities as $item) {
        $data['records'][] = $this->mapReduce( $item->toDataArray() );
      }
      
      $data['status'] = 'OK';
      
      // Map-unreduce:
      $data['map'] = $this->getMapReducePattern();
      
      
      // Altering with hooks if any present:
      // ---------------------------------------------------
      $module_handler->invokeAll('wwaf_rest_data_alter', [ &$data ]);
      \Drupal::theme()->alter('wwaf_rest_data', $data);
      // ---------------------------------------------------
      
      // Add the wwaf_list cache tag so the endpoint results.
      $cache_tag = 'wwaf_list';
      if ($country ) { 
        $cache_tag .= '_'.$country; 
      }
      
      $cache_metadata = new CacheableMetadata();
      $cache_metadata->setCacheTags([$cache_tag]);
      $cache_metadata->addCacheContexts(['url.query_args:country']);

      $response = new CacheableJsonResponse($data);
      $response->addCacheableDependency($cache_metadata);

    }
    catch (Exception $exception) {
      $data = [
        'status' => 'ERROR',
        'message' => $exception->getMessage(),
      ];

      $response = new JsonResponse($data);
      $response->setStatusCode(Response.HTTP_BAD_REQUEST);
    }

    return $response;
  }
  
}