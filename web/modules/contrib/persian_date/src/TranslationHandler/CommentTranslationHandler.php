<?php


namespace Drupal\persian_date\TranslationHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\persian_date\PersianLanguageDiscovery;
use Drupal\persian_date\Plugin\Datetime\PersianDrupalDateTime;
use Drupal\comment\CommentTranslationHandler as BaseCommentTranslationHandler;

class CommentTranslationHandler extends BaseCommentTranslationHandler {
  public function entityFormEntityBuild($entity_type, EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::entityFormEntityBuild($entity_type, $entity, $form, $form_state);
    if (!PersianLanguageDiscovery::isPersian()) {
      return;
    }
    $values = &$form_state->getValue('content_translation', []);
    $metadata = $this->manager->getTranslationMetadata($entity);
    $created = preg_replace('/ \+.*?$/', '', $values['created']);
    $time = !empty($values['created']) ?
      PersianDrupalDateTime::createFromFormat('Y-m-d H:i:s', $created,null,['validate_format' => false])->getTimestamp():
      REQUEST_TIME
    ;
    $metadata->setCreatedTime($time);
  }
}