<?php

/**
 * @file
 * Contains \Drupal\object_log\Controller\ObjectLogController.
 */

namespace Drupal\object_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Contains callbacks for Object Log routes.
 */
class ObjectLogController extends ControllerBase {

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * ObjectLogController constructor.
   *
   * @param Connection $database
   *   The database connection service.
   * @param DateFormatter $date_formatter
   *   The date service.
   * @param FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, DateFormatter $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
  }

  /**
   * Prepares a table listing of stored objects.
   * .
   * @return array
   *   A Drupal render array.
   */
  public function listing() {
    $rows = array();
    $header = array(
      array(
        'data' => $this->t('Label'),
        'field' => 'ol.label',
      ),
      array(
        'data' => $this->t('Date'),
        'field' => 'ol.created',
        'sort' => 'desc',
      ),
    );

    $query = $this->database->select('object_log', 'ol')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('ol', array('label', 'created'));
    $result = $query
      ->limit(25)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $item) {
      $rows[] = array(
        $this->l($item->label, Url::fromRoute('object_log.object', array('label' => $item->label))),
        $this->dateFormatter->format($item->created, 'short'),
      );
    }

    $build = array(
      'object_log' => array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => array('id' => 'object_log'),
        '#empty' => t('No variables stored.'),
      ),
      'object_log_pager' => array(
        '#type' => 'pager',
      ),
      'clear_form' => $this->formBuilder->getForm('\Drupal\object_log\Form\ObjectLogClearLogForm'),
    );

    return $build;
  }

  /**
   * Display a stored object.
   *
   * @param string $label
   *   The name associated with the stored object.
   *
   * @return array
   *   A Drupal render array.
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function objectDetails($label) {
    $log = object_log_retrieve($label);
    if (!$log) {
      throw new NotFoundHttpException();
    }
    $object = $log->data;
    $build = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('object_wrapper'),
      ),
    );
    $build['title'] = array(
      '#plain_text' => $label,
      '#prefix' => '<h4 class="object-label">',
      '#suffix' => '</h4>',
    );
    $build['object'] = array(
      '#type' => 'markup',
      '#markup' => kprint_r($object, TRUE, NULL),
      '#prefix' => '<div class="object">',
      '#suffix' => '</div>',
    );
    $build['listing'] = $this->listing();

    return $build;
  }
}
