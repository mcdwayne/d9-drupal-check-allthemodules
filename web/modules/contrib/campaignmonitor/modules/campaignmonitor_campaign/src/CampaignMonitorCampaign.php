<?php

namespace Drupal\campaignmonitor_campaign;

use Drupal\campaignmonitor\CampaignMonitor;

/**
 *
 */
class CampaignMonitorCampaign extends CampaignMonitor {

  /**
 * Used to load the different library parts of the API.
 */
  const CampaignMonitorCAMPAIGN = 'csrest_campaigns.php';
  protected $campaignMonitor;

  /**
   *
   */
  public function __construct() {
    // // Get account information.
    $this->campaignMonitor = CampaignMonitor::GetConnector();

  }

  /**
   * Create API campaign object.
   *
   * @return mixed object CS_REST_Clients | FALSE
   *   The Campaign Monitor client object or FALSE on failure.
   */
  protected function createCampaignObj() {

    if ($this->campaignMonitor->libraryLoad(self::CampaignMonitorCAMPAIGN)) {
      // See http://drupal.stackexchange.com/questions/142247/how-to-autoload-classes-from-a-3rd-party-php-library-in-drupal-8
      return new \CS_REST_Campaigns($this->campaignMonitor->client_id, $this->campaignMonitor->api_key);
    }

    $this->campaignMonitor->addError(WATCHDOG_ERROR, t('Failed to locate the client library.'));
    return FALSE;
  }

  /**
   * Create a new campaign at the Campaign Monitor servers. The side-effect is that
   * the local cache is cleared.
   *
   * @param array $data
   *   Has the following keys:
   *   'Subject' => 'Campaign Subject',
   *   'Name' => 'Campaign Name',
   *   'FromName' => 'Campaign From Name',
   *   'FromEmail' => 'Campaign From Email Address',
   *   'ReplyTo' => 'Campaign Reply To Email Address',
   *   'HtmlUrl' => 'Campaign HTML Import URL',
   *   # 'TextUrl' => 'Optional campaign text import URL',
   *   'ListIDs' => array('First List', 'Second List'),
   *   'SegmentIDs' => array('First Segment', 'Second Segment')
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function createCampaign($data) {
    if ($obj = $this->createCampaignObj(NULL)) {

      $result = $obj->create($this->campaignMonitor->client_id, $data);
      if ($result->was_successful()) {
        // Clear the cache, so the list information can be retrieved again.
        $this->campaignMonitor->clearCache();
        return $result->was_successful();
      }
      else {
        $this->campaignMonitor->addError(WATCHDOG_ERROR, $result->response->Message, $result->http_status_code);
        drupal_set_message(t('Error message: @message', ['@message' => $result->response->Message]));
        return FALSE;
      }
    }
    return FALSE;
  }

}
