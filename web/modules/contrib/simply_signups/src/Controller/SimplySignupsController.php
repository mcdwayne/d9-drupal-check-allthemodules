<?php

namespace Drupal\simply_signups\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simply_signups\Utility\SimplySignupsUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Adds functionality and pages to the simply signups module.
 */
class SimplySignupsController extends ControllerBase {

  protected $time;
  protected $database;
  protected $entityQuery;
  protected $currentPath;
  protected $formBuilder;
  protected $dateFormatter;
  protected $configFactory;
  protected $entityTypeManager;

  /**
   * Implements __construct function.
   */
  public function __construct(QueryFactory $entity_query, DateFormatter $date_formatter, TimeInterface $time_interface, CurrentPathStack $current_path, Connection $database_connection, FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityQuery = $entity_query;
    $this->dateFormatter = $date_formatter;
    $this->time = $time_interface;
    $this->currentPath = $current_path;
    $this->database = $database_connection;
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('path.current'),
      $container->get('database'),
      $container->get('form_builder'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Displays list of published nodes.
   */
  public function dashboardPage() {
    $config = $this->configFactory->get('simply_signups.config');
    $bundles = $config->get('bundles');
    $rows = [];
    $header = [
      'title' => [
        'data' => $this->t('title'),
        'field' => 'title',
        'specifier' => 'title',
      ],
      'start_date' => [
        'data' => $this->t('start date'),
      ],
      'end_date' => [
        'data' => $this->t('end date'),
      ],
      'status' => [
        'data' => $this->t('published'),
        'field' => 'status',
        'specifier' => 'status',
      ],
      'attending' => [
        'data' => $this->t('attending'),
      ],
      'checkedin' => [
        'data' => $this->t('checked in'),
      ],
      'operations' => [
        'data' => $this->t('operations'),
      ],
    ];
    if (!empty($bundles)) {
      $nids = $this->entityQuery->get('node')
        ->tableSort($header)
        ->pager(50)
        ->condition('type', $bundles, 'IN')
        ->condition('status', 1, '=')
        ->execute();
      $node_storage = $this->entityTypeManager->getStorage('node');
      foreach ($nids as $nid) {
        $node = $node_storage->load($nid);
        $id = $node->id();
        $title = $node->getTitle();
        $status = ($node->isPublished()) ? $this->t('Yes') : $this->t('No');

        /* generate node link */
        $nodeUrl = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
        $nodeLink = Link::fromTextAndUrl($title, $nodeUrl)->toString();

        /*
         * generate node edit link include a destination url
         * to redirect the user back to the dashboard after editing a node.
         */
        $destinationUrl = Url::fromRoute('simply_signups.dashboard');
        $submissions = SimplySignupsUtility::getNumberOfSignups($nid);
        $attending = SimplySignupsUtility::getNumberOfAttending($nid);
        $checkedin = SimplySignupsUtility::getNumberOfCheckedInsAttending($nid);
        $startDate = (SimplySignupsUtility::getStartDate($nid) != FALSE) ? date('m/d/Y - h:i a', SimplySignupsUtility::getStartDate($nid)) : '-';
        $endDate = (SimplySignupsUtility::getEndDate($nid) != FALSE) ? date('m/d/Y - h:i a', SimplySignupsUtility::getEndDate($nid)) : '-';

        $links['edit'] = [
          'title' => $this->t('edit'),
          'url' => Url::fromRoute('entity.node.edit_form', ['node' => $nid, 'destination' => $destinationUrl->toString()]),
        ];
        $links['configure'] = [
          'title' => $this->t('configure'),
          'url' => Url::fromRoute('simply_signups.nodes.settings', ['node' => $nid, 'destination' => $destinationUrl->toString()]),
        ];
        if ($submissions > 0) {
          $links['rsvps'] = [
            'title' => $this->t('signups'),
            'url' => Url::fromRoute('simply_signups.nodes', ['node' => $nid]),
          ];
          $links['download_rsvps'] = [
            'title' => $this->t('download'),
            'url' => Url::fromRoute('simply_signups.nodes.csv', ['node' => $nid]),
          ];
        }
        $rows[$id] = [
          'data' => [
            'title' => $nodeLink,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'attending' => $attending,
            'checkedin' => $checkedin,
            'operations' => [
              'data' => [
                '#type' => 'dropbutton',
                '#links' => $links,
              ],
            ],
          ],
        ];
        unset($links['download_rsvps']);
      }
    }
    $url = Url::fromRoute('simply_signups.config');
    $link = Link::fromTextAndUrl('configure', $url)->toString();
    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t("There are 0 items currently found. Don't forget to @link simply signups.", ['@link' => $link]),
      '#attributes' => [
        'data-striping' => 1,
      ],
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

  /**
   * Displays list of unpublished nodes.
   */
  public function dashboardUnpublishedPage() {
    $config = $this->configFactory->get('simply_signups.config');
    $bundles = $config->get('bundles');
    $rows = [];
    $header = [
      'title' => [
        'data' => $this->t('title'),
        'field' => 'title',
        'specifier' => 'title',
      ],
      'start_date' => [
        'data' => $this->t('start date'),
      ],
      'end_date' => [
        'data' => $this->t('end date'),
      ],
      'status' => [
        'data' => $this->t('published'),
        'field' => 'status',
        'specifier' => 'status',
      ],
      'attending' => [
        'data' => $this->t('attending'),
      ],
      'checkedin' => [
        'data' => $this->t('checked in'),
      ],
      'operations' => [
        'data' => $this->t('operations'),
      ],
    ];
    if (!empty($bundles)) {
      $nids = $this->entityQuery->get('node')
        ->tableSort($header)
        ->pager(50)
        ->condition('type', $bundles, 'IN')
        ->condition('status', 0, '=')
        ->execute();
      $node_storage = $this->entityTypeManager->getStorage('node');
      foreach ($nids as $nid) {
        $node = $node_storage->load($nid);
        $id = $node->id();
        $title = $node->getTitle();
        $status = ($node->isPublished()) ? $this->t('Yes') : $this->t('No');

        /* generate node link */
        $nodeUrl = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
        $nodeLink = Link::fromTextAndUrl($title, $nodeUrl)->toString();

        /*
         * generate node edit link include a destination url
         * to redirect the user back to the dashboard after editing a node.
         */
        $destinationUrl = Url::fromRoute('simply_signups.dashboard');
        $submissions = SimplySignupsUtility::getNumberOfSignups($nid);
        $attending = SimplySignupsUtility::getNumberOfAttending($nid);
        $checkedin = SimplySignupsUtility::getNumberOfCheckedInsAttending($nid);
        $startDate = (SimplySignupsUtility::getStartDate($nid) != FALSE) ? date('m/d/Y - h:i a', SimplySignupsUtility::getStartDate($nid)) : '-';
        $endDate = (SimplySignupsUtility::getEndDate($nid) != FALSE) ? date('m/d/Y - h:i a', SimplySignupsUtility::getEndDate($nid)) : '-';

        $links['edit'] = [
          'title' => $this->t('edit'),
          'url' => Url::fromRoute('entity.node.edit_form', ['node' => $nid, 'destination' => $destinationUrl->toString()]),
        ];
        $links['configure'] = [
          'title' => $this->t('configure'),
          'url' => Url::fromRoute('simply_signups.nodes.settings', ['node' => $nid, 'destination' => $destinationUrl->toString()]),
        ];
        if ($submissions > 0) {
          $links['rsvps'] = [
            'title' => $this->t('signups'),
            'url' => Url::fromRoute('simply_signups.nodes', ['node' => $nid]),
          ];
          $links['download_rsvps'] = [
            'title' => $this->t('download'),
            'url' => Url::fromRoute('simply_signups.nodes.csv', ['node' => $nid]),
          ];
        }
        $rows[$id] = [
          'data' => [
            'title' => $nodeLink,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $status,
            'attending' => $attending,
            'checkedin' => $checkedin,
            'operations' => [
              'data' => [
                '#type' => 'dropbutton',
                '#links' => $links,
              ],
            ],
          ],
        ];
        unset($links['download_rsvps']);
      }
    }
    $url = Url::fromRoute('simply_signups.config');
    $link = Link::fromTextAndUrl('configure', $url)->toString();
    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t("There are 0 items currently found. Don't forget to @link simply signups.", ['@link' => $link]),
      '#attributes' => [
        'data-striping' => 1,
      ],
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }

  /**
   * Renders the template edit form.
   */
  public function templatesEditForm() {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    if ($arg[3] == 'templates') {
      if ($arg[5] == 'edit') {
        $fid = $arg[6];
      }
      $tid = $arg[4];
    }
    if ($arg[0] == 'node') {
      if ($arg[4] == 'edit') {
        $fid = $arg[5];
      }
    }
    $db = $this->database;
    $query = $db->select('simply_signups_templates', 'p');
    $query->fields('p');
    $query->condition('id', $tid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $query = $db->select('simply_signups_templates_fields', 'p');
    $query->fields('p');
    $query->condition('id', $fid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $field = unserialize($row->field);
    }
    $type = ucfirst($field['#type']);
    $form = $this->formBuilder->getForm('Drupal\simply_signups\Form\Field\SimplySignupsFields' . $type . 'Form');
    return $form;
  }

  /**
   * Renders the node edit form.
   */
  public function nodesEditForm() {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $nid = $arg[1];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $isValidNode = (isset($node)) ? TRUE : FALSE;
    if (!$isValidNode) {
      throw new NotFoundHttpException();
    }
    $fid = $arg[3];
    $db = $this->database;
    $query = $db->select('simply_signups_fields', 'p');
    $query->fields('p');
    $query->condition('id', $fid, '=');
    $count = $query->countQuery()->execute()->fetchField();
    if ($count == 0) {
      throw new NotFoundHttpException();
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $row) {
      $field = unserialize($row->field);
    }
    $type = ucfirst($field['#type']);
    $form = $this->formBuilder->getForm('Drupal\simply_signups\Form\Field\SimplySignupsFields' . $type . 'Form');
    return $form;
  }

  /**
   * Generate CSV file which contains signups for a specific node id.
   */
  public function downloadSignups() {
    $path = $this->currentPath->getPath();
    $currentPath = ltrim($path, '/');
    $arg = explode('/', $currentPath);
    $id = $arg[1];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($id);
    $nid = $node->id();
    $output = [];
    $isValidNode = (is_numeric($nid)) ? TRUE : FALSE;
    if (!$isValidNode) {
      throw new NotFoundHttpException();
    }
    $title = $node->getTitle();
    $db = $this->database;
    $query = $db->select('simply_signups_fields', 'p');
    $query->fields('p');
    $query->orderBy('weight');
    $query->condition('nid', $nid, '=');
    $count = $query->countQuery()->execute()->fetchField();

    $options = ['absolute' => TRUE];
    $nodeUrl = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
    $nodeLink = Link::fromTextAndUrl('Return to node', $nodeUrl)->toString();

    if ($count == 0) {
      $url = Url::fromRoute('simply_signups.nodes.fields', ['node' => $nid], $options);
      $link = Link::fromTextAndUrl('Manage fields', $url)->toString();
      $markup = $this->t('There are currently 0 <em>signups fields</em> for this event.<br />@nodeLink<br />@link', ['@nodeLink' => $nodeLink, '@link' => $link]);
      $output = ['#markup' => $markup];
    }
    else {
      $results = $query->execute()->fetchAll();
      $rawHeadings = [];
      foreach ($results as $result) {
        $rawHeadings[] = $result->name;
      }
      $headings = implode(",", $rawHeadings);
      $query = $db->select('simply_signups_data', 'p');
      $query->fields('p');
      $query->condition('nid', $nid, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count == 0) {
        $options = ['absolute' => TRUE];
        $url = Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
        $nodeLink = Link::fromTextAndUrl('Return to node', $url)->toString();
        $output = ['#markup' => $this->t('There are currently 0 <em>signups</em> for this event.<br />@nodeLink', ['@nodeLink' => $nodeLink])];
      }
      else {
        $results = $query->execute()->fetchAll();
        $allData = [];
        $allData[] = 'Event URL, ' . $headings . ', Check-In, Submitted';
        foreach ($results as $row) {
          $rawSignupData = unserialize($row->fields);
          $signupData = [];
          foreach ($rawSignupData as $field) {
            if ($field['type'] == "tel") {
              $formattedTelephone = SimplySignupsUtility::formatTelephone($field['value'], 3);
              $field['value'] = $formattedTelephone;
            }
            $signupData[] = $field['value'];
          }
          $status = ($row->status == 1) ? 'Yes' : 'No';
          $submitted = $this->dateFormatter->format($row->created, 'custom', 'm/d/Y - h:i a');
          $allData[] =  $nodeUrl->toString() . ', ' . implode(",", $signupData) . ', ' . $status . ', ' . $submitted;
        }
        $completeFileData = implode("\n", $allData);
        $requestTime = $this->time->getCurrentTime();
        $title = $title . '_' . $requestTime;
        $csvFilename = $title . '.csv';
        $filename = $csvFilename;
        $output = $completeFileData;
        $response = new Response();
        $response->headers->set('Content-type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setContent(render($output));
        $response->send();
        exit();
      }
    }
  }

}
