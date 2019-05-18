<?php

namespace Drupal\inlinemanual;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to delete topic.
 */
class InlineManualTopicDeleteConfirmForm extends ConfirmFormBase {

  /**
   * The topic ID.
   *
   * @var string
   */
  protected $topic_id;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inlinemanual_admin_topic_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the topic?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return new Url('inlinemanual.topics');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $topic_id
   *   The topic ID to remove.
   */
  public function buildForm(array $form, array &$form_state, $topic_id = '') {
    $this->topic_id = $topic_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $tid_deleted = db_delete('inm_topics')->condition('tid', $this->topic_id)->execute();
    drupal_set_message(t('The topic has been removed from your database.'));

    $form_state['redirect_route'] = $this->getCancelRoute();
  }

}