<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\httpbl\HostQuery;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\ban\BanIpManagerInterface;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the host entity Add/Edit form.
 *
 * @ingroup httpbl
 */
class HostForm extends ContentEntityForm {

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
   * Constructs a HostForm object with additional services.
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\httpbl\Entity\Host */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );

    $form['edit'] = array(
      '#type' => 'details',
      '#title' => t('Edit Http:BL Host'),
      '#description' => t('Add / Edit an Http:BL host.'),
      '#open' => TRUE,
    );

    $form['edit']['host_ip'] = array(
      '#title' => $this->t('Host IP'),
      '#type' => 'textfield',
      '#size' => 15,
      '#default_value' => $entity->host_ip->value,
      '#maxlength' => 15,
      '#required' => TRUE,
    );

    // Get the expiry offset variables and prepare expiration values.
    $safeOffset = \Drupal::state()->get('httpbl.safe_offset');
    $greyOffset = \Drupal::state()->get('httpbl.greylist_offset');
    $blackOffset = \Drupal::state()->get('httpbl.blacklist_offset');
    $safeExpires = \Drupal::service('date.formatter')->formatTimeDiffUntil($safeOffset + \Drupal::time()->getRequestTime());
    $greyExpires = \Drupal::service('date.formatter')->formatTimeDiffUntil($greyOffset + \Drupal::time()->getRequestTime());
    $blackExpires = \Drupal::service('date.formatter')->formatTimeDiffUntil($blackOffset + \Drupal::time()->getRequestTime());

    // Show the current expiration options associated with each status.
    $form['edit']['host_status'] = array(
      '#title' => $this->t('Host Status'),
      '#type' => 'select',
      '#options' => [
        0 => $this->t('White-listed - Expires in ' . $safeExpires),
        2 => $this->t('Grey-listed - Expires in ' . $greyExpires),
        1 => $this->t('Blacklisted - Expires in ' . $blackExpires),
        ],
      '#default_value' => $entity->host_status->value,
      '#description' => $this->t('Uses expiry times configured in here.'),
      '#required' => TRUE,
    );

    // Advise the user on current state of storage settings.
    if ($storage = \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL) {
      $form['edit']['explain_auto'] = array(
        '#title' => $this->t('Auto-banning is enabled for Blacklisted hosts.  Blacklisting a host will add it to core Ban_ip.'),
        '#type' => 'item',
      );
    }
    else {
      $form['edit']['explain_auto'] = array(
        '#title' => $this->t('Auto banning is NOT enabled for blacklisted hosts.'),
        '#type' => 'item',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /* @var $host \Drupal\httpbl\Entity\Host */
    $host = parent::buildEntity($form, $form_state);
    $values = $form_state->getValues();

    // Get the current route so we can check whether we are adding or editing.
    $route = \Drupal::service('current_route_match')->getCurrentRouteMatch()->getRouteName();

    // If adding a host and the host already exists, don't allow duplicates.
    if ($route == 'httpbl.host_add' && HostQuery::getHostsByIp($values['host_ip'])) {
      $form_state->setErrorByName('duplicate_host_attempt', $this->t('Host @ip already exists!', array('@ip' => $values['host_ip'])));
    }

    $safeOffset = \Drupal::state()->get('httpbl.safe_offset');
    $greyOffset = \Drupal::state()->get('httpbl.greylist_offset');
    $blackOffset = \Drupal::state()->get('httpbl.blacklist_offset');
    $safeExpiry = ($safeOffset + \Drupal::time()->getRequestTime());
    $greyExpiry = ($greyOffset + \Drupal::time()->getRequestTime());
    $blackExpiry = ($blackOffset + \Drupal::time()->getRequestTime());
    switch ($values['host_status']) {
      case '0':
        $host->setExpiry($safeExpiry);
        break;
      case '1':
        $host->setExpiry($blackExpiry);
        break;
      case '2':
        $host->setExpiry($greyExpiry);
        break;
    }
    //$host->setSource(HTTPBL_ADMIN_SOURCE);
    return $host;

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.host.collection');
    $entity = $this->getEntity();
    $previous_source = $entity->getSource();
    $entity->setSource(HTTPBL_ADMIN_SOURCE);
    $entity->save();
    // Get the IP being added or edited.
    $ip = $entity->host_ip->value;
    // Get the user who added / edited this.
    $user = $this->currentUser();
    $user = $user->getDisplayName();
    // Get the storage state (whether we are saving host entities only or also to core Ban_ip for banning blacklisted IPs).
    $storage = \Drupal::state()->get('httpbl.storage');
    // Get the current route so we can check whether we are adding or editing.
    $route = \Drupal::service('current_route_match')->getCurrentRouteMatch()->getRouteName();

    // If this is an edit it could be a status change for an IP previously banned.
    if ($route == 'entity.host.edit_form' && $entity->host_status->value != HTTPBL_LIST_BLACK && $this->banManager->isBanned($ip)) {
      //Unban this IP.
      $this->banManager->unbanIp($ip);
      // Log and message this unbanning.
      $this->logTrapper->trapWarning('A banned @type has been un-banned: @title, by user @user. Previous evaluation source: @source.',
        array(
          '@type' => $this->entity->bundle(),
          '@title' => $this->entity->label(),
          '@user' => $user,
          '@source' => $previous_source,
          'link' => $entity->projectLink(),
      ));
      drupal_set_message($this->t('Previously banned host @ip has been unbanned.', array('@ip' => $entity->host_ip->value)), 'warning');
    }
    // @todo, do better separation of add and edit.
    // If Blacklisted & Banning...
    if ($entity->host_status->value == HTTPBL_LIST_BLACK && $storage == HTTPBL_DB_HH_DRUPAL) {
      // Ban the IP!
      $this->banManager->banIp($ip);
      // Log this banning.
      if ($route == 'httpbl.host_add') {
        $this->logTrapper->trapNotice('@type: added blacklisted and banned @title: by user @user. Source:@source.',
          array(
            '@type' => $this->entity->bundle(),
            '@title' => $this->entity->label(),
            '@user' => $user,
            '@source' => $this->entity->getSource(),
            'link' => $entity->projectLink(),
        ));
      }
      else {
        $this->logTrapper->trapNotice('@type: updated blacklisted and banned @title: by user @user. Previous source: @source.',
          array(
            '@type' => $this->entity->bundle(),
            '@title' => $this->entity->label(),
            '@user' => $user,
            '@source' => $previous_source,
            'link' => $entity->projectLink(),
        ));
      }
      drupal_set_message($this->t('Host @ip was blacklisted and banned.', array('@ip' => $entity->host_ip->value)));
    }
    else {
      // Otherwise, log the edit that was made.
      $this->logTrapper->trapNotice('Evaluated @type edited: @title, by user @user. Previous source: @source.',
        array(
          '@type' => $this->entity->bundle(),
          '@title' => $this->entity->label(),
          '@user' => $user,
          '@source' => $previous_source,
          'link' => $entity->projectLink(),
        ));
    }
    $form_state->setRedirect('entity.host.collection');
  }

}
