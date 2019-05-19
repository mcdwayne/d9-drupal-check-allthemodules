<?php

namespace Drupal\webfactory_slave\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webfactory_slave\EntitySyncWrapper;
use Drupal\webfactory_slave\Services\EntitySynchronizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the table entities form.
 *
 * @package Drupal\webfactory_slave\Form
 */
class TableEntitiesForm extends FormBase {

  /**
   * The rest services.
   *
   * @var mixed
   */
  protected $entitySync;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Enable/Disable the pager.
   *
   * Note: if pager is enabled, for now the sync
   * and title filters are not working.
   *
   * @var bool
   */
  protected static $pagerEnabled = FALSE;

  /**
   * TableEntitiesForm constructor.
   *
   * @param \Drupal\webfactory_slave\Services\EntitySynchronizer $entity_sync
   *   EntitySynchronizer service.
   * @param Request $request
   *   The current request.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntitySynchronizer $entity_sync, Request $request, ConfigFactoryInterface $config_factory, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->entitySync = $entity_sync;
    $this->currentRequest = $request;
    $this->configFactory = $config_factory;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webfactory_slave.services.entity_synchronizer'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webfactory_slave_tableentities_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $type_filter  = $this->currentRequest->get('type');
    $sync_filter  = $this->currentRequest->get('sync', EntitySyncWrapper::NEW_ENTITY . '|' . EntitySyncWrapper::NEEDS_UPDATE);
    $title_filter = $this->currentRequest->get('title');

    $offset = NULL;
    $num_per_page = NULL;

    if (static::isPagerEnabled()) {
      $sync_filter = NULL;
      $title_filter = NULL;

      // Get the current page from pager.
      $page = pager_find_page();
      $num_per_page = $this->configFactory->get('webfactory_slave.settings')
        ->get('num_per_page');
      $offset = $num_per_page * $page;
    }

    // Get the datas from the master.
    $data = $this->entitySync->getData($type_filter, $num_per_page, $offset);

    if (static::isPagerEnabled()) {
      // Init Pager (if the pager is enable).
      $nb_total_results = isset($data['nb_total_entities']) ? $data['nb_total_entities'] : 0;
      pager_default_initialize($nb_total_results, $num_per_page);
    }

    $header_form = array(
      'title' => array('data' => $this->t('title'), 'sort' => 'asc'),
      'type' => array('data' => $this->t('content type'), 'sort' => 'asc'),
      'synchronized' => array('data' => $this->t('Synchronized')),
      'status' => array('data' => $this->t('Status')),
    );

    $form['table'] = array(
      '#type' => 'table',
      '#tableselect' => TRUE,
      '#empty' => $this->t('No content available.'),
      '#header' => $header_form,
    );

    if (isset($data['entities']) && is_array($data['entities'])) {
      $entity_type = $this->entityTypeManager->getDefinition($data['entity_type']);

      foreach ($data['entities'] as $bundle => $entities) {
        foreach ($entities as $entity_id => $entity_summary) {
          $entity_wrapper = new EntitySyncWrapper($entity_type, $entity_summary, $this->entityRepository);

          $uuid = $entity_wrapper->getUuid();
          $title = $entity_wrapper->getTitle();
          $status = $entity_wrapper->getStatus();
          $local_entity = $entity_wrapper->getLocalEntity();

          switch ($status) {
            case EntitySyncWrapper::NEEDS_UPDATE:
              $status_label = $this->t('Needs update');
              break;

            case EntitySyncWrapper::UPDATED:
              $status_label = $this->t('Up to date');
              break;

            case EntitySyncWrapper::MODIFIED_LOCALLY:
              $status_label = $this->t('Modified locally');
              break;

            default:
              $status_label = $this->t('New content');
          }

          if (empty($title_filter) || strpos($title, $title_filter) !== FALSE) {
            $link = $title;

            if ($local_entity != NULL) {
              $link = $local_entity->toLink($title)->toString();
            }

            $form['table'][$uuid]['title'] = array(
              '#markup' => $link,
            );

            $form['table'][$uuid]['type'] = array(
              '#markup' => $bundle,
            );

            $form['table'][$uuid]['synchronized'] = array(
              '#theme' => 'entity_status',
              '#entity' => [
                'status' => $status,
                'status_label' => $status_label,
              ],
            );

            $form['table'][$uuid]['status'] = array(
              '#markup' => '',
            );

            // Filter options after construct.
            $this->applyFilters(['sync' => $sync_filter], $form, $entity_wrapper);
          }
        }
      }

      $form['entity_type'] = array(
        '#type'  => 'hidden',
        '#value' => $entity_type->id(),
      );

      $form['selected'] = array(
        '#type' => 'select',
        '#title' => $this->t('Action'),
        '#options' => array(
          'synchronize' => $this->t('Synchronize'),
          'duplicate' => $this->t('Duplicate'),
        ),
        '#description' => $this->t('Set the action.'),
      );

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Update selected contents'),
        '#tableselect' => TRUE,
      );

      if (static::isPagerEnabled()) {
        $form['pager'] = array('#type' => 'pager');
      }
    }

    return $form;
  }

  /**
   * Apply filter to the entity table.
   *
   * @param array $filters
   *   Filters to apply.
   * @param array $form
   *   Form to alter.
   * @param EntitySyncWrapper $entity_wrapper
   *   Entity to filter if needed.
   */
  protected function applyFilters(array $filters, array &$form, EntitySyncWrapper $entity_wrapper) {
    $uuid = $entity_wrapper->getUuid();
    $status = $entity_wrapper->getStatus();

    switch ($filters['sync']) {
      case EntitySyncWrapper::NEW_ENTITY . '|' . EntitySyncWrapper::NEEDS_UPDATE:
        if ($status != EntitySyncWrapper::NEW_ENTITY && $status != EntitySyncWrapper::NEEDS_UPDATE) {
          unset($form['table'][$uuid]);
        }
        break;

      case EntitySyncWrapper::NEW_ENTITY:
        if ($status != EntitySyncWrapper::NEW_ENTITY) {
          unset($form['table'][$uuid]);
        }
        break;

      case EntitySyncWrapper::NEEDS_UPDATE:
        if ($status != EntitySyncWrapper::NEEDS_UPDATE) {
          unset($form['table'][$uuid]);
        }
        break;

      case EntitySyncWrapper::MODIFIED_LOCALLY:
        if ($status != EntitySyncWrapper::MODIFIED_LOCALLY) {
          unset($form['table'][$uuid]);
        }
        break;

      case EntitySyncWrapper::UPDATED:
        if ($status != EntitySyncWrapper::UPDATED) {
          unset($form['table'][$uuid]);
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type_filter  = $this->currentRequest->get('type');

    $values       = $form_state->getValues();
    $action       = $form_state->getValue('selected');
    $entity_type  = $form_state->getValue('entity_type');

    if ($action == 'synchronize') {
      $count = 0;

      foreach ($values['table'] as $uuid => $is_checked) {
        if (!empty($is_checked)) {
          $this->entitySync->save($type_filter, $uuid);
          ++$count;
        }

      }

      drupal_set_message($this->t('@count contents has been updated', array('@count' => $count)));
    }
    elseif ($action == 'duplicate') {
      $count = 0;
      foreach ($values['table'] as $uuid => $is_checked) {
        if ($is_checked) {
          $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
          $type_field = $entity->get('type')->getValue();
          $new_entities_values = array(
            'type' => $type_field['0']['target_id'],
            'title' => $entity->get('title'),
            'body' => $entity->get('body'),
          );

          $new_entity = $this->entityTypeManager
            ->getStorage($entity_type)
            ->create($new_entities_values);
          $new_entity->save();

          ++$count;
        }
      }

      drupal_set_message($this->t('@count contents has been duplicated', array('@count' => $count)));
    }
  }

  /**
   * Pager enabled or not.
   *
   * @return bool
   *   Pager enabled ?
   */
  public static function isPagerEnabled() {
    return static::$pagerEnabled;
  }

}
