<?php

namespace Drupal\blockchain\Form;

use Drupal\blockchain\Entity\BlockchainConfigInterface;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockchainConfigForm.
 */
class BlockchainConfigForm extends EntityForm {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockchainServiceInterface $blockchainService) {

    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('blockchain.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    /** @var \Drupal\blockchain\Entity\BlockchainConfig $blockchainConfig */
    $blockchainConfig = $this->entity;
    $this->blockchainService->getConfigService()->setCurrentConfig($blockchainConfig->id());
    $anyBlock = $this->blockchainService->getStorageService()->anyBlock();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $blockchainConfig->label(),
      '#description' => $this->t("Label for the Blockchain config."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $blockchainConfig->id(),
      '#machine_name' => [
        'exists' => '\Drupal\blockchain\Entity\BlockchainConfig::load',
      ],
      '#disabled' => !$blockchainConfig->isNew(),
    ];

    $form['blockchainId'] = [
      '#type' => 'textfield',
      '#default_value' => $blockchainConfig->getBlockchainId(),
      '#title' => $this->t('Blockchain id'),
      '#description' => $this->t('Blockchain id for this blockchain.'),
      '#disabled' => $anyBlock,
    ];

    $form['nodeId'] = [
      '#type' => 'textfield',
      '#default_value' => $blockchainConfig->getNodeId(),
      '#description' => $this->t('Blockchain node id is used as author for mined blocks.'),
      '#title' => $this->t('Blockchain node id'),
      '#disabled' => $anyBlock,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Blockchain type'),
      '#options' => [
        BlockchainConfigInterface::TYPE_SINGLE => $this->t('Single'),
        BlockchainConfigInterface::TYPE_MULTIPLE  => $this->t('Multiple'),
      ],
      '#default_value' => $blockchainConfig->getType(),
      '#description' => $this->t('Single means only one node, thus one blockchain database.'),
    ];

    $form['auth'] = [
      '#type' => 'select',
      '#default_value' => $blockchainConfig->getAuth(),
      '#title' => $this->t('Auth method'),
      '#description' => $this->t('Auth method Blockchain API uses to interact.'),
      '#options' => $this->blockchainService->getAuthManager()->getList(),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="blockchainType"]' => [
            'value' => BlockchainConfigInterface::TYPE_MULTIPLE,
          ],
        ],
      ],
    ];

    $form['filterType'] = [
      '#type' => 'select',
      '#title' => $this->t('Blockchain nodes filtering type'),
      '#options' => [
        BlockchainConfigInterface::FILTER_TYPE_BLACKLIST => $this->t('Blacklist'),
        BlockchainConfigInterface::FILTER_TYPE_WHITELIST => $this->t('Whitelist'),
      ],
      '#default_value' => $blockchainConfig->getFilterType(),
      '#description' => $this->t('The way, blockchain nodes will be filtered.'),
      '#states' => [
        'visible' => [
          ':input[name="blockchainType"]' => [
            'value' => BlockchainConfigInterface::TYPE_MULTIPLE,
          ],
        ],
      ],
    ];

    $form['filterList'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blockchain filter list'),
      '#default_value' => $blockchainConfig->getFilterList(),
      '#description' => $this->t('List of ip addresses to be filtered, newline separated.'),
    ];

    $form['timeoutPool'] = [
      '#type' => 'number',
      '#title' => $this->t('Pool process timeout'),
      '#default_value' => $blockchainConfig->getTimeoutPool(),
      '#required' => TRUE,
      '#min' => 1,
      '#description' => $this->t('Timeout for pool processing.'),
    ];

    $form['poolManagement'] = [
      '#type' => 'select',
      '#title' => $this->t('Pool management'),
      '#options' => [
        BlockchainConfigInterface::POOL_MANAGEMENT_MANUAL => $this->t('Manual'),
        BlockchainConfigInterface::POOL_MANAGEMENT_CRON  => $this->t('CRON'),
      ],
      '#default_value' => $blockchainConfig->getPoolManagement(),
      '#description' => $this->t('The way, pool queue will be managed.'),
    ];

    $form['intervalPool'] = [
      '#type' => 'number',
      '#title' => $this->t('Pool management interval'),
      '#default_value' => $blockchainConfig->getIntervalPool(),
      '#required' => TRUE,
      '#min' => 1,
      '#description' => $this->t('Interval for pool management CRON job.'),
      '#states' => [
        'visible' => [
          ':input[name="poolManagement"]' => [
            'value' => BlockchainConfigInterface::POOL_MANAGEMENT_CRON,
          ],
        ],
      ],
    ];

    $form['pullSizeAnnounce'] = [
      '#type' => 'number',
      '#title' => $this->t('Announce pull size'),
      '#default_value' => $blockchainConfig->getPullSizeAnnounce(),
      '#required' => TRUE,
      '#min' => 1,
      '#description' => $this->t('Size in blocks to be pulled in one request.'),
    ];

    $form['searchIntervalAnnounce'] = [
      '#type' => 'number',
      '#title' => $this->t('Announce search interval'),
      '#default_value' => $blockchainConfig->getSearchIntervalAnnounce(),
      '#required' => TRUE,
      '#min' => 1,
      '#description' => $this->t('Size in blocks to be skipped while searching.'),
    ];

    $form['announceManagement'] = [
      '#type' => 'select',
      '#title' => $this->t('Announce management'),
      '#options' => [
        BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_IMMEDIATE => $this->t('Immediate'),
        BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_CRON  => $this->t('CRON'),
      ],
      '#default_value' => $blockchainConfig->getAnnounceManagement(),
      '#description' => $this->t('The way, announce queue will be managed.'),
    ];

    $form['intervalAnnounce'] = [
      '#type' => 'number',
      '#title' => $this->t('Announce management interval'),
      '#default_value' => $blockchainConfig->getIntervalAnnounce(),
      '#required' => TRUE,
      '#min' => 1,
      '#description' => $this->t('Interval for announce management CRON job.'),
      '#states' => [
        'visible' => [
          ':input[name="announceManagement"]' => ['value' => BlockchainConfigInterface::ANNOUNCE_MANAGEMENT_CRON],
        ],
      ],
    ];

    $form['allowNotSecure'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow not secure protocol'),
      '#default_value' => $blockchainConfig->getAllowNotSecure(),
      '#description' => $this->t('Check to allow not secure protocol for blockchain nodes.'),
    ];

    $form['powPosition'] = [
      '#disabled' => $anyBlock,
      '#type' => 'select',
      '#title' => $this->t('Proof of work position'),
      '#options' => [
        BlockchainConfigInterface::POW_POSITION_START => $this->t('Start'),
        BlockchainConfigInterface::POW_POSITION_END  => $this->t('End'),
      ],
      '#default_value' => $blockchainConfig->getPowPosition(),
      '#description' => $this->t('Proof of work position in previous hash.'),
    ];

    $form['powExpression'] = [
      '#disabled' => $anyBlock,
      '#type' => 'textfield',
      '#title' => $this->t('Proof of work expression'),
      '#default_value' => $blockchainConfig->getPowExpression(),
      '#description' => $this->t('Proof of work expression in previous hash.'),
    ];

    $form['dataHandler'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Blockchain data handler.'),
      '#options' => $this->blockchainService->getDataManager()->getList(),
      '#default_value' => $blockchainConfig->getDataHandler(),
      '#description' => $this->t('Select data handler for given blockchain.'),
    ];

    $hasSettings = $this->blockchainService
      ->getDataManager()
      ->definitionGet($blockchainConfig->getDataHandler(), 'settings');
    if ($hasSettings) {
      $form['dataHandlerSettings'] = [
        '#type' => 'link',
        '#title' => $this->t('Data handler settings'),
        '#url' => Url::fromRoute('<current>'),
      ];
    }
    $form['action'] = [
      '#submit' => ['::submitForm'],
    ];
    if (!$anyBlock) {
      $form['action']['regenerate_blockchain_id'] = [
        '#type' => 'submit',
        '#executes_submit_callback' => TRUE,
        '#value' => $this->t('Regenerate blockchain id'),
        '#context' => 'regenerate_blockchain_id',
      ];
      $form['action']['regenerate_blockchain_node_id'] = [
        '#type' => 'button',
        '#executes_submit_callback' => TRUE,
        '#value' => $this->t('Regenerate blockchain node id'),
        '#context' => 'regenerate_blockchain_node_id',
      ];
      $form['action']['put_generic_block'] = [
        '#type' => 'button',
        '#executes_submit_callback' => TRUE,
        '#value' => $this->t('Put generic block'),
        '#context' => 'put_generic_block',
      ];
    }
    else {
      $form['action']['remove_blocks'] = [
        '#type' => 'button',
        '#executes_submit_callback' => TRUE,
        '#value' => $this->t('Delete all blocks'),
        '#context' => 'remove_blocks',
      ];
    }

    return $form;
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

    $this->getRequest()->query->remove('destination');
    $element = $form_state->getTriggeringElement();
    if ($element['#type'] == 'button') {
      if ($element['#context'] == 'regenerate_blockchain_id') {
        $this->blockchainService
          ->getConfigService()
          ->getCurrentConfig()
          ->setBlockchainId(
            $this->blockchainService->getConfigService()->generateId()
          )->save();
        $this->messenger()->addStatus($this->t('Blockchain id regenerated'));
      }
      elseif ($element['#context'] == 'regenerate_blockchain_node_id') {
        $this->blockchainService
          ->getConfigService()
          ->getCurrentConfig()
          ->setNodeId(
            $this->blockchainService->getConfigService()->generateId()
          )->save();
        $this->messenger()->addStatus($this->t('Blockchain node id regenerated'));
      }
      elseif ($element['#context'] == 'put_generic_block') {
        $genericBlock = $this->blockchainService->getStorageService()->getGenericBlock();
        $this->blockchainService->getStorageService()->save($genericBlock);
        $this->messenger()->addStatus($this->t('Generic block created'));
      }
      elseif ($element['#context'] == 'remove_blocks') {
        $this->blockchainService->getStorageService()->deleteAll();
        $this->messenger()->addStatus($this->t('All blocks deleted'));
      }
      $form_state->setRedirect('<current>');
    }
    else {
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $blockchain_config = $this->entity;
    $status = $blockchain_config->save();
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Blockchain config.', [
          '%label' => $blockchain_config->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Blockchain config.', [
          '%label' => $blockchain_config->label(),
        ]));
    }
    $form_state->setRedirectUrl($blockchain_config->toUrl('collection'));
  }

}
