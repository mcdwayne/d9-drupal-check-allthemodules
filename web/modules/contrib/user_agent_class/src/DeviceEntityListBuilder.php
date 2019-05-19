<?php

namespace Drupal\user_agent_class;

/**
 * Provides a listing of Device entities.
 */
class DeviceEntityListBuilder extends UserAgentEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Device');
    $header['class'] = $this->t('Class in body');
    return $header + parent::buildHeader();
  }

}
