<?php

namespace Drupal\campaignmonitor_campaign\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\Entity\Node;

/**
 * Campaign Monitor Lists controller.
 */
class CampaignMonitorCampaignCampaignController extends ControllerBase {

  /**
   * The entity handler.
   */
  protected $entityManager;

  /**
   * The current user account.
   */
  protected $account;

  /**
   * Constructs a NodeAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\node\NodeGrantDatabaseStorageInterface $grant_storage
   *   The node grant storage.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    $this->account = \Drupal::currentUser();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

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
      ];
      $content['campaigns_table'][$key]['sent'] = [
        '#markup' => $cm_campaign['Sent'],
      ];
      $content['campaigns_table'][$key]['link'] = [
        '#markup' => $cm_campaign['Link'],
      ];

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

    return $content;
  }

  /**
   * Callback to clear config cache.
   */
  public function clearCampaignCache() {
    $caches = ['cache.config', 'cache.data'];
    campaignmonitor_clear_cache($caches);
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

  /**
   * Determine if menu item should exist
   * Drupal 8 does not appear to have provision for suppressing display of a menu item
   * So using access here as a substitute.
   *
   * @param $node_type
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function nodeTypeAccess($node_type) {
    return AccessResult::allowedIf(campaignmonitor_campaign_type_is_configured($node_type));

  }

  /**
   * Campaign Monitor tab on Node page
   * Here the administrator can:
   *  - send the draft to Campaign monitor.
   *
   * @param $node
   *
   * @return array
   */
  public function NodeOverview($node) {
    $form = \Drupal::formBuilder()->getForm('Drupal\campaignmonitor_campaign\Form\CampaignMonitorCampaignSendForm', $node);
    // Also show the campaign in its selected View mode.
    $node_storage = $this->entityManager->getStorage('node');
    // We use the load function to load a single node object.
    $node = $node_storage->load($node);
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $settings = campaignmonitor_campaign_get_node_settings('all', $node->bundle());
    $render_array = $view_builder->view($node, $settings['view_mode']);
    $filepath = campaignmonitor_campaign_get_filepath($node, 'path');
    // $scan_css = new CampaignMonitorCssScanner($filepath);
    //
    return [
      'node' => $render_array,
      'form' => $form,
    // 'css' => array(
    //        '#markup'=> $scan_css->print_report()
    //      )
    ];
  }

  /**
   * Control access to the node tab for campaignmonitor.
   *
   * @param $node
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function NodeTabAccess($node) {
    $node = Node::load($node);
    $bundle = $node->bundle();
    return AccessResult::allowedIf(
      campaignmonitor_campaign_type_is_configured($bundle) &&
      $this->account->hasPermission("send $bundle campaigns")
    );

  }

}
