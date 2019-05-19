<?php

namespace Drupal\webform_digests\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Webform digest entity.
 *
 * @ConfigEntityType(
 *   id = "webform_digest",
 *   label = @Translation("Webform digest"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webform_digests\WebformDigestListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_digests\Form\WebformDigestForm",
 *       "edit" = "Drupal\webform_digests\Form\WebformDigestForm",
 *       "delete" = "Drupal\webform_digests\Form\WebformDigestDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\webform_digests\WebformDigestHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "webform_digest",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "webform" = "webform",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webform_digests/{webform_digest}",
 *     "add-form" = "/admin/structure/webform_digests/add",
 *     "edit-form" = "/admin/structure/webform_digests/{webform_digest}/edit",
 *     "delete-form" = "/admin/structure/webform_digests/{webform_digest}/delete",
 *     "collection" = "/admin/structure/webform_digests"
 *   }
 * )
 */
class WebformDigest extends ConfigEntityBase implements WebformDigestInterface {

  /**
   * The Webform digest ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Webform digest label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Webform digest recipient.
   *
   * @var string
   */
  protected $recipient;

  /**
   * The Webform digest origin email address.
   *
   * @var string
   */
  protected $originator;

  /**
   * The Webform digest subject.
   *
   * @var string
   */
  protected $subject;

  /**
   * The Webform digest body.
   *
   * @var string
   */
  protected $body;

  /**
   * The Webform digest conditions.
   *
   * @var array
   */
  protected $conditions = [];

  /**
   * The webform its related to.
   *
   * @var string
   */
  protected $webform;

  /**
   * Temporary storage of the submissions for token purposes.
   *
   * @var array
   */
  protected $submissions = [];

  /**
   * Get the Webform digest recipient.
   */
  public function getRecipient() {
    return $this->recipient;
  }

  /**
   * Set the Webform digest recipient.
   */
  public function setRecipient(string $recipient) {
    $this->recipient = $recipient;

    return $this;
  }

  /**
   * Get the Webform digest origin email address.
   */
  public function getOriginator() {
    return $this->originator;
  }

  /**
   * Set the Webform digest origin email address.
   */
  public function setOriginator(string $originator) {
    $this->originator = $originator;

    return $this;
  }

  /**
   * Get the Webform digest subject.
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Set the Webform digest subject.
   */
  public function setSubject(string $subject) {
    $this->subject = $subject;

    return $this;
  }

  /**
   * Get the webform its related to.
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Set the webform its related to.
   */
  public function setWebform(int $webform) {
    $this->webform = $webform;

    return $this;
  }

  /**
   * Get the Webform digest body.
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Set the Webform digest body.
   */
  public function setBody($body) {
    $this->body = $body;

    return $this;
  }

  /**
   * Does the digest have conditions.
   */
  public function isConditional() {
    return (empty($this->conditions) || !is_array($this->conditions)) ? FALSE : TRUE;
  }

  /**
   * Get the Webform digest conditions.
   */
  public function getConditions() {
    return $this->conditions;
  }

  /**
   * Set the Webform digest conditions.
   */
  public function setConditions(array $conditions) {
    $this->conditions = $conditions;

    return $this;
  }

  /**
   * Set the temporary Webform digest submissions.
   */
  public function setSubmissions(array $submissions) {
    $this->submissions = $submissions;

    return $this;
  }

  /**
   * Get temporary storage of the submissions for token purposes.
   */
  public function getSubmissionsSummary() {
    return array_map(function ($submission) {
      return $submission->label();
    }, $this->submissions);
  }

  /**
   * Get temporary storage of the submissions for token purposes.
   */
  public function getSubmissionsCount() {
    return count($this->submissions);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (is_array($this->body) && !empty($this->body['value'])) {
      $this->body = $this->body['value'];
    }
    parent::preSave($storage);
  }

}
