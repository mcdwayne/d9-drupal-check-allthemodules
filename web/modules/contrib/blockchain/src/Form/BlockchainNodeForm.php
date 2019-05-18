<?php

namespace Drupal\blockchain\Form;

use Drupal\blockchain\Entity\BlockchainNodeInterface;
use Drupal\blockchain\Service\BlockchainService;
use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BlockchainNodeForm.
 */
class BlockchainNodeForm extends EntityForm {

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
    /** @var \Drupal\blockchain\Entity\BlockchainNodeInterface $blockchain_node */
    $blockchain_node = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $blockchain_node->label(),
      '#description' => $this->t("Label for the Blockchain Node."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $blockchain_node->id(),
      '#machine_name' => [
        'exists' => '\Drupal\blockchain\Entity\BlockchainNode::load',
      ],
      '#disabled' => !$blockchain_node->isNew(),
      '#access' => !$blockchain_node->isNew(),
    ];
    $form['address'] = [
      '#type' => 'textfield',
      '#default_value' => $blockchain_node->getAddress(),
      '#title' => $this->t('Ip/host address'),
      '#description' => $this->t("Ip/host address for the Blockchain Node."),
      '#required' => TRUE,
    ];
    $form['port'] = [
      '#type' => 'textfield',
      '#default_value' => $blockchain_node->getPort(),
      '#title' => $this->t('Port'),
      '#description' => $this->t("Port of the Blockchain Node."),
    ];
    $form['secure'] = [
      '#type' => 'checkbox',
      '#default_value' => $blockchain_node->isSecure(),
      '#title' => $this->t('Is secure'),
      '#description' => $this->t("Defines HTTP schema."),
    ];
    $form['addressSource'] = [
      '#type' => 'radios',
      '#default_value' => $blockchain_node->getAddressSource(),
      '#title' => $this->t('Address source'),
      '#description' => $this->t('Source of address.'),
      '#required' => TRUE,
      '#options' => [
        BlockchainNodeInterface::ADDRESS_SOURCE_CLIENT => $this->t('Client'),
        BlockchainNodeInterface::ADDRESS_SOURCE_REQUEST => $this->t('Request'),
      ],
    ];
    $form['self'] = [
      '#type' => 'textfield',
      '#default_value' => $blockchain_node->getSelf(),
      '#title' => $this->t('Blockchain self id'),
      '#description' => $this->t('Blockchain self parameter id.'),
      '#required' => TRUE,
    ];
    $form['blockchainTypeId'] = [
      '#type' => 'select',
      '#default_value' => $blockchain_node->getAddressSource(),
      '#title' => $this->t('Blockchain type id'),
      '#description' => $this->t('Blockchain type id.'),
      '#required' => TRUE,
      '#options' => $this->blockchainService->getConfigService()->getList(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\blockchain\Entity\BlockchainNodeInterface $blockchainNode */
    $blockchainNode = $this->entity;
    if ($blockchainNode->isNew()) {
      $blockchainNode->setId(
        $blockchainNode->generateId()
      );
    }
    $status = $blockchainNode->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Blockchain Node.', [
          '%label' => $blockchainNode->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Blockchain Node.', [
          '%label' => $blockchainNode->label(),
        ]));
    }
    $form_state->setRedirectUrl($blockchainNode->toUrl('collection'));
  }

}
