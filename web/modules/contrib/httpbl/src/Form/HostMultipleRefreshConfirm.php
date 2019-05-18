<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;

/**
 * Provides a multiple host expiry refresh confirmation form.
 */
class HostMultipleRefreshConfirm extends ConfirmFormBase {

  /**
   * The array of hosts to refresh.
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
   * A logger arbitration instance.
   *
   * @var \Drupal\httpbl\Logger\HttpblLogTrapperInterface
   */
  protected $logTrapper;

  /**
   * Constructs a new HostMultipleRefreshConfirm form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logTrapper
   *   A logger arbitration instance.
  */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager, HttpblLogTrapperInterface $logTrapper) {
    $this->tempStoreFactory = $temp_store_factory;
    //Get the storage info from the EntityTypeManager.
    $this->storage = $manager->getStorage('host');
    $this->logTrapper = $logTrapper;
 }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('httpbl.logtrapper')
   );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'host_multiple_refresh_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('<p>Host cron expirations will be refreshed in accordance to their status type (safe, grey or blacklisted).</p><p> This action may be un-doable by using other actions.</p>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->hostInfo), 'Are you sure you want to refresh this host?', 'Are you sure you want to refresh these hosts?');
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
    return t('Refresh Expiry');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->hostInfo = $this->tempStoreFactory->get('host_multiple_refresh_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->hostInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\httpbl\HostInterface[] $hosts */
    $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

    // Build expiry values.
    $safe_offset = \Drupal::state()->get('httpbl.safe_offset') ?:  10800;
    $safe_timestamp = \Drupal::time()->getRequestTime() + $safe_offset;
    $safe_time_message = \Drupal::service('date.formatter')->formatTimeDiffUntil($safe_timestamp);
    $grey_offset = \Drupal::state()->get('httpbl.greylist_offset') ?:  86400;
    $grey_timestamp = \Drupal::time()->getRequestTime() + $grey_offset;
    $grey_time_message = \Drupal::service('date.formatter')->formatTimeDiffUntil($grey_timestamp);
    $black_offset = \Drupal::state()->get('httpbl.blacklist_offset') ?:  31536000;
    $black_timestamp = \Drupal::time()->getRequestTime() + $black_offset;
    $black_time_message = \Drupal::service('date.formatter')->formatTimeDiffUntil($black_timestamp);

    $items = [];
    // Prepare confirmations.
    foreach ($this->hostInfo as $id => $host_ips) {
      foreach ($host_ips as $host_ip) {
        $host = $hosts[$id];
        $status = $host->getHostStatus();
        $key = $id . ':' . $host_ip;
        $default_key = $id . ':' . $host_ip;

        switch ($status) {
          case '0':
            $items[$default_key] = [
              'label' => [
                '#markup' => $this->t('White-listed @label expiry will be refreshed to @time.', ['@label' => $host->label(), '@time' => $safe_time_message]),
              ],
              'host' => [
                '#theme' => 'item_list',
              ],
            ];
            break;
          case '1':
            $items[$default_key] = [
              'label' => [
                '#markup' => $this->t('Blacklisted @label expiry will be refreshed to @time.', ['@label' => $host->label(), '@time' => $black_time_message]),
              ],
              'host' => [
                '#theme' => 'item_list',
              ],
            ];
            break;
          case '2':
            $items[$default_key] = [
              'label' => [
                '#markup' => $this->t('Grey-listed @label expiry will be refreshed to @time.', ['@label' => $host->label(), '@time' => $grey_time_message]),
              ],
              'host' => [
                '#theme' => 'item_list',
              ],
            ];
            break;
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

      // Build expiry values.
      $safe_offset = \Drupal::state()->get('httpbl.safe_offset') ?:  10800;
      $safe_timestamp = \Drupal::time()->getRequestTime() + $safe_offset;
      $safe_time_message = \Drupal::service('date.formatter')->formatTimeDiffUntil($safe_timestamp);
      $grey_offset = \Drupal::state()->get('httpbl.greylist_offset') ?:  86400;
      $grey_timestamp = \Drupal::time()->getRequestTime() + $grey_offset;
      $grey_time_message = \Drupal::service('date.formatter')->formatTimeDiffUntil($grey_timestamp);
      $black_offset = \Drupal::state()->get('httpbl.blacklist_offset') ?:  31536000;
      $black_timestamp = \Drupal::time()->getRequestTime() + $black_offset;
      $black_time_message = \Drupal::service('date.formatter')->formatTimeDiffUntil($black_timestamp);

      // Prepare work arrays for status types.
      $safe_hosts = [];
      $grey_hosts = [];
      $black_hosts = [];
      /** @var \Drupal\httpbl\HostInterface[] $hosts */
      $hosts = $this->storage->loadMultiple(array_keys($this->hostInfo));

      foreach ($this->hostInfo as $id => $host_ips) {
        foreach ($host_ips as $host_ip) {
          $host = $hosts[$id];
          $status = $host->getHostStatus();

          switch ($status) {
            case '0':
              $safe_hosts[$id] = $host;
              break;
            case '1':
              $black_hosts[$id] = $host;
              break;
            case '2':
              $grey_hosts[$id] = $host;
              break;
          }
        }
      }

      if ($safe_hosts) {
        foreach ($safe_hosts as $safe_host) {
          $host = $safe_host;
          $host->setExpiry($safe_timestamp);
          $host->setSource(HTTPBL_ADMIN_SOURCE);
          $host->save();
        }
        $this->logTrapper->trapNotice('Refreshed expiry for @count white-listed hosts to @time.', ['@count' => count($safe_hosts), '@time' => $safe_time_message]);
        $safe_count = count($safe_hosts);
        drupal_set_message($this->formatPlural($safe_count, 'Refreshed 1 white-listed host.', 'Refreshed @count white-listed hosts.'));
     }

      if ($grey_hosts) {
        foreach ($grey_hosts as $grey_host) {
          $host = $grey_host;
          $host->setExpiry($grey_timestamp);
          $host->setSource(HTTPBL_ADMIN_SOURCE);
          $host->save();
        }
        $this->logTrapper->trapNotice('Refreshed expiry for @count grey-listed hosts to @time.', ['@count' => count($grey_hosts), '@time' => $grey_time_message]);
        $grey_count = count($grey_hosts);
        drupal_set_message($this->formatPlural($grey_count, 'Refreshed 1 grey-listed host.', 'Refreshed @count grey-listed hosts.'));
     }

      if ($black_hosts) {
        foreach ($black_hosts as $black_host) {
          $host = $black_host;
          $host->setExpiry($black_timestamp);
          $host->setSource(HTTPBL_ADMIN_SOURCE);
          $host->save();
        }
        $this->logTrapper->trapNotice('Refreshed expiry for @count blacklisted hosts to @time.', ['@count' => count($black_hosts), '@time' => $black_time_message]);
        $black_count = count($black_hosts);
        drupal_set_message($this->formatPlural($black_count, 'Refreshed 1 blacklisted host.', 'Refreshed @count blacklisted hosts.'));
     }


      $this->tempStoreFactory->get('host_multiple_refresh_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('entity.host.collection');
  }

}
