<?php

namespace Drupal\follow_unfollow\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\follow_unfollow\FollowUnfollowStastistics;

/**
 * Defines form for following and unfollowing content.
 */
class FollowUnfollowForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The nid variable.
   *
   * @var \Drupal\follow_unfollow\FollowUnfollowForm
   */
  protected $nid;

  /**
   * The tid variable.
   *
   * @var \Drupal\follow_unfollow\FollowUnfollowForm
   */
  protected $tid;

  /**
   * The Target Uid storage.
   *
   * @var \Drupal\follow_unfollow\FollowUnfollowForm
   */
  protected $targetUid;

  /**
   * The Author Uid storage.
   *
   * @var \Drupal\follow_unfollow\FollowUnfollowForm
   */
  protected $authorUid;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The statistics.
   *
   * @var \Drupal\follow_unfollow\FollowUnfollowStastistics
   */
  protected $statistics;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('follow_unfollow.statistics')
    );
  }

  /**
   * Class constructor.
   */
  public function __construct(AccountInterface $account, EntityManagerInterface $entityManager, Connection $databaseConnection, RequestStack $requestStack, FollowUnfollowStastistics $statistics) {
    $this->databaseConnection = $databaseConnection;
    $this->entityManager = $entityManager;
    $this->account = $account;
    $this->requestStack = $requestStack;
    $this->statistics = $statistics;
    $this->nid = NULL;
    $this->tid = NULL;
    $this->targetUid = NULL;
    $this->authorUid = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'follow_unfollow_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#theme'] = 'follow_unfollow_form';

    // Get the current argument.
    $path = $this->requestStack->getCurrentRequest()->getpathInfo();
    $path = trim($path, '/');
    $pathArgument = explode('/', $path);
    $authorUid = $this->account->id();

    // Getting current argument details.
    switch ($pathArgument[0]) {
      case 'node':
        $this->nid = $pathArgument['1'];

        break;

      case 'taxonomy':
        $this->tid = $pathArgument['2'];

        break;

      case 'user':
        $this->targetUid = $pathArgument['1'];

        break;
    }
    // Get follow statistics of current content page.
    $followData = $this->statistics->statistics($this->nid, $this->tid, $this->targetUid, $authorUid, 1);

    $followCount = isset($followData->ncount) ? $followData->ncount : 0;
    $form['follow_unfollow_statistics'] = [
      '#markup' => '<div id = "follow-count">' . $followCount . '</div><div class = "follow-text">Following</div>',
      '#prefix' => '<div id = "follow-wrapper">',
      '#suffix' => '</div>',
    ];

    // Nid of node page.
    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $this->nid,
    ];

    // Tid of taxonomy.
    $form['tid'] = [
      '#type' => 'hidden',
      '#value' => $this->tid,
    ];

    // Target Uid of user.
    $form['target_uid'] = [
      '#type' => 'hidden',
      '#value' => $this->targetUid,
    ];

    // Author uid of user.
    $form['author_uid'] = [
      '#type' => 'hidden',
      '#value' => $authorUid,
    ];

    // Check ajax trigger element value.
    $triggerdElement = $form_state->getTriggeringElement();
    if (!empty($triggerdElement)) {
      $followSubmitValue = ($triggerdElement['#name'] == 'Follow') ? $this->t('Unfollow & Alert me') : $this->t('Follow & Alert me');
      $followSubmitName = ($triggerdElement['#name'] == 'Follow') ? 'Unfollow' : 'Follow';
    }
    else {
      $followSubmitValue = !empty($followData->ncount) ? $this->t('Unfollow & Alert me') : $this->t('Follow & Alert me');
      $followSubmitName = !empty($followData->ncount) ? 'Unfollow' : 'Follow';
    }

    // Submit handler.
    $form['submit_button'] = [
      '#type' => 'button',
      '#name' => $followSubmitName,
      '#value' => $followSubmitValue,
      '#prefix' => '<div id = "follow-submit">',
      '#suffix' => '</div>',
      '#attributes' => ['id' => ['edit-submit-button']],
      '#ajax' => [
        'callback' => 'Drupal\follow_unfollow\Form\FollowUnfollowForm::followUnfollowAjaxCallback',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Getting statistics',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements Ajax callback function.
   */
  public function followUnfollowAjaxCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $values = $form_state->getValues();
    $type = NULL;
    $data = NULL;
    $action = NULL;

    $nid = !empty($values['nid']) ? $values['nid'] : NULL;
    $tid = !empty($values['tid']) ? $values['tid'] : NULL;
    $targetUid = !empty($values['target_uid']) ? $values['target_uid'] : NULL;
    $authorUid = !empty($values['author_uid']) ? $values['author_uid'] : NULL;

    // Check type of content and data of content need for email templates.
    if (!empty($nid)) {
      $type = 'node';
      $data = \Drupal::entityManager()->getStorage('node')->load($nid);
    }
    elseif (!empty($tid)) {
      $type = 'taxonomy';
      $data = \Drupal::entityManager()->getStorage('taxonomy_term')->load($tid);
    }
    elseif (!empty($targetUid)) {
      $type = 'user';
      $data = \Drupal::entityManager()->getStorage('user')->load($targetUid);
    }

    // Get follow statistics of current content page.
    $service = \Drupal::service('follow_unfollow.statistics');
    // Checking button action.
    if ($values['submit_button']->getUntranslatedString() == 'Follow & Alert me') {
      $followUserData = $service->statistics($nid, $tid, $targetUid, $authorUid, 0);
      if ($followUserData->ncount) {
        // Update status of follow particular dataset.
        $followUpdate = \Drupal::database()->update('follow_unfollow_statistics')
          ->fields([
            'status' => 1,
            'changed' => REQUEST_TIME,
          ]);
        if (!empty($nid)) {
          $followUpdate->condition('nid', $values['nid'], '=');
        }
        if (!empty($tid)) {
          $followUpdate->condition('tid', $values['tid'], '=');
        }
        if (!empty($targetUid)) {
          $followUpdate->condition('uid', $values['target_uid'], '=');
        }
        $followUpdate->condition('author_uid', $values['author_uid'], '=');
        $followUpdate->execute();

        // Setting action value.
        $action = 'follow';
      }
      else {
        $followInsert = \Drupal::database()->insert('follow_unfollow_statistics')
          ->fields([
            'nid' => !empty($values['nid']) ? $values['nid'] : 0,
            'tid' => !empty($values['tid']) ? $values['tid'] : 0,
            'uid' => !empty($values['target_uid']) ? $values['target_uid'] : 0,
            'author_uid' => !empty($values['author_uid']) ? $values['author_uid'] : 0,
            'status' => 1,
            'created' => REQUEST_TIME,
            'changed' => 0,
          ]);
        $followInsert->execute();
      }
      $form['submit_button']['#name'] = 'Unfollow';
      $form['submit_button']['#value'] = 'Unfollow & Alert me';

      // Setting action value.
      $action = 'unfollow';
    }
    elseif ($values['submit_button']->getUntranslatedString() == 'Unfollow & Alert me') {
      $followUpdate = \Drupal::database()->update('follow_unfollow_statistics')
        ->fields([
          'status' => 0,
          'changed' => REQUEST_TIME,
        ]);
      if (!empty($nid)) {
        $followUpdate->condition('nid', $values['nid'], '=');
      }
      if (!empty($tid)) {
        $followUpdate->condition('tid', $values['tid'], '=');
      }
      if (!empty($targetUid)) {
        $followUpdate->condition('uid', $values['target_uid'], '=');
      }
      $followUpdate->condition('author_uid', $values['author_uid'], '=');
      $followUpdate->execute();

      $form['submit_button']['#name'] = 'Follow';
      $form['submit_button']['#value'] = 'Follow & Alert me';

      // Setting action value.
      $action = 'follow';
    }

    // Email trigger.
    $mailService = \Drupal::service('follow_unfollow.mail');
    $mailService->sendMail($action, $type, $data);

    // Submit button text change.
    $element = \Drupal::service('renderer')->render($form['submit_button']);
    $ajax_response->addCommand(new ReplaceCommand('#follow-submit', $element));

    // Statistics count chnage.
    $followUserData = $service->statistics($nid, $tid, $targetUid, $authorUid, 1);
    $followUserCount = isset($followUserData->ncount) ? $followUserData->ncount : 0;
    $ajax_response->addCommand(new ReplaceCommand('#follow-count', '<div id = "follow-count">' . $followUserCount . '</div>'));

    // Return the AjaxResponse Object.
    return $ajax_response;
  }

}
