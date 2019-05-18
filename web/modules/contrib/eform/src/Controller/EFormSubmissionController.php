<?php

namespace Drupal\eform\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\eform\Entity\EFormSubmission;
use Drupal\eform\Entity\EFormType;

/**
 * Class EFormSubmissionController.
 *
 * @package Drupal\eform\Controller
 *
 * @todo Does it make sense to have separate confirm page url
 *   or just confirm text on submit url?
 */
class EFormSubmissionController extends EFormControllerBase {

  /**
   * @var SqlContentEntityStorage $entityStorage
   */
  protected $entityStorage;

  /**
   * The _title_callback for the page that renders a single eform submission.
   *
   * @param \Drupal\eform\Entity\EFormSubmission $eform_submission
   *   The current eform submission.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The page title.
   */
  public function submissionTitle(EFormSubmission $eform_submission) {
    $eform_type = EFormType::load($eform_submission->getType())->name;

    $format = 'long';
    $date = \Drupal::service('date.formatter')->format($eform_submission->getChangedTime(), $format);

    $eid = $eform_submission->id();

    return $this->t('@eform_type: @date - @eid',
      array(
        '@eform_type' => $eform_type,
        '@date' => $date,
        '@eid' => $eid,
      )
    );
  }

  /**
   * Return title for submit page.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return string
   */
  public function submitTitle(EFormType $eform_type) {
    // @todo Replace tokens here?
    return $eform_type->loadDefaults()->getFormTitle();
  }

  /**
   * Get the Confirmation Page title.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return string
   */
  public function confirmationTitle(EFormType $eform_type) {
    // @todo Replace tokens here?

    return $eform_type->loadDefaults()->getSubmissionPageTitle();
  }

  /**
   * Provides the EForm submission form.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *   The node type entity for the node.
   *
   * @return array
   *   A EForm submission form.
   */
  public function submitPage(EFormType $eform_type) {
    $eform_type->loadDefaults();
    /** @var \Drupal\eform\Entity\EFormSubmission $eform_submission */
    $eform_submission = $this->getSubmitEFormSubmission($eform_type);
    $resubmit_action = $eform_type->getResubmitAction();
    if ($eform_submission->isSubmitted()) {
      if ($resubmit_action == $eform_type::RESUBMIT_ACTION_DISALLOW) {
        $disallow_text = $eform_type->getDisallowText();
        $form['disallow'] = array(
          '#type' => 'processed_text',
          '#text' => $disallow_text['value'],
          '#format' => $disallow_text['format'],
        );
        return $form;
      }
    }
    $form_mode = $this->getFormMode($eform_submission);
    $form = $this->entityFormBuilder()->getForm($eform_submission, $form_mode);

    if ($this->userHasViewSubmissions($eform_type)) {
      $form['user_submissions_link'] = $this->getUserSubmissionsLink($eform_type);
    }
    return $form;
  }

  /**
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return array
   */
  protected function getUserSubmissionsLink(EFormType $eform_type) {
    $links_output = [];
    if ($view_id = $eform_type->getUserView()) {
      $route_args = [
        'eform_type' => $eform_type->type,
      ];
      $url = Url::fromRoute('entity.eform_submission.user_submissions', $route_args);
      $links_output['#markup'] = $this->l('View your previous submissions', $url);
    }
    return $links_output;

  }

  /**
   * Determine Form Display for that should be used for a submission.
   *
   * @param \Drupal\eform\Entity\EFormSubmission $eform_submission
   *
   * @return string
   *  Id for Form Display Mode
   */
  function getFormMode(EFormSubmission $eform_submission) {
    if ($eform_submission->isDraft()) {
      return 'submit_draft';
    }
    if ($eform_submission->isNew()) {
      return 'submit';
    }
    else {
      return 'submit_previous';
    }
  }

  /**
   * Return confirm page.
   *
   * @todo Should this be called 'submission page' or 'confirm page'.
   *       Decide and make sure UI and code use the same term.
   * @param \Drupal\eform\Entity\EFormType $eform_type
   * @param \Drupal\eform\Entity\EFormSubmission $eform_submission
   *
   * @return array
   */
  public function confirmPage(EFormType $eform_type, EFormSubmission $eform_submission) {
    $output = array();
    $eform_type->loadDefaults();
    $submission_text = $eform_type->getSubmissionText();
    if (!empty($submission_text['value'])) {
      $output['submission_text'] = array(
        '#type' => 'processed_text',
        '#text' => $submission_text['value'],
        '#format' => $submission_text['format'],
      );
    }
    if ($eform_type->isSubmissionShowSubmitted()) {
      // @todo use dependency injection to get entityManager
      $view_builder = \Drupal::entityManager()->getViewBuilder('eform_submission');

      $output['submission'] = $view_builder->view($eform_submission, 'confirm');
      if (!isset($output['submission']['#title'])) {
        $output['submission']['#title'] = $this->t('Submission');
      }
    }
    return $output;

  }

  /**
   * Get the submission that should be used for the current user on the submission form.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getSubmitEFormSubmission(EFormType $eform_type) {

    if ($eform_type->isDraftable()) {
      $eform_submission = $this->getDraftSubmission($eform_type);
    }
    if (empty($eform_submission)) {
      $resubmit_action = $eform_type->getResubmitAction();
      if ($resubmit_action == $eform_type::RESUBMIT_ACTION_NEW || $this->currentUser()
          ->isAnonymous()
      ) {
        $eform_submission = $this->getNewSubmission($eform_type);
      }
      else {
        $eform_submission = $this->getPreviousEFormSubmission($eform_type);
        if (empty($eform_submission)) {
          $eform_submission = $this->getNewSubmission($eform_type);
        }
      }
    }
    return $eform_submission;
  }

  /**
   * @todo Replace this function with dependency injection.
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  protected function entityStorage() {
    return $this->entityManager()->getStorage('eform_submission');
  }

  /**
   * Get new Entityform Submission.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function getNewSubmission(EFormType $eform_type) {
    $eform_submission = $this->entityStorage()
      ->create(array(
        'type' => $eform_type->id(),
      ));
    return $eform_submission;
  }

  /**
   * Determine what submission should be used as the draft submission.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getDraftSubmission(EFormType $eform_type) {
    if ($eform_type->isDraftable()) {
      $query = $this->entityStorage()->getQuery();
      $query->condition('uid', $this->currentUser()->id());
      $query->condition('draft', EFORM_DRAFT);
      $query->condition('type', $eform_type->id());
      // Should not be more than 1 draft.
      $query->sort('created', 'DESC');
      $ids = $query->execute();
      if ($ids) {
        $id = array_shift($ids);
        // @todo Add alter hook here to allow other modules to change.
        //   see Entityform Anonymous sub-module in Drupal 7.
        return $this->entityStorage()->load($id);
      }
    }
    return NULL;
  }

  /**
   * @todo Abandoned?
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function checkSubmitAccess(EFormType $eform_type) {
    return AccessResult::allowed();
  }

  /**
   * Just for development.
   *
   * @todo Remove when bulk operations are added to EForm Views
   *
   * @param $eform_type_str
   *
   * @return array
   */
  public function nukeEm() {
    $query = \Drupal::entityQuery('eform_submission');
    $eids = $query->execute();
    entity_delete_multiple('eform_submission', $eids);
    return [
      '#type' => 'markup',
      '#markup' => 'Nuked!',
    ];
  }

  /**
   * Get the previous submission for the current user.
   *
   * @param \Drupal\eform\Entity\EFormType $eform_type
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function getPreviousEFormSubmission(EFormType $eform_type) {
    $query = $this->entityStorage()->getQuery();
    $query->condition('uid', $this->currentUser()->id());
    $query->condition('type', $eform_type->type);
    if ($eform_type->isDraftable()) {
      $query->sort('draft', 'DESC');
    }
    $query->sort('created', 'DESC');
    $ids = $query->execute();
    if (empty($ids)) {
      return NULL;
    }
    $id = array_shift($ids);
    $eform_submission = $this->entityStorage()->load($id);
    return $eform_submission;
  }

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The date formatter service..
   *
  public function __construct(DateFormatter $date_formatter, RendererInterface $renderer) {
  $this->dateFormatter = $date_formatter;
  $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   *
  public static function create(ContainerInterface $container) {
  return new static(
  $container->get('date.formatter'),
  $container->get('renderer')
  );
  }
   */
}
