<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\ban\BanIpManagerInterface;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;

/**
 * Provides a multiple host blacklisting and banning confirmation form.
 */
class HostMultipleBanConfirm extends ConfirmFormBase {

  /**
   * The array of hosts to blacklist and ban.
   *
   * @var string[][]
   */
  protected $hostInfo = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The host entity and storage manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $manager;

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
   * Constructs a new HostMultipleBanConfirm form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\ban\BanIpManagerInterface $banManager
   *   The Ban manager.
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logTrapper
   *   A logger arbitration instance.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager, BanIpManagerInterface $banManager, HttpblLogTrapperInterface $logTrapper) {
    $this->tempStoreFactory = $temp_store_factory;
    //Get the storage info from the EntityTypeManager.
    $this->storage = $manager->getStorage('host');
    $this->banManager = $banManager;
    $this->logTrapper = $logTrapper;
 }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('ban.ip_manager'),
      $container->get('httpbl.logtrapper')
   );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'host_multiple_ban_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $banningEnabled = TRUE ? \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL : $banningEnabled = FALSE;
    if ($banningEnabled) {
      return $this->t('<p>Already banned hosts will be ignored.  Already blacklisted hosts will refresh their expiry.</p><p>These actions are un-doable by using other actions*.</p><p>*If you blacklist yourself, don\'t panic; use "drush sos --stop", fix your IP, then don\'t forget to restart ("drush sos --start").</p>');
    }
    else {
       return $this->t('<p>Already blacklisted hosts will refresh their expiry.</p><p>These actions are un-doable by using other actions*.</p><p>*If you blacklist yourself, don\'t panic; use "drush sos --stop", fix your IP, then don\'t forget to restart ("drush sos --start").</p>');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $banningEnabled = TRUE ? \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL : $banningEnabled = FALSE;
    if ($banningEnabled) {
     return $this->formatPlural(count($this->hostInfo), 'Are you sure you want to blacklist and ban this host?', 'Are you sure you want to blacklist and ban these hosts?');
    }
    else {
      return $this->formatPlural(count($this->hostInfo), 'Are you sure you want to blacklist this host?', 'Are you sure you want to blacklist these hosts?');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.host.collection');
  }

   /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    global $blacklistConfirmText;
    return $blacklistConfirmText;
  }

 /**
   * {@inheritdoc}
   */
  public function setConfirmText($userInList = FALSE, $blacklistConfirmText = NULL) {
    global $blacklistConfirmText;
    $banningEnabled = TRUE ? \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL : $banningEnabled = FALSE;
    
    if($userInList && $banningEnabled) {
      $blacklistConfirmText = t('Blacklist and Ban Hosts -- including ME!');
    }
    elseif($banningEnabled) {
      $blacklistConfirmText = t('Blacklist and Ban');        
    }

    if( $userInList && !$banningEnabled) {
      $blacklistConfirmText = t('Blacklist Hosts -- including ME!');
    }
    elseif(!$banningEnabled) {
      $blacklistConfirmText = t('Blacklist');        
    }
    return $blacklistConfirmText;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global  $blacklistConfirmText;
    // Prepare default confirm text.
    $this->setConfirmText();
    // Retrieve temporary storage.
    $this->hostInfo = $this->tempStoreFactory->get('host_multiple_ban_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->hostInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $request = $this->getRequest();
    $this_user = $request->getClientIp();
    /** @var \Drupal\httpbl\HostInterface[] $hosts */
    $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

    $items = [];
    // Prepare a list of any matching, banned IPs, so we can include the fact
    // they are already banned in the confirmation message.  Also check and 
    // warn user if their own IP is in the list!
    foreach ($this->hostInfo as $id => $host_ips) {
      foreach ($host_ips as $host_ip) {
        $host = $hosts[$id];
        $key = $id . ':' . $host_ip;
        $default_key = $id . ':' . $host_ip;

        // Check if current user's IP is in the list...
        if ($this_user == $host_ip) {
           // Alter confirm button.
           $this->setConfirmText($userInList = TRUE);
           $this->getConfirmText();
           // Theme a warning in the item list.
           $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('<strong>Your IP (@label) is in this list! YOU ARE ABOUT TO BLACKLIST YOURSELF!</strong>', ['@label' => $host->label()]),
            ],
            'me' => [
              '#theme' => 'item_list',
            ],
          ];
        }

        // If we have any already banned hosts, we theme up some extra warning about each
        // of them.
        if ($this->banManager->isBanned($host_ip)) {
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label - <em> Is already banned.</em>', ['@label' => $host->label()]),
            ],
            'ban hosts' => [
              '#theme' => 'item_list',
            ],
          ];
        }
        // Otherwise just a regular item-list of hosts to be blacklisted
        // and banned.
        elseif (!isset($items[$default_key])) {
          $items[$key] = $host->label();
        }
      }
    }

    $form['hosts'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Check if banning is enabled.
    $banningEnabled = TRUE ? \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL : $banningEnabled = FALSE;

    if ($form_state->getValue('confirm') && !empty($this->hostInfo)) {
      $ban_hosts = [];
      $blacklist_hosts = [];
      /** @var \Drupal\httpbl\HostInterface[] $hosts */
      $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

      foreach ($this->hostInfo as $id => $host_ips) {
        foreach ($host_ips as $host_ip) {
          $host = $hosts[$id];
          if ($this->banManager->isBanned($host_ip)) {
            // Only queue the host for blacklisting;
            $blacklist_hosts[$id] = $host;
          }
          elseif (!isset($ban_hosts[$id]) && $banningEnabled) {
            $ban_hosts[$id] = $host;
          }

          $blacklist_hosts[$id] = $host;
       }
      }

      if ($ban_hosts) {
        foreach ($ban_hosts as $ban_host) {
          $this->banManager->banIp($ban_host->getHostIp());
        }
        $this->logTrapper->trapNotice('Banned @count hosts.', array('@count' => count($ban_hosts)));
        $banned_count = count($ban_hosts);        
        drupal_set_message($this->formatPlural($banned_count, 'Banned 1 host.', 'Banned @count hosts.'));
     }

      if ($blacklist_hosts) {
        $now = \Drupal::time()->getRequestTime();
        $offset = \Drupal::state()->get('httpbl.blacklist_offset') ?:  31536000;
        $timestamp = $now + $offset;
        foreach ($blacklist_hosts as $id => $blacklist_host) {
          $host = $blacklist_host;
          $host->setHostStatus(HTTPBL_LIST_BLACK);
          $host->setExpiry($timestamp);
          $host->setSource(HTTPBL_ADMIN_SOURCE);
          $host->save();
        }
        $blacklist_count = count($blacklist_hosts);
        $this->logTrapper->trapNotice('Blacklisted @count hosts.', array('@count' => $blacklist_count));
        drupal_set_message($this->formatPlural($blacklist_count, 'Blacklisted 1 host.', 'Blacklisted @count hosts.'));
      }

      $this->tempStoreFactory->get('host_multiple_ban_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.host.collection');
  }

}
