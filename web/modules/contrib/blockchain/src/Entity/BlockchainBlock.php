<?php

namespace Drupal\blockchain\Entity;

use Drupal\blockchain\Service\BlockchainService;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Blockchain Block entity.
 *
 * @ingroup blockchain
 *
 * @ContentEntityType(
 *   id = "blockchain_block",
 *   label = @Translation("Blockchain Block"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\blockchain\BlockchainBlockListBuilder",
 *     "views_data" = "Drupal\blockchain\Entity\BlockchainBlockViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\blockchain\Form\BlockchainBlockForm",
 *       "add" = "Drupal\blockchain\Form\BlockchainBlockForm",
 *     },
 *     "access" = "Drupal\blockchain\BlockchainBlockAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\blockchain\BlockchainBlockHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "blockchain_block",
 *   admin_permission = "administer blockchain block entities",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/blockchain/blockchain_block/{blockchain_block}",
 *     "add-form" = "/admin/structure/blockchain/blockchain_block/add",
 *     "collection" = "/admin/structure/blockchain/blockchain_block",
 *   },
 *   field_ui_base_route = "blockchain.dashboard",
 *   blockchain_entity = TRUE,
 * )
 */
class BlockchainBlock extends ContentEntityBase implements BlockchainBlockInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['author'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Author of block'))
      ->setDescription(t('The user ID of author of the Blockchain Block entity.'));

    $fields['previous_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Block previous hash'))
      ->setDescription(t('Hash of previous block.'));

    $fields['nonce'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Nonce of block'))
      ->setDescription(t('Nonce number for given block.'))
      ->setRequired(TRUE);

    $fields['data'] = BaseFieldDefinition::create('blockchain_data')
      ->setSetting('case_sensitive', TRUE)
      ->setLabel(t('Block data'))
      ->setDescription(t('Serialized block data.'))
      ->setDisplayOptions('view', [
        'type' => 'blockchain_block_formatter',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'blockchain_block_widget',
        'weight' => 0,
      ]);

    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    return $this->setTimestamp($timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    $this->set('timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthor() {
    return $this->get('author')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthor($author) {
    $this->set('author', $author);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->get('data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($data) {
    $this->set('data', $data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNonce() {
    return $this->get('nonce')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNonce($nonce) {
    $this->set('nonce', $nonce);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousHash() {
    return $this->get('previous_hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviousHash($hash) {
    $this->set('previous_hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toHash() {

    return BlockchainService::instance()
      ->getHashService()
      ->hashBlock($this);
  }

  /**
   * Comparator.
   *
   * @param BlockchainBlockInterface $blockchainBlock
   *   Block to compare.
   *
   * @return bool
   *   Compare result.
   */
  public function equals(BlockchainBlockInterface $blockchainBlock) {

    return $this->toHash() == $blockchainBlock->toHash();
  }

}
