<?php

namespace Drupal\mailchimp_newsletter_campaign;

use Exception;
use Mailchimp\MailchimpCampaigns;

/**
 * Provides methods related to campaign creation and scheduling.
 */
class Campaigns {

  /**
   * Returns all MailChimp lists for a given key.
   *
   * @return array
   *   An array of lists.
   */
  public static function mailchimpGetList() {
    $lists = [];
    try {
      /* @var $mcapi \Mailchimp\MailchimpLists */
      $mcapi = mailchimp_get_api_object('MailchimpLists');
      if (!$mcapi) {
        throw new Exception('Cannot get lists without Mailchimp API. Check API key has been entered.');
      }
      if ($mcapi != NULL) {
        $result = $mcapi->getLists(['count' => 500]);

        if ($result->total_items > 0) {
          foreach ($result->lists as $list) {
            $lists[$list->id] = $list;
          }
        }
      }
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      \Drupal::logger('mailchimp')->error('An error occurred requesting list information from Mailchimp. "{message}"', ['message' => $e->getMessage()]);
    }

    return $lists;
  }

  /**
   * Create a campaign in MailChimp.
   *
   * @param string $template
   *   Template content for campaign.
   * @param object $recipients
   *   List settings for the campaign.
   * @param object $settings
   *   Campaign settings.
   *
   * @return string
   *   New campaign ID.
   */
  public static function mailchimpCreateCampaign($template, $recipients, $settings) {
    $content_parameters = [
      'html' => $template,
    ];
    /* @var $mc_campaigns \Mailchimp\MailchimpCampaigns */
    $mc_campaigns = mailchimp_get_api_object('MailchimpCampaigns');
    try {
      if (!$mc_campaigns) {
        throw new Exception('Cannot create campaign without Mailchimp API. Check API key has been entered.');
      }
      $result = $mc_campaigns->addCampaign(MailchimpCampaigns::CAMPAIGN_TYPE_REGULAR, $recipients, $settings);
      if (!empty($result->id)) {
        $campaign_id = $result->id;
        drupal_set_message(t('Campaign %name (%cid) was successfully saved on Mailchimp.', ['%name' => $settings->title, '%cid' => $campaign_id]));
        $mc_campaigns->setCampaignContent($campaign_id, $content_parameters);
      }
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      \Drupal::logger('mailchimp_newsletter_campaign')->error('An error occurred while creating this campaign on mailchimp: {message}', [
        'message' => $e->getMessage(),
      ]);
      return NULL;
    }
    return $campaign_id;
  }

  /**
   * Schedule a MailChimp campaign.
   *
   * @param string $campaign_id
   *   The ID of the campaign.
   * @param string $schedule_time
   *   The date and time in UTC to schedule the campaign for delivery.
   */
  public static function mailchimpScheduleCampaign($campaign_id, $schedule_time) {

    $mc_campaigns = mailchimp_get_api_object('MailchimpCampaigns');

    try {

      if (!$mc_campaigns) {
        throw new Exception('Cannot schedule campaign without Mailchimp API. Check API key has been entered.');
      }

      $mc_campaigns->schedule($campaign_id, $schedule_time);
    }
    catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
      \Drupal::logger('mailchimp_newsletter_campaign')->error('An error occurred while scheduling this campaign: {message}', [
        'message' => $e->getMessage(),
      ]);
    }
  }

}
