<?php

namespace Drupal\ssf_comment\Controller;

use Drupal\comment\CommentInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ssf\Bayes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the comment entity.
 *
 * @see \Drupal\comment\Entity\Comment.
 */
class SsfCommentController extends ControllerBase {

  /**
   * The bayesian statistical spam filter.
   *
   * @var \Drupal\ssf\Bayes
   */
  protected $bayes;

  /**
   * The messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a SsfCommentController object.
   *
   * @param \Drupal\ssf\Bayes $bayes
   *   The bayesian statistical spam filter.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   */
  public function __construct(Bayes $bayes, MessengerInterface $messenger) {
    $this->bayes = $bayes;
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
   * Publishes the specified comment.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   A comment entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function commentApprove(CommentInterface $comment) {
    if ($comment->hasField('field_ssf_spam') && $comment->get('field_ssf_spam')->value) {
      $this->bayes->unlearn($comment->get('comment_body')->value, Bayes::SPAM);
      $comment->set('field_ssf_spam', FALSE);
    }
    if ($comment->hasField('field_ssf_ham') && !$comment->get('field_ssf_ham')->value) {
      $this->bayes->learn($comment->get('comment_body')->value, Bayes::HAM);
      $comment->set('field_ssf_ham', TRUE);
    }

    $comment->setPublished(TRUE);
    $comment->save();

    $this->messenger->addStatus($this->t('Comment approved.'));
    $permalink_uri = $comment->permalink();
    $permalink_uri->setAbsolute();
    return new RedirectResponse($permalink_uri->toString());
  }

}
