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
 * Provides a multiple host deletion confirmation form.
 */
class HostMultipleDeleteConfirm extends ConfirmFormBase {

  /**
   * The array of hosts to delete.
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
   * The host storage.
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
   * Constructs a new HostDeleteMultipleConfirm form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\ban\BanIpManagerInterface $banManager
   *   The Ban manager.
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logger
   *   A logger instance.
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
    return 'host_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->hostInfo), 'Are you sure you want to delete this host?', 'Are you sure you want to delete these hosts?');
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
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->hostInfo = $this->tempStoreFactory->get('host_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->hostInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\httpbl\HostInterface[] $hosts */
    $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

    $items = [];
    // Prepare a list of any matching, banned IPs, so we can include the fact
    // they are banned in the confirmation message.
    foreach ($this->hostInfo as $id => $host_ips) {
      foreach ($host_ips as $host_ip) {
        $host = $hosts[$id];
        $key = $id . ':' . $host_ip;
        $default_key = $id . ':' . $host_ip;

        // If we have any banned hosts, we theme up some extra warning about each
        // of them.
        if ($this->banManager->isBanned($host_ip)) {
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label - <em>Will also be un-banned.</em>', ['@label' => $host->label()]),
            ],
            'un-banned hosts' => [
              '#theme' => 'item_list',
            ],
          ];
        }
        // Otherwise just a regular item-list of hosts to be deleted.
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
    if ($form_state->getValue('confirm') && !empty($this->hostInfo)) {
      $total_count = 0;
      $unban_hosts = [];
      $delete_hosts = [];
      /** @var \Drupal\httpbl\HostInterface[] $hosts */
      $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

      foreach ($this->hostInfo as $id => $host_ips) {
        foreach ($host_ips as $host_ip) {
          $host = $hosts[$id];
          if ($this->banManager->isBanned($host_ip)) {
            $unban_hosts[$id] = $host;
            $delete_hosts[$id] = $host;
          }
          elseif (!isset($unban_hosts[$id])) {
            $delete_hosts[$id] = $host;
          }
        }
      }

      if ($unban_hosts) {
        foreach ($unban_hosts as $unban_host) {
          $this->banManager->unbanIp($unban_host->getHostIp());
        }
        $this->logTrapper->trapNotice('Unbanned @count hosts.', array('@count' => count($unban_hosts)));
        $unBanned_count = count($unban_hosts);        
        drupal_set_message($this->formatPlural($unBanned_count, 'Un-banned 1 host.', 'Un-banned @count hosts.'));
     }

      if ($delete_hosts) {
        // Delete directly through storage by sending the array of work for it to do.
        $this->storage->delete($delete_hosts);
        $delete_count = count($delete_hosts);
        $this->logTrapper->trapNotice('Deleted @count hosts.', array('@count' => $delete_count));
        drupal_set_message($this->formatPlural($delete_count, 'Deleted 1 host.', 'Deleted @count hosts.'));
      }

      $this->tempStoreFactory->get('host_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.host.collection');
  }

}
