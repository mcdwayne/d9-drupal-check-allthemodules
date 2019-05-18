<?php

/**
 * @file
 * Contains \Drupal\nodeletter\MailchimpApiTrait.
 */

namespace Drupal\nodeletter;


/**
 * Class MailchimpApiTrait
 * @package Drupal\nodeletter
 *
 * @deprecated This will be merged into MailchimpNodeletterSender once.
 */
trait MailchimpApiTrait {


  private $_mailchimp;
  private $_mailchimp_templates;
  private $_mailchimp_lists;
  private $_mailchimp_campaigns;

  /**
   * @return \Mailchimp\Mailchimp
   */
  private function getMailChimpApi() {
    if (empty($this->_mailchimp))
      $this->_mailchimp = mailchimp_get_api_object();
    return $this->_mailchimp;
  }

  /**
   * @return \Mailchimp\MailchimpTemplates
   */
  private function getMailchimpTemplatesApi() {
    if (empty($this->_mailchimp_templates))
      $this->_mailchimp_templates = mailchimp_get_api_object('MailchimpTemplates');
    return $this->_mailchimp_templates;
  }

  /**
   * @return \Mailchimp\MailchimpCampaigns
   */
  private function getMailchimpCampaignsApi() {
    if (empty($this->_mailchimp_campaigns))
      $this->_mailchimp_campaigns = mailchimp_get_api_object('MailchimpCampaigns');
    return $this->_mailchimp_campaigns;
  }

  /**
   * @return \Mailchimp\MailchimpLists
   */
  private function getMailchimpListsApi() {
    if (empty($this->_mailchimp_lists))
      $this->_mailchimp_lists = mailchimp_get_api_object('MailchimpLists');
    return $this->_mailchimp_lists;
  }


  /**
   * @return bool
   */
  private function isMailchimpUsable() {
    return $this->getMailChimpApi() != null;
  }

  /**
   * @return array
   */
  private function getMailChimpTemplates() {
    $mc_tpls = $this->getMailchimpTemplatesApi()->getTemplates();
    return empty($mc_tpls->templates) ? [] : $mc_tpls->templates;
  }

  /**
   * @return array
   */
  private function getMailChimpLists() {
    $mc_lists = $this->getMailchimpListsApi()->getLists();
    return empty($mc_lists->lists) ? [] : $mc_lists->lists;
  }



}
