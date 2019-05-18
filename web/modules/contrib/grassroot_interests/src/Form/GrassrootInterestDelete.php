<?php

/**
 * @file
 * Contains \Drupal\grassroot_interests\Form\GrassrootInterestDelete
 */

namespace Drupal\grassroot_interests\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\grassroot_interests\GrassrootInterestManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting keywords
 */
class GrassrootInterestDelete extends ConfirmFormBase {

  /**
   * @var \Drupal\grassroot_interests\GrassrootInterestManagerInterface
   */
  protected $grassrootManager;

  /**
   * Constructs a new GrassrootInterestForm object.
   *
   * @param \Drupal\grassroot_interests\GrassrootInterestManagerInterface $grassroot_manager
   */
  public function __construct(GrassrootInterestManagerInterface $grassroot_manager) {
    $this->grassrootManager = $grassroot_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grassroot_interests.grassroot_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grassroot_interest_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to unblock %title?', array('%title' => $this->title));
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
  public function getCancelUrl() {
    return new Url('grassroot_interests.grassroot_main');
  }

  /**
   * {@inheritdoc}
   *
   * @param string $url_id
   *   The IP address record ID to unban.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = '', $title = '') {
    if (!$found = $this->grassrootManager->checkKeywords($keyword_id)) {
      throw new NotFoundHttpException();
    }
    $this->title = $title;
    $this->keyword_id = $keyword_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->grassrootManager->deleteKeywords($this->keyword_id);
    $this->logger('user')->notice('Deleted %id', array('%id' => $this->keyword_id));
    drupal_set_message($this->t('The keywords associate with %id was deleted.', array('%id' => $this->keyword_id)));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
