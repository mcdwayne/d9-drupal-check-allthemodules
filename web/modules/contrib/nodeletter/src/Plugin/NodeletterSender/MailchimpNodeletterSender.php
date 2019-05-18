<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\NodeletterSender\MailchimpNodeletterSender.
 */

namespace Drupal\nodeletter\Plugin\NodeletterSender;

use Drupal\nodeletter\MailchimpApiTrait;
use Drupal\nodeletter\NodeletterSender\NewsletterParameters;
use Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface;
use Drupal\nodeletter\NodeletterSendException;
use Drupal\nodeletter\SendingStatus;
use GuzzleHttp\Exception\ClientException;
use Mailchimp\MailchimpAPIException;

/**
 * NodeletterSender plugin for MailChimp
 *
 * @see \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface
 *
 * @Plugin(
 *   id = "mailchimp",
 *   label = "MailChimp"
 * )
 *
 */
class MailchimpNodeletterSender implements NodeletterSenderPluginInterface {

  use MailchimpApiTrait;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return "mailchimp";
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientLists() {
    $mc_lists = $this->getMailchimpListsApi()->getLists();
    $lists = [];
    foreach($mc_lists->lists as $mc_list) {
      $l = new MailchimpRecipientList($mc_list->id, $mc_list->name);
      $lists[] = $l;
    }
    return $lists;

  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientSelectors( $list_id ) {

    if (empty($list_id)) {
      throw new \Exception("Bad Argument");
    }

    $lists_api = $this->getMailchimpListsApi();

    $segments = $lists_api->getSegments($list_id);

    $selectors = [];
    foreach($segments->segments as $segment) {
      $s = new MailchimpRecipientSelector(
        $segment->id,
        $segment->name,
        $segment->member_count
      );
      $selectors[] = $s;
    }

    $interest_categories = $lists_api->getInterestCategories($list_id);
    foreach($interest_categories->categories as $interest_category) {
      $c = new MailchimpInterestCategory($interest_category->id,
        $interest_category->title, $interest_category->type,
        $interest_category->display_order);

      $interests = $lists_api->getInterests($list_id, $interest_category->id);
      foreach($interests->interests as $interest) {
        $i = new MailchimpInterestSelector($interest->id, $c, $interest->name,
          $interest->display_order, $interest->subscriber_count);
        $selectors[] = $i;
      }
    }

    return $selectors;

  }

  /**
   * {@inheritdoc}
   */
  public function getTemplates() {

    $api_call_params = [
      'type' => 'user' // @TODO: don't hardcode this template filter!
    ];
    $mc_tpls = $this->getMailchimpTemplatesApi()->getTemplates($api_call_params);
    $templates = [];
    foreach($mc_tpls->templates as $mc_tpl) {
      $t = new MailchimpNewsletterTemplate($mc_tpl->id, $mc_tpl->name);
      $templates[] = $t;
    }
    return $templates;

  }


  /**
   * @param \Drupal\nodeletter\NodeletterSender\NewsletterParameters $params
   * @return string
   * @throws MailchimpAPIException
   */
  protected function createCampaign(NewsletterParameters $params) {

    $campaigns_api = $this->getMailchimpCampaignsApi();

    $campaign_type = 'regular';
    $campaign_recipients = (object) [
      'list_id' => $params->getListId(),
    ];

    $selectors = $params->getRecipientSelectors();
    if (!empty($selectors)) {
      $segment_opts = [
        'match' => 'any'
      ];
      $conditions = [];
      foreach($selectors as $selector) {
        if ($selector instanceof MailchimpRecipientSelector) {
          $segment_opts['saved_segment_id'] = intval($selectors[0]->getId());
        } else if ($selector instanceof MailchimpInterestSelector) {
          $conditions[] = [
            'field' => 'interests-' . $selector->getCategory()->getId(),
            'op' => 'interestcontains',
            'value' => [$selector->getMailchimpInterestId()],
          ];
        }
      }
      if (!empty($conditions)) {
        $segment_opts['conditions'] = $conditions;
      }
      $campaign_recipients->segment_opts = (object) $segment_opts;
    }

    $campaign_settings = (object) [
      'subject_line'=> $params->getSubject(),
      'from_name' => $params->getSenderName(),
      'reply_to' => $params->getReplyToAddress(),
      'auto_footer' => FALSE,
    ];

    $result = $campaigns_api->addCampaign(
      $campaign_type,
      $campaign_recipients,
      $campaign_settings
    );

    $campaign_id = $result->id;
    $campaign_url = $result->archive_url;

    \Drupal::logger('nodeletter')->notice(
      "Created Mailchimp campaign @id: <a href=\"{url}\">{url}</a>",
      [ 'url' => $campaign_url, '@id' => $campaign_id ]
    );

    // Now the campaign is created at mailchimp.
    // Currently it will will stay there even if some error occurs
    // later on. This may mess up the mailchimp account with invalid
    // campaigns.
    // TODO: delete campaign if an error occurs before sending.

    $template_variables = [];
    foreach($params->getTemplateVariables() as $tpl_var) {
      $template_variables[ $tpl_var->getName() ] = $tpl_var->getValue();
    }
    $campaign_template = (object) [
      'id' => intval($params->getTemplateId()),
      'sections' => (object) $template_variables,
    ];


    $campaigns_api->setCampaignContent($campaign_id, [
      'template' => $campaign_template,
    ]);

    return $campaign_id;

  }


  /**
   * Convert a MailchimpAPIException to a NodeletterSendException.
   *
   * @param \Mailchimp\MailchimpAPIException $e
   * @return \Drupal\nodeletter\NodeletterSendException
   */
  protected function convertMailchimpAPIException(MailchimpAPIException $e ) {

    $exception_msg = "General Mailchimp API error";
    $exception_code = NodeletterSendException::CODE_SERVICE_API_ERROR;

    // interpret API exception to get more details about the
    // actual problem
    // @see http://developer.mailchimp.com/documentation/mailchimp/guides/get-started-with-mailchimp-api-3/#errors
    $http_exception = $e->getPrevious();
    if ($http_exception instanceof ClientException) {
      $mailchimp_response = $http_exception->getResponse();
      $response_type = $mailchimp_response->getHeaderLine('Content-Type');
      $response_body = $mailchimp_response->getBody();
      $response_body->rewind();

      if ('application/problem+json; charset=utf-8' == $response_type) {

        $exception_code = NodeletterSendException::CODE_BAD_CONFIG;

        $problem_details = \GuzzleHttp\json_decode($response_body->getContents());
        if (! empty($problem_details->errors)) {
          $exception_msg = "Bad Mailchimp API parameter: ";
          $exception_msg .= $problem_details->errors[0]->message;
        } else {
          $exception_msg = "Bad Mailchimp API parameter: ";
          $exception_msg .= $problem_details->detail;
        }
      }
    }

    return new NodeletterSendException(
      $exception_msg,
      $exception_code,
      $http_exception
    );
  }

  /**
   * {@inheritdoc}
   */
  public function send( NewsletterParameters $params ) {

    try {

      $campaign_id = $this->createCampaign($params);

      $campaigns_api = $this->getMailchimpCampaignsApi();

      $campaigns_api->send($campaign_id);

      \Drupal::logger('nodeletter')->notice(
        "Triggered action \"send\" on Mailchimp campaign @id",
        [ '@id' => $campaign_id ]
      );

      return $campaign_id;

    } catch (MailchimpAPIException $e) {
      throw $this->convertMailchimpAPIException($e);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function sendTest( $recipient, NewsletterParameters $params ) {

    try {

      $campaign_id = $this->createCampaign($params);
      $test_type = 'html'; # could also be "plaintext"

      $campaigns_api = $this->getMailchimpCampaignsApi();
      $campaigns_api->sendTest($campaign_id, [$recipient], $test_type);

      \Drupal::logger('nodeletter')->notice(
        "Triggered action \"test\" on Mailchimp campaign @id",
        [ '@id' => $campaign_id ]
      );

      return $campaign_id;

    } catch (MailchimpAPIException $e) {
      throw $this->convertMailchimpAPIException($e);
    }

  }


  /**
   * {@inheritdoc}
   */
  public function retrieveCurrentSendingStatus($sending_id) {

    try {
      $campaigns_api = $this->getMailchimpCampaignsApi();
      $campaign = $campaigns_api->getCampaign($sending_id);
      $mc_status = $campaign->status;
      switch($mc_status) {
        case 'save':
          return SendingStatus::CREATED;
        case 'paused':
          return SendingStatus::PAUSED;
          break;
        case 'schedule':
          return SendingStatus::SCHEDULED;
          break;
        case 'sending':
          return SendingStatus::SENDING;
          break;
        case 'sent':
          return SendingStatus::SENT;
        default:
          throw new NodeletterSendException(
            "Unknown sending status $mc_status received from MailChimp API",
            NodeletterSendException::CODE_UNDEFINED_ERROR
          );
          break;
      }
    } catch (MailchimpAPIException $e) {
      throw $this->convertMailchimpAPIException($e);
    }
  }

}
