<?php

namespace Drupal\smallads_index;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\user\UserData;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountProxy;

class Form extends FormBase {

  /**
   * @var Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * Total number of search results (for pager)
   *
   * @var int
   */
  private $total;

  /**
   * @var UserData
   */
  private $userData;

  /**
   * @var AccountProxy
   */
  private $current_user;

  public function __construct(RequestStack $request, UserData $user_data, AccountProxy $current_user) {
    $this->request = $request->getCurrentRequest();
    $this->userData = $user_data;
    $this->currentUser = $current_user;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('user.data'),
      $container->get('current_user')
    );
  }

  function buildForm(array $form, FormStateInterface $form_state, $smallad_type = NULL) {
    $prefs = $this->userData
     ->get('smallads_index', $this->currentUser->id(), 'pref');

    $parts = explode('/', $this->request->getRequestUri());
    $form_state->set('smallad_type', $parts[2]);
    $form['fragment'] = [
      '#title' => $this->t('Keywords'),
      '#placeholder' => $this->t('Any word that might occur in the ad.'),
      '#type' => 'textfield',
      '#weight' => 1,
      '#default_value' => $this->request->query->get('fragment')
    ];
    $form['advanced'] = [
      '#title' => $this->t('Advanced'),
      '#type' => 'details',
      '#open' => empty($prefs),
      '#weight' => 3
    ];
    //these should be saved
    $form['advanced']['radius'] = [
      '#title' => $this->t('Max km from me'),
      '#description' => $this->t('0 means global.'),
      '#type' => 'number',
      '#default_value' => isset($prefs['radius']) ? $prefs['radius'] : '5',//this could be a setting
      '#field_suffix' => 'km',
      '#weight' => 0
    ];
    $form['advanced']['directexchange'] = [
      '#title' => $this->t("I'm willing to barter/swap something specific."),
      '#type' => 'checkbox',
      '#default_value' => isset($prefs['directexchange']) ? $prefs['directexchange'] : TRUE,//this could be a setting
      '#weight' => 1
    ];
    $form['advanced']['indirectexchange'] = [
      '#title' => $this->t('I use complementary currency'),
      '#type' => 'checkbox',
      '#default_value' => isset($prefs['indirectexchange']) ? $prefs['indirectexchange'] : TRUE,//this could be a setting
      '#weight' => 1
    ];
    $form['advanced']['money'] = [
      '#title' => $this->t('Allow part-payment in money'),
      '#description' => $this->t('Some ads may require that costs be covered.'),
      '#type' => 'checkbox',
      '#default_value' => isset($prefs['money']) ? $prefs['money'] : TRUE,//this could be a setting
      '#weight' => 1
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#weight' => 5
    ];

//    stdClass Object(
//      [id] => 38
//      [name] => admin
//      [url] => matslats.net/ad/38
//      [type] => offer
//      [title] =>  double-loaded dragonfly in need of repairs
//      [body] => Aptent comis typicus uxor.
//      [keywords] => admin
//      [directexchange] => 0
//      [indirectexchange] => 0
//      [money] => 1
//      [scope] => 4
//      [uuid] => bfa9d286-ff92-4f22-ba8d-e1565216346c
//      [expires] => 1498774870
//      [client_id] => 1
//      [lon] => 60
//      [lat] => 0
//    )
    $hits = [];
    $results = $this->getResults($form_state->get('smallad_type'));
    if ($results) {
      $this->showMapResults($form, $results, -1);
      $this->showListResults($form, $results, 10);
    }
    return $form;
  }

  function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    //save the search settings
    $prefs = [
      'radius' => $form_state->getValue('radius'),
      'directexchange' => $form_state->getValue('directexchange'),
      'indirectexchange' => $form_state->getValue('indirectexchange'),
      'money' => $form_state->getValue('money')
    ];
    $this->userData
     ->set('smallads_index', $this->currentUser->id(), 'pref', $prefs);

    $form_state->setRedirect(
      'smallads_index.search',
      ['smallad_type' => $form_state->get('smallad_type')],
      [
        'query' => [
          'fragment' => trim($form_state->getValue('fragment')),
          //'bundle' => $form_state->getValue('bundle')
        ] + array_filter($prefs)
      ]
    );
  }

    //$form_state->setRebuild();

  public function getFormId() {
    return 'smallads_index_form';
  }

  public function getResults($type) {
    $query = $this->request->query;
    $items = [];
    if ($query->all()) {
      $filters['type'] = $type;
      if ($query->has('fragment')) {
        $filters['fragment'] = $query->get('fragment');
      }
      if ($query->has('radius') and $radius = $query->get('radius')) {
        $filters['radius'] = $query->get('radius');
        $filters['home'] = smallads_geo_home_location();
      }

      if ($query->has('directexchange')) {
        if ($query->get('directexchange') == 0) {
          $filters['directexchange'] = 0;
        }
      }
      if ($query->has('indirectexchange')) {
        if ($query->get('indirectexchange') == 0) {
          $filters['indirectexchange'] = 0;
        }
      }

      if ($query->has('money')) {
        if ($query->get('money') == 0) {
          $filters['directexchange'] = 0;
        }
      }
      if ($query->has('page')) {
        $filters['page'] = $query->get('page');
      }
      // Now submit the search results.
      if($filters) {
        $result = smallads_index_execute($filters);
        $this->total = $result->total;
        $items = $result->items;
      }
    }
    return $items;
  }

  /**
   * Show the results in a map.
   * @param type $form
   * @param array $results
   *
   * @todo
   */
  function showMapresults(&$form, array $results) {
    $features = [];
    foreach ($results as $result) {
      $dest = Url::fromUri($result->url);
      $features[] = [
        'type' => 'point',
        'lat' => $result->lat,
        'lon' => $result->lon,
        'icon' => [
          'iconUrl' => '/'.drupal_get_path('module', 'smallads_geo') . '/images/bluepin.png',
          //'icon_size' => ['x' => 32, 'y' => 32],
          'icon_anchor' => ['x' => 24, 'y' => 32],//not supported yet in leaflet module Beta1
          'popup_anchor' => ['x' => 16, 'y' => 0],
        ],
        'popup' => \Drupal\Core\Link::fromTextAndUrl($result->title, $dest)->toString() . '<br>'.$result->body,
        'leaflet_id' => $result->id
      ];
    }
    $form['map'] = \Drupal::service('leaflet.service')->leafletRenderMap(
      leaflet_leaflet_map_info()['OSM Mapnik'],
      $features,
      '400px'
    );
    $form['map']['#weight'] = -1;
  }

  /**
   * Show the results in a list, with pager.
   */
  function showListresults(&$form, array $results) {
    if($results) {
      foreach ($results as $result) {
        $body = strip_tags($result->body);
        if (strlen($body) > 400) {
          $body = substr($body, 0, 400) . (strlen($body) > 400 ? '...' : '');
        }
        $snippet = $this->t(
            '@snippet<strong>Keywords</strong>: @words<br />on %site',
            [
              '@snippet' => $body ? \Drupal\Core\Render\Markup::create($body.'<br \>') : '',
              '@words' => $result->keywords,
              '%site' => \Drupal\Core\Link::fromTextAndUrl($result->name, Url::fromUri($result->url))->toString()
            ]
        );
        $hits[] = [
          '#theme' => 'search_result',
          '#result' => [
            'link' => $result->url,
            'title' => $result->title,
            'language' => $result->lang,
            'snippet' => $snippet,
//            'extra' => $result->name,
            //'date' => '1500000000',
          ]
        ];
      }
      $form['search_results'] = [
        '#theme' => 'item_list__search_results',
        '#items' => $hits,
        '#empty' => [
          '#markup' => '<h3>' . $this->t('Your search yielded no results.') . '</h3>',
        ],
        '#list_type' => 'ol',
        '#weight' => 10,
      ];
      pager_default_initialize($this->total, 2);
      $form['pager'] = [
        '#type' => 'pager',
        '#weight' => 11,
      ];
    }
    else {
      $form['search_results'] = ['#markup' => $this->t('No results')];
    }
  }
}
