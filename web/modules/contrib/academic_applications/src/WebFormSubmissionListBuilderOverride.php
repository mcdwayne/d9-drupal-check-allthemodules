<?php

namespace Drupal\academic_applications;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Overrides the WebformSubmissionListBuilder.
 */
class WebFormSubmissionListBuilderOverride extends WebformSubmissionListBuilder {

  /**
   * The submission bundler.
   *
   * @var \Drupal\academic_applications\SubmissionBundler
   */
  protected $submissionBundler;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.message_manager'),
      $container->get('academic_applications.submission_bundler')
    );
  }

  /**
   * Constructs a new WebformSubmissionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   * @param \Drupal\academic_applications\SubmissionBundler $bundler
   *   The submission bundler.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, RequestStack $request_stack, AccountInterface $current_user, ConfigFactoryInterface $config_factory, WebformRequestInterface $webform_request, WebformElementManagerInterface $element_manager, WebformMessageManagerInterface $message_manager, SubmissionBundler $bundler) {
    parent::__construct($entity_type, $storage, $entity_type_manager, $route_match, $request_stack, $current_user, $config_factory, $webform_request, $element_manager, $message_manager);
    $this->addUploadsColumn();
    $this->submissionBundler = $bundler;
  }

  protected function addUploadsColumn() {
    $column = [
      'title' => 'References',
      'name' => 'academic_applications_uploads',
      'format' => 'raw',
      'sort' => FALSE,
    ];
    $this->insertColumnBefore(
      'operations',
      'academic_applications_uploads',
      $column
    );
  }

  /**
   * Insert a new column before a given column.
   *
   * @param string $key
   *   The column key to insert a column before.
   * @param string $newKey
   *   The column key of the new column.
   * @param array $column
   *   The new column.
   */
  protected function insertColumnBefore($key, $newKey, $column) {
    if (array_key_exists($key, $this->columns)) {
      $new = [];
      foreach ($this->columns as $k => $value) {
        if ($k === $key) {
          $new[$newKey] = $column;
        }
        $new[$k] = $value;
      }
      $this->columns = $new;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildRowColumn(array $column, EntityInterface $entity) {
    if ($column['name'] == 'academic_applications_uploads') {
      $links = [];
      $submissions = $this->submissionBundler->uploadFormSubmissions($entity);
      $count = 1;
      foreach ($submissions as $submission) {
        $links[] = !empty($submission->getData()['name']) ? $submission->link($submission->getData()['name']) : $submission->link($count);
        $count++;
      }
      $row = [
        'data' => [
          '#type' => 'markup',
          '#markup' => implode(', ', $links),
        ],
      ];
    }
    else {
      $row = parent::buildRowColumn($column, $entity);
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $base_route_name = $this->requestHandler->getBaseRouteName($entity, $this->sourceEntity);
    $route_parameters = $this->requestHandler->getRouteParameters($entity, $this->sourceEntity);
    if ($entity->access('view')) {
      $operations['bundle'] = [
        'title' => $this->t('Bundle'),
        'weight' => -10,
        'url' => Url::fromRoute("$base_route_name.webform_submission.bundle", $route_parameters),
      ];
    }
    return $operations;
  }

}
