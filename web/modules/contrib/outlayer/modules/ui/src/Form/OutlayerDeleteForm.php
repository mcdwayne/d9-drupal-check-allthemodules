<?php

namespace Drupal\outlayer_ui\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a Outlayer optionset.
 */
class OutlayerDeleteForm extends EntityConfirmFormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a OutlayerForm object.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the Outlayer optionset %label?', ['%label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.outlayer.collection');
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

    $this->messenger->addMessage($this->t('Optionset %label was deleted', ['%label' => $this->entity->label()]));
    $this->logger('outlayer')->notice('Deleted optionset %oid (%label)', ['%oid' => $this->entity->id(), '%label' => $this->entity->label()]);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
