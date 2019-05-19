<?php

namespace Drupal\ssf_comment\Form;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ssf\Bayes;

/**
 * Class ConfirmSpamForm.
 *
 * @package Drupal\ssf_comment\Form
 */
class ConfirmSpamForm extends ConfirmFormBase {
  protected $toReportId;
  protected $bayes;
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(Bayes $ssf_bayes, MessengerInterface $messenger) {
    $this->bayes = $ssf_bayes;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ssf.bayes'),
      $container->get('messenger')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssf_confirm_spam_comment';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $comment = '') {
    $this->toReportId = (int) $comment;

    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to set this comment as spam and unpublish it?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.comment.edit_form', ['comment' => $this->toReportId]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $comment = Comment::load($this->toReportId);
    if ($comment->hasField('field_ssf_ham') && $comment->get('field_ssf_ham')->value) {
      $this->bayes->unlearn($comment->get('comment_body')->value, Bayes::HAM);
      $comment->set('field_ssf_ham', FALSE);
    }
    if ($comment->hasField('field_ssf_spam') && !$comment->get('field_ssf_spam')->value) {
      $this->bayes->learn($comment->get('comment_body')->value, Bayes::SPAM);
      $comment->set('field_ssf_spam', TRUE);
    }

    $comment->setUnpublished();
    $comment->save();

    $this->messenger->addStatus($this->t('The comment "@title" has been unpublished and marked as spam.', ['@title' => $comment->getSubject()]));

    $url = new Url('comment.admin_approval');
    return $form_state->setRedirectUrl($url);
  }

}
