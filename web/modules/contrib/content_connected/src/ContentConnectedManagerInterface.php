<?php

namespace Drupal\content_connected;

/**
 * Interface ContentConnectedManagerInterface.
 *
 * @package Drupal\content_connected
 */
interface ContentConnectedManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function getlinkFields();

  /**
   * {@inheritdoc}
   */
  public function getEntityRefrenceFields();

  /**
   * {@inheritdoc}
   */
  public function getLongTextFields();

  /**
   * {@inheritdoc}
   */
  public function renderMatches($nid);

}
