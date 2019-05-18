<?php

namespace Drupal\competition_voting\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Session\AccountProxy;
use Drupal\competition\CompetitionJudgingSetup;
use Drupal\competition_voting\CompetitionVoting;
use Drupal\supercookie\SupercookieManager;
use Drupal\supercookie\SupercookieResponse;

/**
 * Competition entry vote form.
 */
class CompetitionEntryVoteForm extends FormBase {

  /**
   * The judging setup library.
   *
   * @var \Drupal\competition\CompetitionJudgingSetup
   */
  protected $judgingSetup;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The supercookie manager service.
   *
   * @var \Drupal\supercookie\SupercookieManager
   */
  protected $supercookieManager;

  /**
   * The supercookie response service.
   *
   * @var \Drupal\supercookie\SupercookieResponse
   */
  protected $supercookieResponse;

  /**
   * The competition voting service.
   *
   * @var \Drupal\competition_voting\CompetitionVoting
   */
  protected $competitionVoting;

  /**
   * Constructs a CompetitionEntryJudgingForm object.
   *
   * @param \Drupal\competition\CompetitionJudgingSetup $judging_setup
   *   The judging setup library.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\supercookie\SupercookieManager $supercookie_manager
   *   The SupercookieManager service.
   * @param \Drupal\supercookie\SupercookieResponse $supercookie_response
   *   The SupercookieResponse service.
   * @param \Drupal\competition_voting\CompetitionVoting $competition_voting
   *   The competition manager.
   */
  public function __construct(CompetitionJudgingSetup $judging_setup, AccountProxy $current_user, SupercookieManager $supercookie_manager, SupercookieResponse $supercookie_response, CompetitionVoting $competition_voting) {

    $this->judgingSetup = $judging_setup;
    $this->currentUser = $current_user;
    $this->supercookieManager = $supercookie_manager;
    $this->supercookieResponse = $supercookie_response;
    $this->competitionVoting = $competition_voting;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('competition.judging_setup'),
      $container->get('current_user'),
      $container->get('supercookie.manager'),
      $container->get('supercookie.response'),
      $container->get('competition.voting')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'competition_entry_vote_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $competition_entry = NULL) {

    $competition = $competition_entry->getCompetition();
    $judging = $competition->getJudging();

    $form['#competition'] = $competition;
    $form['#competition_entry'] = $competition_entry;
    $form['#attributes']['class'][] = 'competition-entry-vote-form';

    // This form is initially rendered as a field in
    // competition_voting_competition_entry_view_alter()
    // but is submitted to a separate route so that that form is rebuilt
    // with the correct competition_entry.
    $form['#action'] = '/competition/' . $competition->id() . '/vote/' . $competition_entry->id();

    $form['round_id'] = [
      '#type' => 'value',
      '#value' => $judging->active_round,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Vote'),
      '#attributes' => [
        'class' => ['vote'],
      ],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $success = $this->competitionVoting->vote($form['#competition_entry'], $form_state->getValue('round_id'));

    if ($success) {

      drupal_set_message($this->t("Your vote for entry has been recorded!"));

    }
    else {

      drupal_set_message($this->t("A problem occurred saving your vote."), 'error');

    }

    $form_state->setRedirect('entity.competition_entry.vote', [
      'competition' => $form['#competition']->id(),
    ]);

  }

}
