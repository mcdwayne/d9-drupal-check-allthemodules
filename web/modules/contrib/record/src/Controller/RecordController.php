<?php

namespace Drupal\record\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\record\RecordInterface;

/**
 * Returns responses for Record routes.
 */
class RecordController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a RecordController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add links for available record types.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function addPage() {
    $build = [];
    $build['#content'] = 'todo like NodeController';

    return $build;
  }

  /**
   * Generates an overview table of older revisions of a record.
   *
   * @param \Drupal\record\RecordInterface $record
   *   A record object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(RecordInterface $record) {
    $build = [];
    $build['#content'] = 'todo like NodeController';

    return $build;
  }

}
