<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSender\NewsletterParameters.
 */

namespace Drupal\nodeletter\NodeletterSender;

use Drupal\nodeletter\Entity\NodeTypeSettings;

/**
 * Newsletter data and options necessary to send a newsletter.
 *
 * @see \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface::send()
 * @see \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface::sendTest()
 *
 */
class NewsletterParameters {

  /**
   * @var string
   */
  private $sender_address;

  /**
   * @var string
   */
  private $sender_name;

  /**
   * @var string
   */
  private $replay_to_address;

  /**
   * @var string
   */
  private $list_id;

  /**
   * @var RecipientSelectorInterface[]
   */
  private $recipient_selectors = [];

  /**
   * @var string
   */
  private $subject;

  /**
   * @var string
   */
  private $template_id;

  /**
   * @var RenderedTemplateVariable[]
   */
  private $template_vars = [];


  public function __construct( $list_id, $subject, $template_id ) {
    $this->list_id = $list_id;
    $this->subject = $subject;
    $this->template_id = $template_id;
  }

//  public function setSenderAddress( $addr ) {
//    $this->sender_address = $addr;
//    return $this;
//  }
//
//  /**
//   * Get e-mail address part of "sender" field in newsletter header.
//   *
//   * The complete header field may be composed
//   * like »"senderName" <senderAddress>«.
//   *
//   * Defaults to the site mail if not set explicitly.
//   *
//   * @see NewsletterParameters::getSenderName()
//   * @see https://tools.ietf.org/html/rfc5322#section-3.6.2
//   *
//   * @return string
//   */
//  public function getSenderAddress() {
//    if (!empty($this->sender_address))
//      return $this->sender_address;
//
//    $site_config = \Drupal::config('system.site');
//    return $site_config->get('mail');
//  }

  public function setSenderName( $name ) {
    $this->sender_name = $name;
    return $this;
  }

  /**
   * Get name part of "sender" field in newsletter header.
   *
   * The complete header field may be composed
   * like »"senderName" <senderAddress>«.
   *
   * Defaults to the site name if not set explicitly.
   *
   * @see NewsletterParameters::getSenderAddress()
   * @see https://tools.ietf.org/html/rfc5322#section-3.6.2
   *
   * @return string
   */
  public function getSenderName() {
    if (!empty($this->sender_name))
      return $this->sender_name;

    $nodeletter_config = \Drupal::config('nodeletter.settings');
    if (!empty($nodeletter_config->get('from_name')))
      return $nodeletter_config->get('from_name');

    $site_config = \Drupal::config('system.site');
    return $site_config->get('name');
  }

  public function setReplyToAddress( $addr ) {
    $this->replay_to_address = $addr;
    return $this;
  }

  /**
   * Get e-mail address of "reply-to" field in newsletter header.
   *
   * Defaults to the nodeletter setting "reply_to_address" or the
   * site mail if not set explicitly.
   *
   * @see NewsletterParameters::getSenderAddress()
   * @see https://tools.ietf.org/html/rfc5322#section-3.6.2
   *
   * @return string
   */
  public function getReplyToAddress() {
    if (!empty($this->replay_to_address))
      return $this->replay_to_address;

    $nodeletter_config = \Drupal::config('nodeletter.settings');
    if (!empty($nodeletter_config->get('reply_to_address')))
      return $nodeletter_config->get('reply_to_address');

    $site_config = \Drupal::config('system.site');
    return $site_config->get('mail');
  }

  public function setListId( $id ) {
    $this->list_id = $id;
    return $this;
  }

  /**
   * Get recipient list ID.
   *
   * @see NodeletterSenderPluginInterface::getRecipientLists()
   *
   * @return string
   */
  public function getListId() {
    return $this->list_id;
  }

  /**
   * Set recipient selectors.
   * 
   * @see NodeletterSenderPluginInterface::getRecipientSelectors()
   *
   * @param RecipientSelectorInterface[] $selectors
   * @return $this
   */
  public function setRecipientSelectors( $selectors ) {
    $this->recipient_selectors = $selectors;
    return $this;
  }

  /**
   * Get recipient selectors.
   *
   * @see NodeletterSenderPluginInterface::getRecipientSelectors()
   *
   * @return RecipientSelectorInterface[]
   */
  public function getRecipientSelectors() {
    return $this->recipient_selectors;
  }

  public function setSubject( $subject ) {
    $this->subject = $subject;
    return $this;
  }

  /**
   * Get newsletter subject.
   *
   * @see https://tools.ietf.org/html/rfc5322#section-3.6.5
   *
   * @return string
   */
  public function getSubject()  {
    return $this->subject;
  }

  public function setTemplateId( $id ) {
    $this->template_id = $id;
    return $this;
  }

  /**
   * Get template ID.
   *
   * @see NodeletterSenderPluginInterface::getTemplates()
   *
   * @return string
   */
  public function getTemplateId()  {
    return $this->template_id;
  }

  public function setTemplateVariables( $vars ) {
    $this->template_vars = $vars;
    return $this;
  }

  /**
   * Get rendered template variables.
   *
   * @see NodeTypeSettings::getTemplateVariables()
   *
   * @return RenderedTemplateVariable[]
   */
  public function getTemplateVariables() {
    return $this->template_vars;
  }

}
