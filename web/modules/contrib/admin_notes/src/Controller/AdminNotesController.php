<?php

namespace Drupal\admin_notes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for page example routes.
 */
class AdminNotesController extends ControllerBase {

  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'), $container->get('module_handler'), $container->get('date.formatter'), $container->get('form_builder')
    );
  }

  /**
   * Constructs a DbLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->userStorage = $this->entityManager()->getStorage('user');
  }

  /**
   *
   */
  public function adminNotesReport() {
    $rows = array();
    $header = array(
      array(
        'data' => $this->t('Date'),
        'field' => 'an.timestamp',
        'sort' => 'desc',
      ),
      array(
        'data' => $this->t('User'),
        'field' => 'u.name',
      ),
      array(
        'data' => $this->t('Note'),
      ),
      array(
        'data' => $this->t('Path'),
      ),
    );
    $query = $this->database->select('admin_notes', 'an')
        ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
        ->extend('\Drupal\Core\Database\Query\TableSortExtender');

    $query->leftJoin('users', 'u', 'an.uid = u.uid');
    $result = $query
        ->fields('an')
        ->fields('u')
        ->limit(50)
        ->orderByHeader($header)
        ->execute()
        ->fetchAllAssoc('anid');

    $rows = array();
    foreach ($result as $entry) {
      $username = array(
        '#theme' => 'username',
        '#account' => $this->userStorage->load($entry->uid),
      );
      $rows[] = array(
        'data' => array(
          $this->dateFormatter->format($entry->timestamp, 'short'),
          array('data' => $username),
          array('data' => $entry->note),
          array('data' => $entry->path),
        ),
      );
    }
    $build['admin_notes_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'admin-notes', 'class' => array('admin-notes')),
      '#empty' => t('No log messages available.'),
    );
    $build['admin_notes_pager'] = array('#type' => 'pager');
    return $build;
  }

  // Public function ajxsubmit() {
  //        if ($_GET['op'] == 'Delete') {
  //    print \Drupal\admin_notes\Form\AdminNotesForm::admin_notes_record_handler($_GET['path'],'delete');
  //  }
  //  // ...otherwise...
  //  else {
  //   // print _admin_notes_record_handler($_GET['path'], 'save', $_GET['note']);
  //      $path = \Drupal::service('path.current')->getPath();
  //    print \Drupal\admin_notes\Form\AdminNotesForm::admin_notes_record_handler($path, 'save',  $_GET['note']);
  //  }
  //  }.
  /**
   * Constructs a simple page.
   *
   * The router _controller callback, maps the path
   * 'examples/page_example/simple' to this method.
   *
   * _controller callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  /**
   * A more complex _controller callback that takes arguments.
   *
   * This callback is mapped to the path
   * 'examples/page_example/arguments/{first}/{second}'.
   *
   * The arguments in brackets are passed to this callback from the page URL.
   * The placeholder names "first" and "second" can have any value but should
   * match the callback method variable names; i.e. $first and $second.
   *
   * This function also demonstrates a more complex render array in the returned
   * values. Instead of rendering the HTML with theme('item_list'), content is
   * left un-rendered, and the theme function name is set using #theme. This
   * content will now be rendered as late as possible, giving more parts of the
   * system a chance to change it if necessary.
   *
   * Consult @link http://drupal.org/node/930760 Render Arrays documentation
   * @endlink for details.
   *
   * @param string $first
   *   A string to use, should be a number.
   * @param string $second
   *   Another string to use, should be a number.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the parameters are invalid.
   */
}
