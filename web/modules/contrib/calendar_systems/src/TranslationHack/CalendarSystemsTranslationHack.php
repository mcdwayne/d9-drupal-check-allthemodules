<?php

namespace Drupal\calendar_systems\TranslationHack;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

trait CalendarSystemsTranslationHack {

  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);

    $values = &$form_state->getValue('content_translation');
    if (!$values || !is_array($values) || empty($values['created'])) {
      return;
    }

    $cal = calendar_systems_factory();
    if (!$cal) {
      return;
    }

    $created = preg_replace('/ \+.*?$/', '', $values['created']);
    if (!$cal->parse($created, 'Y-m-d H:i:s')) {
      return;
    }

    $time = $cal->getTimestamp();
    $this->manager->getTranslationMetadata($entity)->setCreatedTime($time);
  }

}
