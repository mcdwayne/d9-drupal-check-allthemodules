<?php

namespace Drupal\workflow_notifications\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\workflow_notifications\Entity\WorkflowNotification;

/**
 * Class WorkflowNotificationListBuilder
 */
class WorkflowNotificationListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['from_sid'] = $this->t('From State');
    $header['to_sid'] = $this->t('To State');
    $header['when_to_trigger'] = $this->t('When To Trigger');
    $header += parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];

    /** @var $entity WorkflowNotification */
    $wid = workflow_url_get_workflow()->id();
    if ($wid <> $entity->getWorkflowId()) {
      return $row;
    }

    $row['label'] = $entity->label();
    $row['from_sid'] = $entity->from_sid;
    $row['to_sid'] = $entity->to_sid;
    $row['when_to_trigger'] = $entity->when_to_trigger;
    $row += parent::buildRow($entity);
    return $row;
  }

}
