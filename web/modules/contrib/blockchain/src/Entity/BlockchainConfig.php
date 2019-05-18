<?php

namespace Drupal\blockchain\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Blockchain config entity.
 *
 * @ConfigEntityType(
 *   id = "blockchain_config",
 *   label = @Translation("Blockchain config"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\blockchain\BlockchainConfigListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\blockchain\Form\BlockchainConfigForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\blockchain\BlockchainConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "blockchain_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/blockchain_config/{blockchain_config}/edit",
 *     "collection" = "/admin/structure/blockchain/blockchain_config"
 *   }
 * )
 */
class BlockchainConfig extends ConfigEntityBase implements BlockchainConfigInterface {

  /**
   * The Blockchain config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Blockchain config label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Blockchain id.
   *
   * @var string
   */
  protected $blockchainId;

  /**
   * The Blockchain node id.
   *
   * @var string
   */
  protected $nodeId;

  /**
   * The Blockchain type.
   *
   * @var string
   */
  protected $type;

  /**
   * Is auth used.
   *
   * @var string
   */
  protected $auth;

  /**
   * Address filter type.
   *
   * @var string
   */
  protected $filterType;

  /**
   * Address filter list.
   *
   * @var string
   */
  protected $filterList;

  /**
   * Type of pool management.
   *
   * @var string
   */
  protected $poolManagement;

  /**
   * TYpe of announce management.
   *
   * @var string
   */
  protected $announceManagement;

  /**
   * Pool management interval.
   *
   * @var string
   */
  protected $intervalPool;

  /**
   * Pool management timeout.
   *
   * @var string
   */
  protected $timeoutPool;

  /**
   * Announce management interval.
   *
   * @var string
   */
  protected $intervalAnnounce;

  /**
   * Pull size announce.
   *
   * @var string
   */
  protected $pullSizeAnnounce;

  /**
   * Search size announce.
   *
   * @var string
   */
  protected $searchIntervalAnnounce;

  /**
   * POW position.
   *
   * @var string
   */
  protected $powPosition;

  /**
   * POW expression.
   *
   * @var string
   */
  protected $powExpression;

  /**
   * Data handler id.
   *
   * @var string
   */
  protected $dataHandler;

  /**
   * Allow not secure prorocol schema.
   *
   * @var bool
   */
  protected $allowNotSecure;

  /**
   * {@inheritdoc}
   */
  public function getBlockchainId() {

    return $this->blockchainId;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockchainId($blockchainId) {

    $this->blockchainId = $blockchainId;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeId() {

    return $this->nodeId;
  }

  /**
   * {@inheritdoc}
   */
  public function setNodeId($nodeId) {

    $this->nodeId = $nodeId;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {

    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {

    $this->type = $type;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuth() {

    return $this->auth;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuth($authId) {

    $this->auth = $authId;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterType() {

    return $this->filterType;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilterType($filterType) {

    $this->filterType = $filterType;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterList() {

    return $this->filterList;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilterList($filterList) {

    $this->filterList = $filterList;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPoolManagement() {

    return $this->poolManagement;
  }

  /**
   * {@inheritdoc}
   */
  public function setPoolManagement($poolManagement) {

    $this->poolManagement = $poolManagement;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAnnounceManagement() {

    return $this->announceManagement;
  }

  /**
   * {@inheritdoc}
   */
  public function setAnnounceManagement($announceManagement) {

    $this->announceManagement = $announceManagement;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervalPool() {

    return $this->intervalPool;
  }

  /**
   * {@inheritdoc}
   */
  public function setIntervalPool($intervalPool) {

    $this->intervalPool = $intervalPool;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeoutPool() {

    return $this->timeoutPool;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimeoutPool($timeoutPool) {

    $this->timeoutPool = $timeoutPool;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getIntervalAnnounce() {

    return $this->intervalAnnounce;
  }

  /**
   * {@inheritdoc}
   */
  public function setIntervalAnnounce($intervalAnnounce) {

    $this->intervalAnnounce = $intervalAnnounce;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPowPosition() {

    return $this->powPosition;
  }

  /**
   * {@inheritdoc}
   */
  public function setPowPosition($powPosition) {

    $this->powPosition = $powPosition;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPowExpression() {

    return $this->powExpression;
  }

  /**
   * {@inheritdoc}
   */
  public function setPowExpression($powExpression) {

    $this->powExpression = $powExpression;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataHandler() {

    return $this->dataHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataHandler($dataHandler) {

    $this->dataHandler = $dataHandler;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllowNotSecure() {

    return $this->allowNotSecure;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllowNotSecure($allowNotSecure) {

    $this->allowNotSecure = $allowNotSecure;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {

    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {

    $this->id = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {

    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {

    $this->label = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPullSizeAnnounce() {

    return $this->pullSizeAnnounce;
  }

  /**
   * {@inheritdoc}
   */
  public function setPullSizeAnnounce($pullSizeAnnounce) {

    $this->pullSizeAnnounce = $pullSizeAnnounce;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchIntervalAnnounce() {

    return $this->searchIntervalAnnounce;
  }

  /**
   * {@inheritdoc}
   */
  public function setSearchIntervalAnnounce($searchIntervalAnnounce) {

    $this->searchIntervalAnnounce = $searchIntervalAnnounce;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBlockchainFilterListAsArray(array $blockchainFilterList) {

    $blockchainFilterList = implode("\r\n", $blockchainFilterList);
    return $this->setFilterList($blockchainFilterList);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockchainFilterListAsArray() {

    if ($list = $this->getFilterList()) {
      $parsed = preg_split('~\R~', $list);
      array_walk($parsed, 'trim');

      return $parsed;
    }

    return [];
  }

}
