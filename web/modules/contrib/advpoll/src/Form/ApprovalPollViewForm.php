<?php

namespace Drupal\advpoll\Form;

use Drupal\poll\Form\PollViewForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\poll\PollInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Displays banned IP addresses.
 */
class ApprovalPollViewForm extends PollViewForm implements BaseFormIdInterface {

  /**
   * The Poll of the form.
   *
   * @var \Drupal\poll\PollInterface
   */
  protected $poll;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return 'approval_poll_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'approval_poll_view_form_' . $this->poll->id();
  }

  /**
   * Set the Poll of this form.
   *
   * @param \Drupal\poll\PollInterface $poll
   *   The poll that will be set in the form.
   */
  public function setPoll(PollInterface $poll) {
    $this->poll = $poll;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL, $view_mode = 'full') {
    // Add the poll to the form.
    $form['poll']['#type'] = 'value';
    $form['poll']['#value'] = $this->poll;

    $form['#view_mode'] = $view_mode;

    if ($this->showResults($this->poll, $form_state)) {

      // Check if the user already voted. The form is still being built but
      // the Vote button won't be added so the submit callbacks will not be
      // called. Directly check for the request method and use the raw user
      // input.
      if ($request->isMethod('POST') && $this->poll->hasUserVoted()) {
        $input = $form_state->getUserInput();
        if (isset($input['op']) && $input['op'] == $this->t('Vote')) {
          // If this happened, then the form submission was likely a cached page.
          // Force a session for this user so he can see the results.
          drupal_set_message($this->t('Your vote for this poll has already been submitted.'), 'error');
          $_SESSION['poll_vote'][$this->poll->id()] = FALSE;
        }
      }

      $form['results'] = $this->showPollResults($this->poll, $view_mode);

      // For all view modes except full and block (as block displays it as the
      // block title), display the question.
      if ($view_mode != 'full' && $view_mode != 'block') {
        $form['results']['#show_question'] = TRUE;
      }
    }
    else {
      $options = $this->poll->getOptions();
      if ($options) {
        $form['choice'] = array(
          '#type' => 'checkboxes',
          '#title' => t('Choices'),
          '#title_display' => 'invisible',
          '#options' => $options,
        );
      }
      $form['#theme'] = 'poll_vote';
      $form['#entity'] = $this->poll;
      $form['#action'] = $this->poll->url('canonical', ['query' => \Drupal::destination()->getAsArray()]);
      // Set a flag to hide results which will be removed if we want to view
      // results when the form is rebuilt.
      $form_state->set('show_results', FALSE);

      // For all view modes except full and block (as block displays it as the
      // block title), display the question.
      if ($view_mode != 'full' && $view_mode != 'block') {
        $form['#show_question'] = TRUE;
      }

    }

    $form['actions'] = $this->actions($form, $form_state, $this->poll);

    $form['#cache'] = array(
      'tags' => $this->poll->getCacheTags(),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }




  /**
   * Cancel vote submit function.
   *
   * @param array $form
   *   The previous form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\poll\PollVoteStorageInterface $vote_storage */
    $vote_storage = \Drupal::service('poll_vote.storage');
    $vote_storage->cancelVote($this->poll, $this->currentUser());
    \Drupal::logger('poll')->notice('%user\'s vote in Poll #%poll deleted.', array(
      '%user' => $this->currentUser()->id(),
      '%poll' => $this->poll->id(),
    ));
    drupal_set_message($this->t('Your vote was cancelled.'));

    // In case of an ajax submission, trigger a form rebuild so that we can
    // return an updated form through the ajax callback.
    if ($this->getRequest()->query->get('ajax_form')) {
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Save a user's vote submit function.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function save(array $form, FormStateInterface $form_state) {
    $options = array();
    $choices = $form_state->getValue('choice');
    // Save vote.
    /** @var \Drupal\poll\PollVoteStorage $vote_storage */
    $vote_storage = \Drupal::service('poll_vote.storage');

    foreach($choices as $index => $choice) {
      if ($choice) {
        $options['chid'] = $index;
        $options['uid'] = $this->currentUser()->id();
        $options['pid'] = $form_state->getValue('poll')->id();
        $options['hostname'] = \Drupal::request()->getClientIp();
        $options['timestamp'] = time();


        $vote_storage->saveVote($options);
      }
    }

    drupal_set_message($this->t('Your vote has been recorded.'));

    if ($this->currentUser()->isAnonymous()) {
      // The vote is recorded so the user gets the result view instead of the
      // voting form when viewing the poll. Saving a value in $_SESSION has the
      // convenient side effect of preventing the user from hitting the page
      // cache. When anonymous voting is allowed, the page cache should only
      // contain the voting form, not the results.
      $_SESSION['poll_vote'][$form_state->getValue('poll')->id()] = $form_state->getValue('choice');
    }

    // In case of an ajax submission, trigger a form rebuild so that we can
    // return an updated form through the ajax callback.
    if ($this->getRequest()->query->get('ajax_form')) {
      $form_state->setRebuild(TRUE);
    }

    // No explicit redirect, so that we stay on the current page, which might
    // be the poll form or another page that is displaying this poll, for
    // example as a block.
  }

  /**
   * Validates the vote action.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateVote(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('choice')) {
      $form_state->setErrorByName('choice', $this->t('Your vote could not be recorded because you did not select any of the choices.'));
    }
  }

}
