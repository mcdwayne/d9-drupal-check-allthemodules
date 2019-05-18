<?php

namespace Drupal\carerix_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\carerix_form\CarerixServiceInterface;

/**
 * Class CRDataNodeSyncForm.
 *
 * @package Drupal\carerix_form\Form
 */
class CRDataNodeSyncForm extends FormBase {

  /**
   * The carerix service.
   *
   * @var \Drupal\carerix_form\CarerixServiceInterface
   */
  protected $carerix;

  /**
   * CarerixIntegrationServiceController constructor.
   *
   * @param \Drupal\carerix_form\CarerixServiceInterface $carerix
   *   Carerix service.
   */
  public function __construct(CarerixServiceInterface $carerix) {
    $this->carerix = $carerix;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('carerix')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'carerix_data_node_sync_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['data_node_type'] = [
      '#title' => $this->t('Carerix data node type'),
      '#type' => 'select',
      '#options' => [
        'Document-type' => 'Document-type',
        'URL-label' => 'URL-label',
      ],
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync Carerix data nodes'),
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $dataNodeType = $form_state->getValue('data_node_type');
    $dataNodes = [];
    $ts = time();
    // Default in Carerix API.
    $count = 10;
    $loop = 0;

    do {
      // Update starting point.
      $start = $loop * $count;

      // Get Carerix data nodes.
      $fetched = $this->carerix->getAllEntities('DataNode', [
        'qualifier' => 'notActive != 1 and deleted != 1 and type.name = "' . $dataNodeType . '"',
        'count' => $count,
        'start' => $start,
      ]);

      $loop++;

      if (empty($fetched)) {
        break;
      }

      // Merge results.
      $dataNodes = array_merge_recursive($dataNodes, $fetched->toArray());

    } while (count($fetched) == $count);

    // Get stored data nodes.
    $data = \Drupal::database()->select('carerix_data_nodes', 'c')
      ->fields('c', ['data_node_id', 'data_node_value'])
      ->condition('c.data_node_type', $dataNodeType, '=')
      ->execute()
      ->fetchAllKeyed();

    // Add operations.
    $operations = [];
    $i = 0;
    foreach ($dataNodes as $i => $dataNode) {
      $operations[] = [
        '\Drupal\carerix_form\Controller\CRDataNodesSyncController::syncDataNode',
        [
          $data,
          $dataNode,
          $dataNodeType,
          $ts,
          $this->t('(Operation @operation)', ['@operation' => $i]),
        ],
      ];
    }

    // Cleanup unused.
    $operations[] = [
      '\Drupal\carerix_form\Controller\CRDataNodesSyncController::cleanUpDataNodes',
      [
        $dataNodeType,
        $ts,
        $this->t('Operation @operation', ['@operation' => $i]),
      ],
    ];

    $batch = [
      'operations' => $operations,
      'title' => t('Syncing Data Nodes...'),
      'finished' => '\Drupal\carerix_form\Controller\CRDataNodesSyncController::syncFinishedCallback',
    ];

    batch_set($batch);
  }

}
