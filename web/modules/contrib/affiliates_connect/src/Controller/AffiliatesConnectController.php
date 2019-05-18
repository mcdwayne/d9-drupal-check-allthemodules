<?php

namespace Drupal\affiliates_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\affiliates_connect\AffiliatesNetworkManager;
use Drupal\Core\Url;

/**
 * Renders plugins of affiliates connect.
 */
class AffiliatesConnectController extends ControllerBase {
  /**
   * The affiliates network manager.
   *
   * @var \Drupal\affiliates_connect\AffiliatesNetworkManager
   */
  private $affiliatesNetworkManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.affiliates_network'));
  }

  /**
   * AffiliatesConnectController constructor.
   *
   * @param \Drupal\affiliates_connect\AffiliatesNetworkManager $affiliatesNetworkManager
   *   The affiliates network manager.
   */
  public function __construct(AffiliatesNetworkManager $affiliatesNetworkManager) {
    $this->affiliatesNetworkManager = $affiliatesNetworkManager;
  }

  /**
   * Render the list of plugins for a affiliates network.
   *
   * @return array
   *   Render array listing the integrations.
   */
  public function plugins() {
    $networks = $this->affiliatesNetworkManager->getDefinitions();

    $status = FALSE;


    $data = [];
    foreach ($networks as $network) {
      $data[$network['id']] = $this->buildRow($network);
    }
    $form['overview'] = [
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $data,
      '#empty' => $this->t('There are no plugins enabled.'),
    ];
    return $form;
  }

  /**
   * Builds the header row for the plugins listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   */
  public function buildHeader()
  {
    $header = [
      $this->t('Module'),
      $this->t('Fetcher Status'),
      $this->t('Operations'),
    ];
    return $header;
  }

  /**
   * Builds a row for a plugin in the plugins listing.
   *
   * @param \Drupal\affiliates_connect\AffiliatesNetworkManager $network
   *   The plugin definition
   *
   * @return array
   *   A render array structure of fields for this plugin.
   *
   */
  public function buildRow($network)
  {
    $scraper_api = $this->config($network['id'].'.settings')->get('scraper_api');
    $native_api = $this->config($network['id'].'.settings')->get('native_api');
    $row = [
      'title' => [
        'data' => [
          '#type' => 'markup',
          '#prefix' => '<b>' . $network['label'] . '</b>',
          '#suffix' => '<div class="affiliates-connect-description">' . $network['description'] . '</div>',
        ],
      ],
      'fetcher' => [
          'data' => [
            '#type' => 'markup',
            'native' => [
              'data' => $this->getFetcherIcon($native_api),
              '#suffix' => '<b>Native</b>',
            ],
            'scraper' => [
              'data' => $this->getFetcherIcon($scraper_api),
              '#suffix' => '<b>Scraper</b>'
            ],
          ],
          'class' => ['fetcher'],
      ],
      'operations' => [
        'data' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => URL::fromRoute($network['id'] . '.settings'),
            ],
          ],
        ],
      ],
    ];
    return $row;
  }

  /**
   * Fetch the status icon for the plugins.
   *
   * @param bool $status
   *   The plugin status for native_api or scraper_api
   *
   * @return array
   *   A render array structure.
   *
   */
  public function getFetcherIcon($status)
  {
    $status_label = $status ? $this->t('Enabled') : $this->t('Disabled');

    $status_icon = [
      '#theme' => 'image',
      '#uri' => $status ? 'core/misc/icons/73b355/check.svg' : 'core/misc/icons/e32700/error.svg',
      '#width' => 18,
      '#height' => 18,
      '#alt' => $status_label,
      '#title' => $status_label,
    ];
    return $status_icon;
  }
}
