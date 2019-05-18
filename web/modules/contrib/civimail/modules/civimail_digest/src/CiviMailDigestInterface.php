<?php

namespace Drupal\civimail_digest;

/**
 * Interface CiviMailDigestInterface.
 */
interface CiviMailDigestInterface {

  /**
   * The digest as a record in the digest table and is not prepared yet.
   */
  const STATUS_CREATED = 0;

  /**
   * The digest has content associated via previous CiviMail mailings.
   */
  const STATUS_PREPARED = 1;

  /**
   * The digest has already been the subject of a CiviMail mailing.
   */
  const STATUS_SENT = 2;

  /**
   * The digest mailing has failed.
   */
  const STATUS_FAILED = 3;

  /**
   * Checks if the digest is configured as active.
   *
   * As a side effect, displays a warning if inactive.
   *
   * @return bool
   *   The configuration status.
   */
  public function isActive();

  /**
   * Checks if the digest scheduler is configured as active.
   *
   * @return bool
   *   The scheduler configuration status.
   */
  public function isSchedulerActive();

  /**
   * Checks if the digest to be prepared has content.
   *
   * @return bool
   *   The content status for the digest.
   */
  public function hasNextDigestContent();

  /**
   * Returns the last sent or prepared digest timestamp.
   *
   * @return int
   *   The digest timestamp.
   */
  public function getLastDigestTimeStamp();

  /**
   * Previews the digest before its preparation.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Digest preview.
   */
  public function previewDigest();

  /**
   * Collects the nodes that must be part of the digest.
   *
   * As a side effect, it assigns a digest id to each content entity
   * based on the limitations.
   *
   * @return int
   *   Digest id.
   */
  public function prepareDigest();

  /**
   * Views a digest that has already been prepared.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Prepared digest view.
   */
  public function viewDigest($digest_id);

  /**
   * Gets the digests with their status.
   *
   * @return array
   *   List of digests.
   */
  public function getDigests();

  /**
   * Sends a test digest to the configured test groups.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return bool
   *   Digest send status.
   */
  public function sendTestDigest($digest_id);

  /**
   * Sends the digest to the configured groups.
   *
   * @param int $digest_id
   *   Digest id.
   *
   * @return bool
   *   Digest send status.
   */
  public function sendDigest($digest_id);

  /**
   * Returns the digest status label.
   *
   * @param int $status_id
   *   Digest status id.
   *
   * @return string
   *   Status label.
   */
  public function getDigestStatusLabel($status_id);

}
