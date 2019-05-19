<?php

namespace Drupal\workflow_sms_notify\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\workflow_sms_notify\Entity\WorkflowSmsNotify;

/**
 * Class WorkflowSmsNotificationListBuilder
 */

 class WorkflowSmsNotificationListBuilder extends ConfigEntityListBuilder {
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
        /** @var $entity WorkflowSmsNotify */
        $wid = workflow_url_get_workflow()->id();
        if ($wid <> $entity->getWorkflowId()) {
            return $row;
        }
        $state_options = ['' => 'Any State'];
        $state_options += workflow_get_workflow_state_names($wid, FALSE);
        $trigger = ['on_state_change' => t('On State change'), 'before_state_change' => t('Before State change')];
        $row['label'] = $entity->label();
        $row['from_sid'] = $state_options[$entity->from_sid];
        $row['to_sid'] = $state_options[$entity->to_sid];
        $row['when_to_trigger'] = $trigger[$entity->when_to_trigger];
        $row += parent::buildRow($entity);
        return $row;
    }
    /**
   * {@inheritdoc}
   */
    public function getDefaultOperations(EntityInterface $entity) {
        /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
        $operations = parent::getDefaultOperations($entity);
        return $operations;
    }
 }