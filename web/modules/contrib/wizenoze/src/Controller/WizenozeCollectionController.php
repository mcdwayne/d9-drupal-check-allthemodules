<?php

namespace Drupal\wizenoze\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wizenoze\Helper\WizenozeAPI;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a controller to serve search pages.
 */
class WizenozeCollectionController extends ControllerBase {

  /**
   * Protected renderer variable.
   *
   * @var renderer
   */
  protected $renderer;

  /**
   * Protected config variable.
   *
   * @var config
   */
  protected $config;

  /**
   * Constructs a new WizenozeCollectionController object.
   *
   * @param Drupal\Core\Render\RendererInterface $renderer_interface
   *   The renderer service.
   * @param Drupal\Core\Config\ConfigFactory $config
   *   The config manager service.
   */
  public function __construct(RendererInterface $renderer_interface, ConfigFactory $config) {
    $this->renderer = $renderer_interface;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('renderer'), $container->get('config.factory')
    );
  }

  /**
   * The render list function.
   *
   * @return array
   *   The page build.
   */
  public function renderList() {

    $output = [];
    $wizenoze = WizenozeAPI::getInstance();
    $items = $wizenoze->collectionList();
    $row = [];
    $header = [
      'id',
      'name',
      'description',
      'access type',
      'content type',
      'actions',
    ];

    if (!empty($items)) {

      foreach ($items as $item) {
        $url = Url::fromRoute('wizenoze.config.collection.edit', ['id' => $item['id']]);
        $link = Link::fromTextAndUrl($this->t('Edit'), $url)->toRenderable();
        $link['#attributes'] = [
          'class' => [
            'button',
            'button--primary',
            'button--small',
          ],
        ];

        $row[] = [
          'id' => $item['id'],
          'name' => $item['name'],
          'description' => $item['description'],
          'access type' => $item['accessType'],
          'content type' => implode(', ', $this->config->get('wizenoze.settings')->get('collection-id-' . $item['id'])),
          'edit' => $this->renderer->render($link),
        ];
      }
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $row,
      '#attributes' => [
        'id' => 'collection-table',
      ],
    ];

    $output['#markup'] = $this->renderer->render($table);

    return $output;
  }

}
