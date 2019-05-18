<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface.
 */

namespace Drupal\nodeletter\NodeletterSender;
use Drupal\nodeletter\NodeletterSendException;
use Drupal\nodeletter\SendingNotFoundException;
use Drupal\nodeletter\SendingStatus;


/**
 * Interface for NodeletterSender plugins.
 *
 * NodeletterSender plugins send newsletters through 3rd party services
 * (like MailChimp) based on data (properties, field values, ...) of nodes.
 *
 * @package Drupal\nodeletter\NodeletterSender
 */
interface NodeletterSenderPluginInterface {

  /**
   * Get plugin ID.
   *
   * @return string
   */
  public function id();


  /**
   * Return list of recipient lists.
   *
   * Newsletters may be sent to one of the list.
   * Each node type will have on list defined in its nodeletter settings.
   *
   * @return \Drupal\nodeletter\NodeletterSender\RecipientListInterface[]
   */
  public function getRecipientLists();


  /**
   * Return list of recipient selectors.
   *
   * Users may create a selection of recipients for a newsletter based
   * on those selectors returned from this method.
   *
   * @param string $list_id Recipient List of which selectors are requested.
   *
   * @return \Drupal\nodeletter\NodeletterSender\RecipientSelectorInterface[]
   */
  public function getRecipientSelectors( $list_id );


  /**
   * Return list of available newsletter templates.
   *
   * Newsletter created through nodeletter are meant to be rendered on
   * the third-party service side by a pre defined template before sending.
   * All templates available at the third-party service to nodeletter users
   * may be returned by this method as array of template names.
   *
   * @return NewsletterTemplateInterface[]
   */
  public function getTemplates();
//
//  /**
//   * Return label of a newsletter template.
//   *
//   * @see NodeletterSenderPluginInterface::getTemplates()
//   *
//   * @param string $template_id;
//   * @return NewsletterTemplateInterface[]
//   */
//  public function getTemplateLabel( $template_id );


  /**
   * Send a newsletter through a third-party service.
   *
   * Actually this is *the method* of the whole nodeletter module: It
   * hands the newsletter configuration over to the third-party service for
   * sending.
   *
   * The third-party service may begin immediately with the delivery of
   * of the sending.
   *
   * If the hand over went successfully and the delivery started the
   * ID of the sending (third-party sending ID) may be returned.
   *
   * If anything prevents the hand over or the delivery an exception
   * of type NodeletterSendException may be raised.
   *
   *
   * @param NewsletterParameters $params Data and options for the newsletter
   *   to send.
   * @return string Sending-ID as defined by the third-party service.
   * @throws NodeletterSendException
   */
  public function send( NewsletterParameters $params );


  /**
   * Send a test mail through a third-party service.
   *
   * This function provides editors a way to test their nodeletters before
   * sending them to all recipients.
   *
   * If the hand over went successfully and the delivery started the
   * ID of the sending (third-party sending ID) may be returned.
   *
   * If anything prevents the hand over or the delivery an exception
   * of type NodeletterSendException may be raised.
   *
   *
   * @param NewsletterParameters $params Data and options for the newsletter
   *   to send.
   * @return string Sending-ID as defined by the third-party service.
   * @throws NodeletterSendException
   */
  public function sendTest($recipient, NewsletterParameters $params );


  /**
   * Retrieves current status of an sending from the service provider.
   *
   * Returned status is described by SendingStatus constants.
   * Note that instead of returning SendingStatus::FAILED a
   * NodeletterSendException may be raised. This is because the exception
   * contains details about the failure.
   *
   * @see SendingStatus
   * @param $sending_id string Sending-ID as defined by the third-party service.
   * @return string Status of sending as defined in SendingStatus
   * @throws NodeletterSendException Implies SendingStatus::FAILED.
   * @throws SendingNotFoundException Sending-ID not found at service provider.
   */
  public function retrieveCurrentSendingStatus($sending_id );

}

