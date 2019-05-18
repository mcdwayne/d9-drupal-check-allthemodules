<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\ban\BanIpManagerInterface;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting (and unbanning) a host entity.
 *
 * @ingroup httpbl
 */
class HostDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The host storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entity_manager;

  /**
   * The ban IP manager.
   *
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $banManager;

  /**
   * A logger arbitration instance.
   *
   * @var \Drupal\httpbl\Logger\HttpblLogTrapperInterface
   */
  protected $logTrapper;

  /**
   * Constructs a HostDeleteForm object with additional services.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\ban\BanIpManagerInterface $banManager
   *   The Ban manager.
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logTrapper
   *   The log manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service (for tracking changes).
  */
  public function __construct(EntityRepositoryInterface $entity_repository, BanIpManagerInterface $banManager, HttpblLogTrapperInterface $logTrapper, TimeInterface $time = NULL) {
    parent::__construct($entity_repository, NULL, $time);
    $this->banManager = $banManager;
    $this->logTrapper = $logTrapper;
 }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('ban.ip_manager'),
      $container->get('httpbl.logtrapper'),
      $container->get('datetime.time')
    );
  }

 /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    if ($this->banManager->isBanned($this->entity->label())) {
      return $this->t('Are you sure you want to delete and unban evaluated host %name?', array('%name' => $this->entity->label()));
    }
    else {
      return $this->t('Are you sure you want to delete evaluated host %name?', array('%name' => $this->entity->label()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    if ($this->banManager->isBanned($this->entity->label())) {
      return $this->t('This evaluated host is also banned (Drupal core Ban).  If deleted it will be unbanned. This action cannot be undone.');
    }

    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the evaluated hosts list.
   */
  public function getCancelUrl() {
    return new Url('entity.host.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();

    if ($this->banManager->isBanned($entity->host_ip->value)) {
      $this->banManager->unbanIp($entity->host_ip->value);
      $unbanned = TRUE;
    }
    else {
      $unbanned = FALSE;
    }
    //Delete the host.
    $entity->delete();
    $user = $this->currentUser();
    $user = $user->getDisplayName();
    
    if ($unbanned) {
      $this->logTrapper->trapNotice('@type deleted and unbanned: @title, by user @user. Source: @source.',
        array(
          '@type' => $this->entity->bundle(),
          '@title' => $this->entity->label(),
          '@user' => $user,
          '@source' => $this->entity->getSource(),
          'link' => $this->entity->projectLink(),
        ));
      drupal_set_message($this->t('Evaluated host @ip was deleted and unbanned.', array('@ip' => $entity->host_ip->value)));

    }
    else {
      $this->logTrapper->trapNotice('@type deleted: @title, by user @user. Source: @source.',
        array(
          '@type' => $this->entity->bundle(),
          '@title' => $this->entity->label(),
          '@user' => $user,
          '@source' => $this->entity->getSource(),
          'link' => $this->entity->projectLink(),
        ));
      drupal_set_message($this->t('Evaluated host @ip was deleted.', array('@ip' => $entity->host_ip->value)));
    }
    $form_state->setRedirect('entity.host.collection');
  }

}
