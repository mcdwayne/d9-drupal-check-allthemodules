<?php

namespace Drupal\nodeownership\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\node\NodeInterface;
use Drupal\nodeownership\NodeownershipClaimInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Claim Content entity.
 *
 * @ingroup nodeownership
 *
 * @ContentEntityType(
 *   id = "nodeownership_claim",
 *   label = @Translation("Nodeownership Claim Entity"),
 *   handlers = {
 *     "views_data" = "Drupal\nodeownership\NodeownershipClaimViewsData",
 *     "form" = {
 *       "add" = "Drupal\nodeownership\Form\NodeownershipClaimForm",
 *       "approve" = "Drupal\nodeownership\Form\NodeownershipClaimApproveForm",
 *       "decline" = "Drupal\nodeownership\Form\NodeownershipClaimDeclineForm",
 *     },
 *     "access" = "Drupal\nodeownership\NodeownershipClaimAccessControlHandler",
 *   },
 *   base_table = "nodeownership",
 *   admin_permission = "administer nodeownership_claim entity",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/claims",
 *     "approve" = "/claim/{nodeownership_claim}/approve",
 *     "decline" = "/claim/{nodeownership_claim}/decline",
 *   },
 *   field_ui_base_route = "nodeownership.settings",
 * )
 */
class NodeownershipClaim extends ContentEntityBase implements NodeownershipClaimInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    $changed = $this->getUntranslated()->getChangedTime();
    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
      $changed = max($translation_changed, $changed);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Set Node ID of claimed node.
   */
  public function setNodeId($nid) {
    $this->set('nid', $nid);
    return $this;
  }

  /**
   * Get Node ID of claimed node.
   */
  public function getNodeId() {
    return $this->get('nid')->target_id;
  }

  /**
   * Get claimed node.
   */
  public function getNode() {
    return $this->get('nid')->entity;
  }

  /**
   * Set Claimed Node.
   */
  public function setNode(NodeInterface $node) {
    $this->set('nid', $node->id());
    return $this;
  }

  /**
   * Set Claim Status.
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Get Claim Status.
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
          ->setLabel(t('ID'))
          ->setDescription(t('The ID of the Claim entity.'))
          ->setReadOnly(TRUE)
          ->setSetting('unsigned', TRUE);

    // Standard field.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
                ->setLabel(t('UUID'))
                ->setDescription(t('The UUID of the Claim entity.'))
                ->setReadOnly(TRUE);

    // Standard field.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
          ->setLabel(t('Claimed by'))
          ->setDescription(t('The username of the claiming author.'))
          ->setSetting('target_type', 'user')
          ->setDefaultValueCallback('Drupal\nodeownership\Entity\NodeownershipClaim::getCurrentUserId');

    $fields['nid'] = BaseFieldDefinition::create('entity_reference')
           ->setLabel(t('Claimed node'))
           ->setDescription(t('The nid of the claimed node.'))
           ->setRevisionable(TRUE)
           ->setSetting('target_type', 'node');

    $fields['created'] = BaseFieldDefinition::create('created')
        ->setLabel(t('Created'))
        ->setDescription(t('The time that the claim was created.'))
        ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
         ->setLabel(t('Changed'))
         ->setDescription(t('The time that the claim was changed.'))
         ->setTranslatable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
        ->setLabel(t('Claim Status'))
        ->setDescription(t('Status of the claim'))
        ->setDefaultValue(NODEOWNERSHIP_CLAIM_PENDING);

    $fields['notes'] = BaseFieldDefinition::create('string')
        ->setLabel(t('Notes for claim'))
        ->setDescription(t("Notes for this claim"))
        ->setDisplayOptions('view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -6,
        ))
        ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => -6,
        ))
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);

    $fields['contact'] = BaseFieldDefinition::create('string')
         ->setLabel(t('Contact Email'))
         ->setDescription(t("Email for the contact"))
         ->setDisplayOptions('view', array(
           'label' => 'above',
           'type' => 'string',
           'weight' => -5,
         ))
          ->setDisplayOptions('form', array(
            'type' => 'string',
            'weight' => -5,
          ))
          ->setDisplayConfigurable('form', TRUE)
          ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return array(\Drupal::currentUser()->id());
  }

}
