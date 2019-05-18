<?php

namespace Drupal\contest\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\contest\ContestStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the contest edit forms.
 */
class ContestForm extends ContentEntityForm {
  use ContestValidateTrait;

  protected $logger;

  /**
   * Constructs a new EntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logging manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, LoggerChannelFactory $logger) {
    parent::__construct($entity_manager);
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('logger.factory'));
  }

  /**
   * The contest form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function form(array $form, FormStateInterface $form_state) {
    $contest = $this->entity;
    $form['#title'] = $this->t('Edit @label', ['@label' => $contest->label()]);

    return parent::form($form, $form_state, $contest);
  }

  /**
   * Save a contest.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function save(array $form, FormStateInterface $form_state) {
    $contest = $this->entity;
    $update = $contest->id() ? TRUE : FALSE;
    $contest->save();

    if ($update) {
      drupal_set_message($this->t('The contest %contest has been updated.', ['%contest' => $contest->label()]));
    }
    else {
      $this->logger->get('contest')->notice('Contest %contest added.', ['%contest' => $contest->label(), 'link' => $contest->link($contest->label())]);
      drupal_set_message($this->t('The contest %contest has been added.', ['%contest' => $contest->label()]));
    }
    $form_state->setRedirect('contest.contest_list');
  }

  /**
   * Validation function for a contest form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Make sure the contest winners aren't published.
    if (ContestStorage::getPublished($this->entity->id())) {
      $form_state->setErrorByName('form_id', $this->t('You must unpublish the winners to edit the contest.'));
    }
    // Validate the start date.
    if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $form_state->getValue('start_tmp'))) {
      $form_state->setErrorByName('start_tmp', $this->t('Incorrect start date format, (YYYY-MM-DD).'));
    }
    // Validate the end  date.
    elseif (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $form_state->getValue('end_tmp'))) {
      $form_state->setErrorByName('end_tmp', $this->t('Incorrect end date format, (YYYY-MM-DD).'));
    }
    // Validate contest duration.
    elseif (strtotime($form_state->getValue('end_tmp')) - strtotime($form_state->getValue('start_tmp')) < ContestStorage::DAY) {
      $form_state->setErrorByName('start_tmp', $this->t('A contest must run for at least one day.'));
      $form_state->setErrorByName('end_tmp', $this->t('A contest must run for at least one day.'));
    }
    // Set the start and end dates in Unix time.
    else {
      $form_state->setValue('end', [['value' => strtotime($form_state->getValue('end_tmp'))]]);
      $form_state->setValue('start', [['value' => strtotime($form_state->getValue('start_tmp'))]]);
    }
    // Check for a complete profile.
    if (!self::completeProfile($form_state->getValue('sponsor_uid')[0]['target_id'], 'sponsor')) {
      $form_state->setErrorByName('sponsor_uid', $this->t('The sponsor must have a complete profile.'));
    }
    parent::validateForm($form, $form_state);
  }

}
