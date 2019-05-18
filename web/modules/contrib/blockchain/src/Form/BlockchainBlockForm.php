<?php

namespace Drupal\blockchain\Form;

use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainBatchHandler;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Blockchain Block edit forms.
 *
 * @ingroup blockchain
 */
class BlockchainBlockForm extends ContentEntityForm {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    TimeInterface $time = NULL,
    BlockchainServiceInterface $blockchainService = NULL) {

    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('blockchain.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $entity = $this->entity;
    if (!$this->blockchainService->getConfigService()->exists($entity->getEntityTypeId())) {
      $this->blockchainService->getConfigService()->discoverBlockchainConfigs();
    }
    $this->blockchainService->getConfigService()->setCurrentConfig($entity->getEntityTypeId());
    if (!$this->blockchainService->getStorageService()->anyBlock()) {
      if (!$form_state->has('entity_form_initialized')) {
        $this->init($form_state);
      }
      $form['message'] = [
        '#type' => 'item',
        '#title' => $this->t('There are no blocks in list yet'),
        '#description' => $this->t('Click below to generate first generic block.'),
      ];
      $form['action']['put_generic_block'] = [
        '#type' => 'button',
        '#executes_submit_callback' => TRUE,
        '#value' => $this->t('Put generic block'),
        '#context' => 'put_generic_block',
        '#submit' => [[$this, 'callbackHandler']],
      ];
    }
    else {
      $form = parent::buildForm($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actionsElement(array $form, FormStateInterface $form_state) {

    $element = parent::actionsElement($form, $form_state);
    $element['add_to_queue'] = [
      '#type' => 'button',
      '#executes_submit_callback' => TRUE,
      '#value' => $this->t('Add to pool'),
      '#context' => 'add_to_queue',
      '#submit' => [[$this, 'callbackHandler']],
      '#weight' => 100,
    ];
    $element['submit']['#value'] = $this->t('Mine pool');

    return $element;
  }

  /**
   * Callback for custom actions.
   */
  public function callbackHandler(array &$form, FormStateInterface $form_state) {

    /* @var $entity \Drupal\blockchain\Entity\BlockchainBlockInterface */
    $entity = $this->entity;
    $this->getRequest()->query->remove('destination');
    $context = $form_state->getTriggeringElement()['#context'];
    if ($context == 'put_generic_block') {
      $genericBlock = $this->blockchainService->getStorageService()->getGenericBlock();
      $this->blockchainService->getStorageService()->save($genericBlock);
      $this->messenger()->addStatus($this->t('Generic block created'));
    }
    elseif ($context == 'add_to_queue') {
      // Add values to entity.
      $this->copyFormValuesToEntity($entity, $form, $form_state);
      // Here we pass raw data.
      $this->blockchainService
        ->getQueueService()
        ->addBlockItem($entity->getData(), $entity->getEntityTypeId());
      $this->messenger()->addStatus($this->t('Block added to queue'));
    }
    $form_state->setRedirect('<current>');
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    /* @var $entity \Drupal\blockchain\Entity\BlockchainBlockInterface */
    $entity = $this->entity;
    $this->blockchainService
      ->getQueueService()
      ->addBlockItem($entity->getData(), $entity->getEntityTypeId());
    BlockchainBatchHandler::set(BlockchainBatchHandler::getMiningBatchDefinition());
  }

}
