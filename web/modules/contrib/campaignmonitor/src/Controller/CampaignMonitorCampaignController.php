<?php

namespace Drupal\campaignmonitor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Campaign Monitor Lists controller.
 */
class CampaignMonitorCampaignController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function campaignsOverview() {
    $content = [];

    $campaigns_admin_url = Url::fromUri('https://waxeye.createsend.com/subscribers/', ['attributes' => ['target' => '_blank']]);

    $campaigns_empty_message = t('You don\'t have any campaigns configured in your
      Campaign Monitor account, (or you haven\'t configured your API key correctly on
      the Global Settings tab). Head over to @link and create some campaigns, then
      come back here and click "Refresh campaigns from Campaign Monitor"',
      ['@link' => Link::fromTextAndUrl(t('Campaign Monitor'), $campaigns_admin_url)->toString()]);

    $content['campaigns_table'] = [
      '#type' => 'table',
      '#header' => [t('Name'), t('Sent'), t('Link')],
      '#empty' => $campaigns_empty_message,
    ];

    $cm_campaigns = campaignmonitor_campaign_get_campaigns();

    foreach ($cm_campaigns as $key => $cm_campaign) {

      // $details = campaignmonitor_get_list_details($key);
      //      $stats = campaignmonitor_get_list_stats($key);
      $content['campaigns_table'][$key]['name'] = [
        '#markup' => $cm_campaign['Name'],
      // '#type' => 'link',
      //        '#url' => $list_url.
      ];
      $content['campaigns_table'][$key]['sent'] = [
        '#markup' => $cm_campaign['Sent'],
      ];
      $content['campaigns_table'][$key]['link'] = [
        '#markup' => $cm_campaign['Link'],
      ];
      // $content['campaigns_table'][$key]['operations'] = array(
      //        '#markup' => implode(' ', $operations),
      //
      //      );.
    }

    $refresh_url = Url::fromRoute('campaignmonitor.refresh_campaigns', ['destination' => 'admin/config/services/campaignmonitor/campaigns']);

    $content['refresh'] = [
      '#type' => 'container',
    ];

    $content['refresh']['refresh_link'] = [
      '#title' => 'Refresh campaigns from Campaign Monitor',
      '#type' => 'link',
      '#url' => $refresh_url,
    ];

    // $create_url = Url::fromRoute('campaignmonitor.list_create_form', array('destination' => 'admin/config/services/campaignmonitor/campaigns'));
    //
    //    $content['create'] = array(
    //      '#type' => 'container'
    //    );
    //
    //    $content['create']['create_link'] = array(
    //      '#title' => 'Create a new list',
    //      '#type' => 'link',
    //      '#url' => $create_url
    //    );.
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function draftsOverview() {
    $content = [];

    $drafts_admin_url = Url::fromUri('https://waxeye.createsend.com/subscribers/', ['attributes' => ['target' => '_blank']]);

    $drafts_empty_message = t('You don\'t have any drafts configured in your
      Campaign Monitor account, (or you haven\'t configured your API key correctly on
      the Global Settings tab). Head over to @link and create some drafts, then
      come back here and click "Refresh drafts from Campaign Monitor"',
      ['@link' => Link::fromTextAndUrl(t('Campaign Monitor'), $drafts_admin_url)->toString()]);

    $content['drafts_table'] = [
      '#type' => 'table',
      '#header' => [t('Name'), t('Author'), t('Created'), t('Link')],
      '#empty' => $drafts_empty_message,
    ];

    $cm_drafts = campaignmonitor_campaign_get_drafts();

    // $total_webhook_events = count(campaignmonitor_default_webhook_events());
    foreach ($cm_drafts as $key => $cm_campaign) {

      // $details = campaignmonitor_get_list_details($key);
      //      $stats = campaignmonitor_get_list_stats($key);
      $content['drafts_table'][$key]['name'] = [
        '#markup' => $cm_campaign['Name'],
      // '#type' => 'link',
      //        '#url' => $list_url.
      ];
      $content['drafts_table'][$key]['author'] = [
        '#markup' => $cm_campaign['From'],

      ];
      $content['drafts_table'][$key]['sent'] = [
        '#markup' => $cm_campaign['Created'],
      ];
      $content['drafts_table'][$key]['link'] = [
        '#type' => 'link',
        '#title' => 'Preview',
        '#url' => Url::fromUri($cm_campaign['Link']),
      ];
    }

    $refresh_url = Url::fromRoute('campaignmonitor.refresh_drafts', ['destination' => 'admin/config/services/campaignmonitor/drafts']);

    $content['refresh'] = [
      '#type' => 'container',
    ];

    $content['refresh']['refresh_link'] = [
      '#title' => 'Refresh drafts from Campaign Monitor',
      '#type' => 'link',
      '#url' => $refresh_url,
    ];

    // $create_url = Url::fromRoute('campaignmonitor.list_create_form', array('destination' => 'admin/config/services/campaignmonitor/drafts'));
    //
    //    $content['create'] = array(
    //      '#type' => 'container'
    //    );
    //
    //    $content['create']['create_link'] = array(
    //      '#title' => 'Create a new list',
    //      '#type' => 'link',
    //      '#url' => $create_url
    //    );.
    return $content;
  }

  /**
   * Callback to clear config cache.
   */
  public function clearCampaignCache() {
    $caches = ['cache.config', 'cache.data'];
    campaignmonitor_clear_cache($caches);
    // drupal_set_message('Campaign Monitor caches cleared');.
    return new RedirectResponse('/admin/config/services/campaignmonitor/campaigns');
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
