<?php

namespace Drupal\tealium;

use Drupal\tealium\Data\TealiumJqueryEventBindingInterface;
use Drupal\tealium\Data\TealiumUtagData;

/**
 * Tealium integration main class.
 */
class TealiumDataService implements TealiumDataServiceInterface {

  /**
   * The utag data.
   *
   * @var \Drupal\tealium\Data\TealiumUtagData
   */
  protected $utagData;

  /**
   * The utag link data.
   *
   * @var \Drupal\tealium\Data\TealiumUtagData
   */
  protected $utagLinkData;

  /**
   * The utag view data.
   *
   * @var \Drupal\tealium\Data\TealiumUtagData
   */
  protected $utagViewData;

  /**
   * The utag bind data objects.
   *
   * @var \Drupal\tealium\Data\TealiumUtagData[]
   */
  protected $utagBindObjects = [];

  /**
   * TealiumDataService constructor.
   */
  public function __construct() {
    $this->utagData = new TealiumUtagData();
    $this->utagLinkData = new TealiumUtagData();
    $this->utagViewData = new TealiumUtagData();
  }

  /**
   * {@inheritdoc}
   */
  public function addData($name, $value = NULL) {
    $this->utagData->setDataSourceValue($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->utagData;
  }

  /**
   * {@inheritdoc}
   */
  public function addLinkData($name, $value = NULL) {
    $this->utagLinkData->setDataSourceValue($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkData() {
    return $this->utagLinkData;
  }

  /**
   * {@inheritdoc}
   */
  public function addViewData($name, $value = NULL) {
    $this->utagViewData->setDataSourceValue($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getViewData() {
    return $this->utagViewData;
  }

  /**
   * {@inheritdoc}
   */
  public function addBindData(TealiumJqueryEventBindingInterface $bind_utag_data_event) {
    $this->utagBindObjects[] = $bind_utag_data_event;
  }

  /**
   * {@inheritdoc}
   */
  public function getBindData() {
    return $this->utagBindObjects;
  }

}
