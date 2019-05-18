<?php

namespace Drupal\drupal_content_sync\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\drupal_content_sync\Entity\MetaInformation;

/**
 * Builds the form to delete an Flow.
 */
class FlowDeleteForm extends EntityConfirmFormBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * FlowDeleteForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MessengerInterface $messenger) {
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
    return $this->t('Are you sure you want to delete %name? This will also delete all synchronisation meta entities!', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.dcs_flow.collection');
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
    // Delete config related meta entities.
    $meta_entities = \Drupal::entityTypeManager()->getStorage('dcs_meta_info')
      ->loadByProperties(['flow' => $this->getEntity()->id()]);

    foreach ($meta_entities as $meta_entity) {
      $entity = MetaInformation::load($meta_entity->id());
      $entity->delete();
    }

    $links = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties(['link__uri' => 'internal:/admin/content/drupal_content_synchronization/' . $this->entity->id()]);

    if ($link = reset($links)) {
      $link->delete();
      menu_cache_clear_all();
    }

    $this->entity->delete();
    $this->messenger->addMessage($this->t('A synchronization %label has been deleted.', ['%label' => $this->entity->label()]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
