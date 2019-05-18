<?php

namespace Drupal\hn_extended_view_serializer\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use UnexpectedValueException;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "extended_view_serializer",
 *   title = @Translation("Extended view Serializer"),
 *   help = @Translation("Serializes views."),
 *   display_types = {"data"}
 * )
 */
class ExtendedViewSerializer extends Serializer {

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer'),
      $container->getParameter('serializer.formats'),
      $container->getParameter('serializer.format_providers')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];

    $viewId = $this->view->id();
    $path = $this->view->getPath();

    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row_index => $row) {
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }

    $filters = [];

    foreach ($this->view->filter as $filter) {
      // Check if it is a exposed filter.
      if ($filter->isExposed()) {

        // Add filter to filters array.
        // Because we don't have a normalizer for each filter yet, we do a try
        // catch to not break loop.
        try {
          $filters[] = $this->serializer->normalize($filter);
        }
        catch (UnexpectedValueException $exception) {
          \Drupal::logger('HN')->warning($exception->getMessage());
        }
      }
    }

    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }

    $rows = [
      $viewId => $rows,
      'filters' => $filters,
    ];

    return $this->serializer->serialize($rows, $content_type);
  }

}
