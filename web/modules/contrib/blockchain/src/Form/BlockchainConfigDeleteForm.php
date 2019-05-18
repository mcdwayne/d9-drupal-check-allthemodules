<?php

namespace Drupal\blockchain\Form;

use Drupal\blockchain\Service\BlockchainServiceInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Blockchain config entities.
 */
class BlockchainConfigDeleteForm extends EntityConfirmFormBase {

  /**
   * Blockchain service.
   *
   * @var \Drupal\blockchain\Service\BlockchainServiceInterface
   */
  protected $blockchainService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static($container->get('blockchain.service'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(BlockchainServiceInterface $blockchainService) {

    $this->blockchainService = $blockchainService;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $exist = $this->blockchainService
      ->getNodeService()
      ->getStorage()
      ->loadByProperties([
        'blockchainTypeId' => $this->entity->id(),
      ]);
    if ($exist) {
      $form = parent::buildForm($form, $form_state);
      unset($form['actions']['submit']);
      $form['description']['#markup'] = $this->t(
        'This item can not be deleted before any related node exists.'
      );

      return $form;
    }
    else {

      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    return new Url('entity.blockchain_config.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addStatus(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
