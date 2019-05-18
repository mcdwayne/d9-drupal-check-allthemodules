<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node_subs\Service\AccountService;
use Drupal\node_subs\Service\NodeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Class QueueForm.
 */
class QueueForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Datetime\DateFormatterInterface definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;
  /**
   * Drupal\Core\StringTranslation\TranslationManager definition.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;
  /**
   * Drupal\node_subs\Service\AccountService definition.
   *
   * @var \Drupal\node_subs\Service\AccountService
   */
  protected $account;
  /**
   * Drupal\node_subs\Service\NodeService definition.
   *
   * @var \Drupal\node_subs\Service\NodeService
   */
  protected $nodeService;
  /**
   * Constructs a new QueueForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    TranslationManager $string_translation,
    AccountService $account,
    NodeService $node_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->stringTranslation = $string_translation;
    $this->account = $account;
    $this->nodeService = $node_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('string_translation'),
      $container->get('node_subs.account'),
      $container->get('node_subs.nodes')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_queue_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_subs_queue_nodes = $this->nodeService->getQueue(NODE_SUBS_QUEUE_TABLE);

    $header = [
      'title' => $this->t('Title'),
      'type' => $this->t('Node type'),
      'created' => $this->t('Created'),
      'process' => $this->t('Process'),
    ];

    $rows = [];

    if (!empty($node_subs_queue_nodes)) {
      foreach ($node_subs_queue_nodes as $node) {
        $node_obj = $this->entityTypeManager->getStorage('node')->load($node->nid);
        if (!$node_obj) {
          continue;
        }
        $rows[$node->nid] = array(
          'nid' => $node->nid,
          'title' => $node_obj->getTitle(),
          'type' => $node_obj->getType(),
          'created' => $this->dateFormatter->format($node_obj->getCreatedTime(), 'custom', 'd.m.Y - H:i'),
          'process' => $this->account->countAccounts(TRUE, $node->process) . ' / ' . $this->account->countAccounts(),
        );
      }
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('Queue is empty.'),
      '#attributes' => array('class' => array('node-subs-queue')),
      '#required' => TRUE,
      '#required_error' => $this->t('Choose at least one element'),
    ];
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the action for the selected items'),
      '#options' => [
        'process' => $this->t('Process'),
        'remove' => $this->t('Remove from queue'),
      ],
      '#empty_option' => $this->t('- Choose action -'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $table = array_filter($form_state->getValue('table'));
    $node_ids = array_values($table);
    $action = $form_state->getValue('action');

    switch ($action) {
      case 'remove':
        foreach ($node_ids as $nid) {
          $this->nodeService->moveToHistory($nid);
        }
        $message = $this->stringTranslation->formatPlural(count($node_ids), 'Archived 1 node', 'Archived @count nodes');
        drupal_set_message($message);
        break;
      case 'process':
        $this->nodeService->queueProcess($node_ids);
        $message = $this->stringTranslation->formatPlural(count($node_ids), '1 node processed', '@count nodes processed');
        drupal_set_message($message);
        break;
    }

  }

}
