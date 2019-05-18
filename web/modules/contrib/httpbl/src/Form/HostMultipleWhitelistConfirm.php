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
 * Provides a multiple host white-listing (and un-banning) confirmation form.
 */
class HostMultipleWhitelistConfirm extends ConfirmFormBase {

  /**
   * The array of hosts to whitelist.
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
   * Constructs a new HostMultipleWhitelistConfirm form object.
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
    return 'host_multiple_whitelist_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('<p>Any banned hosts will be un-banned.  Already white-listed hosts will be ignored.</p><p>These actions are un-doable by using other actions*.</p>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->hostInfo), 'Are you sure you want to white-list this host?', 'Are you sure you want to white-list these hosts?');
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
    return t('White-list');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve temporary storage.
    $this->hostInfo = $this->tempStoreFactory->get('host_multiple_whitelist_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->hostInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    /** @var \Drupal\httpbl\HostInterface[] $hosts */
    $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

    $items = [];
    // Prepare a list of any matching, white-listed IPs, so we can include the
    // fact they are already white-listed in the confirmation message.
    foreach ($this->hostInfo as $id => $host_ips) {
      foreach ($host_ips as $host_ip) {
        $host = $hosts[$id];
        $host_status = $host->getHostStatus();
        $key = $id . ':' . $host_ip;
        $default_key = $id . ':' . $host_ip;

        // If we have any already white-listed hosts, we theme notice they will
        // be ignored.
        if ($host_status == HTTPBL_LIST_SAFE) {
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('Ignoring @label - <em> already white-listed.</em>', ['@label' => $host->label()]),
            ],
            'ignored hosts' => [
              '#theme' => 'item_list',
            ],
          ];
        }
        // Otherwise just a regular item-list of hosts to be white-listed.
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
      $whitelist_hosts = [];
      $unban_hosts = [];
      /** @var \Drupal\httpbl\HostInterface[] $hosts */
      $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

      foreach ($this->hostInfo as $id => $host_ips) {
        foreach ($host_ips as $host_ip) {
          $host = $hosts[$id];
          $host_status = $host->getHostStatus();

          // If this host is banned...
          if ($this->banManager->isBanned($host_ip)) {
            // Queue the host for un-banning;
            $unban_hosts[$id] = $host;
          }

          // If this host is not already white-listed
          if ($host_status != HTTPBL_LIST_SAFE) {
            // Queue the host for white-listing;
            $whitelist_hosts[$id] = $host;
          }
        }

      }

      if ($unban_hosts) {
        foreach ($unban_hosts as $unban_host) {
          $this->banManager->unbanIp($unban_host->getHostIp());
        }
        $this->logTrapper->trapNotice('Un-banned @count hosts.', array('@count' => count($unban_hosts)));
        $unbanned_count = count($unban_hosts);
        drupal_set_message($this->formatPlural($unbanned_count, 'Un-banned 1 host.', 'Un-banned @count hosts.'));
     }

      if ($whitelist_hosts) {
        $now = \Drupal::time()->getRequestTime();
        $offset = \Drupal::state()->get('httpbl.safe_offset') ?:  10800;
        $timestamp = $now + $offset;
        foreach ($whitelist_hosts as $id => $whitelist_host) {
          $host = $whitelist_host;
          $host->setHostStatus(HTTPBL_LIST_SAFE);
          $host->setExpiry($timestamp);
          $host->setSource(HTTPBL_ADMIN_SOURCE);
          $host->save();
        }
        $whitelist_count = count($whitelist_hosts);
        $this->logTrapper->trapNotice('White-listed @count hosts.', array('@count' => $whitelist_count));
        drupal_set_message($this->formatPlural($whitelist_count, 'White-listed 1 host.', 'White-listed @count hosts.'));
      }
      else {
        // Let user know if there was nothing to do.
        drupal_set_message('No hosts were found banned.  One or more were found already white-listed. There was nothing to do.', 'warning');
      }

      $this->tempStoreFactory->get('host_multiple_whitelist_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.host.collection');
  }

}
