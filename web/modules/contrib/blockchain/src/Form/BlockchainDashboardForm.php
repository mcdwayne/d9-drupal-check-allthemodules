<?php

namespace Drupal\blockchain\Form;

use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\blockchain\Utils\BlockchainBatchHandler;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockchainDashboardForm.
 *
 * @ingroup blockchain
 */
class BlockchainDashboardForm extends FormBase {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * BlockchainDashboardForm constructor.
   *
   * @param \Drupal\blockchain\Service\BlockchainServiceInterface $blockchainService
   *   Blockchain service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(BlockchainServiceInterface $blockchainService,
                              EntityTypeManagerInterface $entityTypeManager) {

    $this->blockchainService = $blockchainService;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('blockchain.service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {

    return 'blockchain_dashboard';
  }

  /**
   * Defines the settings form for Blockchain Block entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $blockchainConfigs = $this->blockchainService->getConfigService()->getAll();
    if (!$blockchainConfigs) {
      $form['empty'] = [
        '#type' => 'item',
        '#title' => $this->t('No blockchain configs created'),
        '#markup' => $this->t('Go to related @tab and discover blockchain configurations.', [
          '@tab' => Link::fromTextAndUrl(
            $this->t('tab'),
            Url::fromRoute('entity.blockchain_config.collection'))->toString(),
        ]),
      ];
    }
    else {
      foreach ($blockchainConfigs as $blockchainConfig) {
        $blockchainConfigId = $blockchainConfig->id();
        $this->blockchainService->getConfigService()->setCurrentConfig($blockchainConfigId);
        $blockCount = $this->blockchainService->getStorageService()->getBlockCount();
        $queueService = $this->blockchainService->getQueueService();
        $countAnnounce = $queueService->getAnnounceQueue()->numberOfItems();
        $countMining = $queueService->getBlockPool()->numberOfItems();
        $announceManagement = $this->blockchainService->getConfigService()->getCurrentConfig()->getAnnounceManagement();
        $poolManagement = $this->blockchainService->getConfigService()->getCurrentConfig()->getPoolManagement();
        $form[$blockchainConfigId . '_wrapper'] = [
          '#type' => 'details',
          '#title' => $this->t('Blockchain block details'),
          '#open' => TRUE,
          '#attributes' => ['class' => ['package-listing']],
        ];
        $form[$blockchainConfigId . '_wrapper']['block_count'] = [
          '#type' => 'item',
          '#title' => $this->t('Number of blocks in storage'),
          '#markup' => $blockCount,
          '#description' => $this->t('Blocks in storage.'),
        ];
        $form[$blockchainConfigId . '_wrapper']['validate'] = [
          '#type' => 'button',
          '#executes_submit_callback' => TRUE,
          '#submit' => [[$this, 'callbackHandler']],
          '#value' => $this->t('Check now'),
          '#context' => 'check_blocks',
          '#blockchain_type' => $blockchainConfig->id(),
          '#disabled' => !$blockCount,
        ];
        $form[$blockchainConfigId . '_wrapper']['queue_mining_item_count'] = [
          '#type' => 'item',
          '#title' => $this->t('Number of items in block pool'),
          '#markup' => $countMining,
          '#description' => $this->t('Block pool management: @type', [
            '@type' => $this->t($poolManagement),
          ]),
        ];
        $form[$blockchainConfigId . '_wrapper']['do_mining'] = [
          '#type' => 'button',
          '#executes_submit_callback' => TRUE,
          '#submit' => [[$this, 'callbackHandler']],
          '#value' => $this->t('Do mining'),
          '#context' => 'do_mining',
          '#disabled' => !$countMining,
        ];
        $form[$blockchainConfigId . '_wrapper']['queue_announce_item_count'] = [
          '#type' => 'item',
          '#title' => $this->t('Number of items in announce queue'),
          '#markup' => $countAnnounce,
          '#description' => $this->t('Announce management: @type', [
            '@type' => $this->t($announceManagement),
          ]),
        ];
        $form[$blockchainConfigId . '_wrapper']['process_announce'] = [
          '#type' => 'button',
          '#executes_submit_callback' => TRUE,
          '#submit' => [[$this, 'callbackHandler']],
          '#value' => $this->t('Process announces'),
          '#context' => 'process_announce',
          '#disabled' => !$countAnnounce,
        ];
      }
    }

    return $form;
  }

  /**
   * Callback for custom actions.
   */
  public function callbackHandler(array &$form, FormStateInterface $form_state) {

    $this->getRequest()->query->remove('destination');
    $context = $form_state->getTriggeringElement()['#context'];
    if ($context == 'do_mining') {
      BlockchainBatchHandler::set(BlockchainBatchHandler::getMiningBatchDefinition());
    }
    elseif ($context == 'process_announce') {
      BlockchainBatchHandler::set(BlockchainBatchHandler::getAnnounceBatchDefinition());
    }
    elseif ($context == 'check_blocks') {
      $type = $form_state->getTriggeringElement()['#blockchain_type'];
      $this->blockchainService->getConfigService()->setCurrentConfig($type);
      if ($this->blockchainService->getStorageService()->checkBlocks()) {
        $this->messenger()->addStatus($this->t('Blocks are valid'));
      }
      else {
        $this->messenger()->addError($this->t('Validation failed'));
      }
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
