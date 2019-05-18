<?php

namespace Drupal\campaignmonitor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Campaign Monitor Lists controller.
 */
class CampaignMonitorListsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function overview() {
    $content = [];

    $lists_admin_url = Url::fromUri('https://waxeye.createsend.com/subscribers/', ['attributes' => ['target' => '_blank']]);

    $lists_empty_message = t('You don\'t have any lists configured in your
      Campaign Monitor account, (or you haven\'t configured your API key correctly on
      the Global Settings tab). Head over to @link and create some lists, then
      come back here and click "Refresh lists from Campaign Monitor"',
      ['@link' => Link::fromTextAndUrl(t('Campaign Monitor'), $lists_admin_url)->toString()]);

    $content['lists_table'] = [
      '#type' => 'table',
      '#header' => [t('Name'), t('List ID'), t('Subscribed'), t('Operations')],
      '#empty' => $lists_empty_message,
    ];

    $cm_lists = campaignmonitor_get_lists();
    // $total_webhook_events = count(campaignmonitor_default_webhook_events());
    foreach ($cm_lists as $key => $cm_list) {

      $details = campaignmonitor_get_list_details($key);
      $stats = campaignmonitor_get_list_stats($key);

      $edit_link = Link::fromTextAndUrl(t('Edit'),
        Url::fromUri('internal:/admin/config/services/campaignmonitor/list/' . $key . '/edit'))->toString();

      $delete_link = Link::fromTextAndUrl(t('Delete'),
        Url::fromUri('internal:/admin/config/services/campaignmonitor/list/' . $key . '/delete'))->toString();

      $operations = [
        'Edit' => $edit_link,
        'Delete' => $delete_link,
      ];

      $list_options = campaignmonitor_get_list_settings($key);
      if (isset($list_options['status']['enabled']) && !$list_options['status']['enabled']) {
        // Add enable operation.
        $class = 'campaignmonitor-list-disabled';

        $link = Link::fromTextAndUrl(t('Enable'),
          Url::fromUri('internal:/admin/config/services/campaignmonitor/list/' . $key . '/enable'))->toString();

        $operations['enable'] = $link;
      }
      else {
        // Add disable operation.
        $class = 'campaignmonitor-list-enabled';
        $link = Link::fromTextAndUrl(t('Disable'),
          Url::fromUri('internal:/admin/config/services/campaignmonitor/list/' . $key . '/disable'))->toString();

        $operations['disable'] = $link;
      }
      // $enabled_webhook_events = count(campaignmonitor_enabled_webhook_events($cm_list->id));
      //      $webhook_url = Url::fromRoute('campaignmonitor.webhook', array('list_id' => $cm_list->id));
      //      $webhook_link = Link::fromTextAndUrl('update', $webhook_url);
      //
      //      $webhook_status = $enabled_webhook_events . ' of ' . $total_webhook_events . ' enabled (' .  $webhook_link->toString() . ')';.
      // $list_url = Url::fromUri('https://admin.campaignmonitor.com/lists/dashboard/overview?id=' . $cm_list->id, array('attributes' => array('target' => '_blank')));.
      $content['lists_table'][$key]['name'] = [
        '#markup' => $cm_list['name'],
      // '#type' => 'link',
      //        '#url' => $list_url.
      ];
      $content['lists_table'][$key]['id'] = [
        '#markup' => $key,
      ];
      $content['lists_table'][$key]['stats'] = [
        '#markup' => $stats['TotalActiveSubscribers'] . ' / ' . $stats['TotalUnsubscribes'],
      ];
      $content['lists_table'][$key]['operations'] = [
        '#markup' => implode(' ', $operations),

      ];
    }

    $refresh_url = Url::fromRoute('campaignmonitor.refresh_lists', ['destination' => 'admin/config/services/campaignmonitor/lists']);

    $content['refresh'] = [
      '#type' => 'container',
    ];

    $content['refresh']['refresh_link'] = [
      '#title' => 'Refresh lists from CampaignMonitor',
      '#type' => 'link',
      '#url' => $refresh_url,
    ];

    $create_url = Url::fromRoute('campaignmonitor.list_create_form', ['destination' => 'admin/config/services/campaignmonitor/lists']);

    $content['create'] = [
      '#type' => 'container',
    ];

    $content['create']['create_link'] = [
      '#title' => 'Create a new list',
      '#type' => 'link',
      '#url' => $create_url,
    ];

    return $content;
  }

  /**
   *
   */
  public function listEnable($list_id) {
    $this->verifyAccess();
    $this->listToggleEnable($list_id);
    drupal_set_message('list enabled');
    return new RedirectResponse('/admin/config/services/campaignmonitor/lists');
  }

  /**
   *
   */
  public function listDisable($list_id) {
    $this->verifyAccess();
    $this->listToggleEnable($list_id);
    drupal_set_message('list disabled');

    return new RedirectResponse('/admin/config/services/campaignmonitor/lists');
  }

  /**
   *
   */
  private function listToggleEnable($list_id) {
    // Get local list information and update enabled state.
    $list_options = campaignmonitor_get_list_settings($list_id);
    $enable = 0;
    if (isset($list_options['status']['enabled'])) {
      $enable = $list_options['status']['enabled'] == 1 ? 0 : 1;
    }
    $list_options['status']['enabled'] = $enable;
    campaignmonitor_set_list_settings($list_id, $list_options);

    // Clear blocks cache.
    //    _block_rehash();
  }

  /**
   * Callback to clear config cache.
   */
  public function clearListCache() {
    $caches = ['cache.config', 'cache.data'];
    campaignmonitor_clear_cache($caches);
    drupal_set_message('Campaign Monitor caches cleared');
    return new RedirectResponse('/admin/config/services/campaignmonitor/lists');
  }

  /**
   * Verify that a token is set for CSRF protection.
   */
  public function verifyAccess() {

    // If (!isset($_GET['token']) || !drupal_valid_token($_GET['token'])) {
    //      drupal_not_found();
    //      module_invoke_all('exit');
    //      exit();
    //    }
  }

}
