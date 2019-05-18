<?php

namespace Drupal\algolia_search_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

class SearchController extends ControllerBase
{

  /**
   * @var State $state
   */
  protected $state;

  /**
   * [__construct description]
   * @param State $state
   */
  public function __construct(StateInterface $state)
  {
    $this->state = $state;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('state')
    );
  }

  /**
   * [showSearchPage description]
   * @return [type] [description]
   */
  public function showSearchPage()
  {
    $settingsName = 'algolia_search_custom_settings_';

    $settings = [
      'appId' => $this->state->get($settingsName . 'app_id'),
      'apiKey' => $this->state->get($settingsName . 'api_key'),
      'indexName' => $this->state->get($settingsName . 'index_name'),
    ];

    return [
      '#theme'       => 'asc_search_page',
      '#attached'    => [
        'library'        => ['algolia_search_custom/algolia_search_custom.search'],
        'drupalSettings' => [
          'settings' => $settings,
        ],
      ],
    ];
  }
}
