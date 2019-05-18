<?php

namespace Drupal\qwantsearch\Service;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Template\Attribute;

/**
 * Class QwantSearchDisplay.
 */
class QwantSearchDisplay implements QwantSearchDisplayInterface {

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler interface.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRenderableResults(array $results) {
    $renderable_results = [
      '#theme' => 'qwantsearch_results_page',
      '#attached' => [
        'library' => [
          'qwantsearch/qwantsearch',
        ],
      ],
      '#results' => [],
    ];
    $nb_elements = count($results);

    if ($nb_elements == 0) {
      $renderable_results = [
        '#markup' => Xss::filter($this->configFactory->get('qwantsearch.settings')
          ->get('qwantsearch_no_result')),
      ];
      return $renderable_results;
    }

    foreach ($results as $index => $result) {
      $renderable_result = [
        '#theme' => 'qwantsearch_search_result',
        '#title' => strip_tags(html_entity_decode($result->title, ENT_QUOTES)),
        '#url' => $result->url,
        '#snippet' => strip_tags(html_entity_decode($result->desc, ENT_QUOTES)),
        '#date' => $result->date,
        '#picture' => $this->generateResultImage($result->media),
        '#row_attributes' => new Attribute(
          [
            'class' => [
              'qwant-search-result',
            ],
          ]
        ),
      ];

      // Allow module to change variables given the qwant result.
      $this->moduleHandler->alter('qwantsearch_search_result', $renderable_result, $result);

      $row_class = ($index % 2 == 0) ? 'odd' : 'even';
      $renderable_result['#row_attributes']->addClass($row_class);
      if ($index == 0) {
        $renderable_result['#row_attributes']->addClass('first');
      }
      if ($index == $nb_elements - 1) {
        $renderable_result['#row_attributes']->addClass('last');
      }
      $renderable_results['#results'][] = $renderable_result;
    }

    return $renderable_results;
  }

  /**
   * {@inheritdoc}
   */
  public function generateResultImage(array $medias) {
    $picture = [];
    foreach ($medias as $media) {
      if ($media->type == 'image') {
        $picture = [
          '#theme' => 'imagecache_external',
          '#width' => $media->width,
          '#height' => $media->height,
          '#uri' => $media->url,
          '#style_name' => $this->configFactory->get('qwantsearch.settings')
            ->get('qwantsearch_result_image_style'),
        ];

        $this->moduleHandler->alter('qwantsearch_result_thumbnail', $picture);
      }
    }
    return $picture;
  }

}
