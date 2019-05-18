<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\ban\BanIpManagerInterface;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;

/**
 * Provides a multiple host un-ban blacklisted confirmation form.
 */
class HostMultipleUnbanConfirm extends ConfirmFormBase {

  /**
   * The array of hosts to un-ban.
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
   * Constructs a new HostMultipleUnbanConfirm form object.
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
    return 'host_multiple_unban_blacklisted_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('<p>This action will un-ban any selected and banned hosts.  Otherwise, any listed status <em>remains</em> unchanged.</p><p>Any "banned but not blacklisted" hosts will also be un-banned, but that occurrance should be rare and warrants further attention.</p><p>This action can be undone by using other actions.</p>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->hostInfo), 'Are you sure you want to un-ban this blacklisted host?', 'Are you sure you want to un-ban these blacklisted hosts?');
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
    return t('Un-ban');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve temporary storage.
    $this->hostInfo = $this->tempStoreFactory->get('host_multiple_unban_blacklisted_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->hostInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    /** @var \Drupal\httpbl\HostInterface[] $hosts */
    $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));
    $items = [];

    // Prepare a list of any matching, banned IPs, so we can include the fact
    // they are already banned in the confirmation message.  Also check and 
    // warn user if their own IP is in the list!
    foreach ($this->hostInfo as $id => $host_ips) {
      foreach ($host_ips as $host_ip) {
        $host = $hosts[$id];
        $host_status = $host->getHostStatus();
        $key = $id . ':' . $host_ip;
        $default_key = $id . ':' . $host_ip;

        // If we have any non-blacklisted hosts, explain they will be ignored.
        if ($host_status != HTTPBL_LIST_BLACK) {

          // Check also to be certain that it is not somehow banned, anyway.
          if (!$this->banManager->isBanned($host_ip)) {
            $items[$default_key] = [
              'label' => [
                '#markup' => $this->t('@label - <em> is not blacklisted or banned.</em>', ['@label' => $host->label()]),
              ],
              'ignored hosts' => [
                '#theme' => 'item_list',
              ],
            ];
          }
          else {
            // Warn user that a "banned but not blacklisted" occurrance has
            // been found.  This should never happen unless IPs have been banned
            // through direct use of the Ban module.
            // Any "banned but not blacklisted" hosts will be un-banned, but 
            // the situation warrants further attention.
            $items[$default_key] = [
              'label' => [
                '#markup' => $this->t('@label - <em> is banned but not blacklisted!  Restrict access to Ban module!  This host will be un-banned.</em>', ['@label' => $host->label()]),
              ],
              'rescued hosts' => [
                '#theme' => 'item_list',
              ],
            ];
            $banUrl = Url::fromUri('internal:/admin/people/permissions#module-ban');
            $banUrl_options = [
              'attributes' => [
                'target' => '_blank',
               ]];
            $banUrl->setOptions($banUrl_options);
            $banLink = Link::fromTextAndUrl(t('review roles with access to the Ban module'), $banUrl)->toString();
            $message = t('Some hosts were found <strong>banned but not blacklisted</strong>. They will be un-banned.</br>Please @ban.', ['@ban' => $banLink]);
            drupal_set_message($message, 'warning', FALSE);
          }
        }
        // Otherwise just a regular item-list of hosts to be un-banned.
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
      $unban_hosts = [];
      /** @var \Drupal\httpbl\HostInterface[] $hosts */
      $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

      foreach ($this->hostInfo as $id => $host_ips) {
        foreach ($host_ips as $host_ip) {
          $host = $hosts[$id];

          if ($this->banManager->isBanned($host_ip)) {
            // Queue the un-banning of any banned host found;
            $unban_hosts[$id] = $host;
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
      else {
        // Let user know if there was nothing to do.
        drupal_set_message('No hosts were found banned.  There was nothing to do.', 'warning');
      }

      $this->tempStoreFactory->get('host_multiple_unban_blacklisted_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.host.collection');
  }

}
