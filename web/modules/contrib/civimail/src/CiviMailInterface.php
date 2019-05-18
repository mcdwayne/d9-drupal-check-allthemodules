<?php

namespace Drupal\civimail;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface CiviMailInterface.
 */
interface CiviMailInterface {

  /**
   * Prepares the CiviCRM mailing parameters for a Drupal content entity.
   *
   * @param int $from_cid
   *   The CiviCRM contact id for the mailing sender.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity that is the subject of the mailing.
   * @param array $groups
   *   List of CiviCRM group id's.
   *
   * @return array
   *   Parameter to be passed to the CiviCRM Mailing creation.
   */
  public function getEntityMailingParams($from_cid, ContentEntityInterface $entity, array $groups);

  /**
   * Returns the markup for the mailing body wrapped in a mail template.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity used for the body.
   *
   * @return array
   *   Render array of the html mail template.
   */
  public function getMailingTemplateHtml(ContentEntityInterface $entity);

  /**
   * Returns the the mailing body as plain text wrapped in a mail template.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity used for the body.
   *
   * @return array
   *   Render array of the text mail template.
   */
  public function getMailingTemplateText(ContentEntityInterface $entity);

  /**
   * Replaces a text with relative urls by absolute ones.
   *
   * The match is done with urls starting with a slash.
   *
   * @param string $text
   *   Text that contains relative urls.
   *
   * @return string
   *   Text replaced with absolute urls.
   */
  public function absolutizeUrls($text);

  /**
   * Schedules and sends a CiviCRM mailing.
   *
   * @param array $params
   *   The mailing parameters.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity that is the subject of the mailing.
   *
   * @return bool
   *   The mailing status.
   */
  public function sendMailing(array $params, ContentEntityInterface $entity);

  /**
   * Sends a Drupal entity to a test address.
   *
   * @param int $from_cid
   *   The CiviCRM contact id for the mailing sender.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity that is the subject of the mailing.
   * @param string $to_mail
   *   The email address that will receive the test.
   *
   * @return bool
   *   The test status.
   */
  public function sendTestMail($from_cid, ContentEntityInterface $entity, $to_mail);

  /**
   * Fetches the mailing history for an entity.
   *
   * Aggregates the results of the civimail_entity_mailing table
   * and the CiviCRM Mailing API.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity that is the subject of the mailing.
   *
   * @return array
   *   List of CiviCRM mailing history for this entity.
   */
  public function getEntityMailingHistory(ContentEntityInterface $entity);

  /**
   * Fetches a single contact straight from the CiviCRM API.
   *
   * @param array $filter
   *   Optional list of filters.
   *
   * @return array
   *   The contact details.
   */
  public function getContact(array $filter);

  /**
   * Removes CiviCRM tokens from a mail render array.
   *
   * To be used by sendTestMail() because of Mime Mail delegation
   * that ignores the CiviCRM context.
   *
   * @param array $build
   *   The mail render array.
   *
   * @return array
   *   The rendered array without the CiviCRM tokens.
   */
  public function removeCiviCrmTokens(array $build);

  /**
   * Returns the entity for the current route.
   *
   * @param string $entity_type_id
   *   The entity type id for the route match.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The content entity that is the subject of the CiviCRM mailing.
   */
  public function getEntityFromRoute($entity_type_id);

  /**
   * Prepares a list of CiviCRM Groups for select form element.
   *
   * @param array $filter
   *   Group filter.
   *
   * @return array
   *   Map of group labels indexed by group id.
   */
  public function getGroupSelectOptions(array $filter = []);

  /**
   * Prepares a list of CiviCRM Contacts for select form element.
   *
   * @param array $filter
   *   Contact filter.
   *
   * @return array
   *   Map of contact labels indexed by contact id.
   */
  public function getContactSelectOptions(array $filter);

  /**
   * Indicates if CiviCRM requirements are fulfilled.
   *
   * @return bool
   *   The status of the requirements.
   */
  public function hasCiviCrmRequirements();

}
