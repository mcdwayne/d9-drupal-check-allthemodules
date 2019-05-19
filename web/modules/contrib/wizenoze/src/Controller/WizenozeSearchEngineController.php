<?php

namespace Drupal\wizenoze\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wizenoze\Helper\WizenozeAPI;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to serve search pages.
 */
class WizenozeSearchEngineController extends ControllerBase {

  /**
   * Protected renderer variable.
   *
   * @var renderer
   */
  protected $renderer;

  /**
   * Constructs a new WizenozeSearchEngineController object.
   *
   * @param Drupal\Core\Render\RendererInterface $renderer_interface
   *   The renderer service.
   */
  public function __construct(RendererInterface $renderer_interface) {
    $this->renderer = $renderer_interface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('renderer')
    );
  }

  /**
   * Function to render List.
   *
   * @return array
   *   The page build.
   */
  public function renderList() {

    $output = [];
    $wizenoze = WizenozeAPI::getInstance();
    $items = $wizenoze->searchEngineList();
    $row = [];
    $header = [
      'id',
      'account',
      'name',
      'description',
      'collections',
      'status',
      'actions',
    ];

    if (!empty($items)) {
      foreach ($items as $item) {

        // Fetch collection attached.
        $collectionList = [];
        foreach ($item['sources'] as $source) {
          if ($source['sourceType'] == 'Collection') {
            $name = $wizenoze->collectionName($source['sourceId']);
            $collectionList[] = $name;
          }
        }

        $url = Url::fromRoute('wizenoze.config.searchengine.edit', ['id' => $item['id']]);
        $link = Link::fromTextAndUrl($this->t('Edit'), $url);
        $link = $link->toRenderable();
        $link['#attributes'] = [
          'class' => [
            'button',
            'button--primary',
            'button--small',
          ],
        ];

        $row[] = [
          'id' => $item['id'],
          'account' => $item['accountId'],
          'name' => $item['name'],
          'description' => $item['description'],
          'collections' => implode(', ', $collectionList),
          'status' => ($item['active'] == 1) ? 'Active' : 'Inactive',
          'edit' => $this->renderer->render($link),
        ];
      }
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $row,
      '#attributes' => [
        'id' => 'search-engine-table',
      ],
    ];

    $output['#markup'] = $this->renderer->render($table);

    return $output;
  }

}
