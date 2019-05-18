<?php

/**
* @file
* Contains \Drupal\nodeletter\Entity\NodeletterSendingInterface.
*/

namespace Drupal\nodeletter\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\nodeletter\NodeletterSendException;
use Drupal\nodeletter\Plugin\NodeletterSender\RenderedTemplateVariable;
use Drupal\nodeletter\SendingStatus;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a NodeletterSending entity.
 */
interface NodeletterSendingInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * @return UserInterface
   */
  public function getOwner();

  /**
   * @return integer
   */
  public function getNodeId();

  /**
   * @return string
   */
  public function getComment();

  /**
   * @param string $comment
   * @return $this
   */
  public function setComment($comment);

  /**
   * @return string
   */
  public function getServiceProvider();

  /**
   * Get the sending mode.
   *
   * Mode 'real' is an actual newsletter sending to a recipient list.
   * Mode 'test' a sending to just a custom test recipient.
   *
   * @return string Either 'real' oder 'test'
   */
  public function getMode();


  /**
   * Get the recipient if mode is 'test'.
   *
   * @see getMode()
   *
   * @return NULL|string
   */
  public function getTestRecipient();

  /**
   * @return string
   */
  public function getSubject();

  /**
   * @return RenderedTemplateVariable[]
   */
  public function getVariables();

  /**
   * @param string $var_name
   * @return RenderedTemplateVariable
   */
  public function getVariable( $var_name );

  /**
   * @param string $var_name
   * @return boolean
   */
  public function hasVariable( $var_name );

  /**
   * @param RenderedTemplateVariable $variable
   * @return $this
   */
  public function addVariable( RenderedTemplateVariable $variable );

  /**
   * @param RenderedTemplateVariable[] $variables
   * @return $this
   */
  public function addVariables( array $variables );

  /**
   * @param RenderedTemplateVariable $variable
   * @return $this
   */
  public function removeVariable( RenderedTemplateVariable $variable );

  /**
   * @return $this
   */
  public function clearVariables();


  /**
   * Get ID of sending as 3rd party service provider defined it.
   *
   * @return string
   */
  public function getSendingId();


  /**
   * Get ID of recipient list as 3rd party service provider defined it.
   *
   * @return string
   */
  public function getListId();


  /**
   * Get IDs of recipient list segments or selectors as 3rd party service
   * provider defined it.
   *
   * @return string[]
   */
  public function getRecipientSelectorIds();

  /**
   * Get current sending status.
   *
   * Possible states are:
   *   'not created': Not yet pushed to sender service provider [initial status]
   *   'created': Sender service provider created sending
   *   'scheduled': Scheduled for sending out
   *   'sending': Sender service provider is sending
   *   'paused': Paused by sender service provider
   *   'sent': Sender service provider completed sending [final status]
   *   'failed': Sending failed [final status]
   *
   * @see SendingStatus
   *
   * @return string
   */
  public function getSendingStatus();

  /**
   * Update sending status.
   *
   * @see NodeletterSendingInterface::getSendingId()
   *
   * @param string $status
   * @return $this
   */
  public function setSendingStatus($status);

  /**
   * @return null|integer
   */
  public function getErrorCode();

  /**
   * @see NodeletterSendException
   * @param $code integer
   * @return $this
   */
  public function setErrorCode($code);

  /**
   * @return null|string
   */
  public function getErrorMessage();

  /**
   * @param $message string
   * @return $this
   */
  public function setErrorMessage($message);
}
