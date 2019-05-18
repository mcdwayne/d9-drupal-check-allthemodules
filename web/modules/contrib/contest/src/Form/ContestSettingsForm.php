<?php

namespace Drupal\contest\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\contest\ContestStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The contest settings form.
 */
class ContestSettingsForm extends FormBase {
  use ContestValidateTrait;

  protected $cfgStore;
  protected $usrStore;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $usrStore
   *   The user storage dependency injection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $cfgStore
   *   The config factory dependency injection.
   */
  public function __construct(EntityStorageInterface $usrStore, ConfigFactoryInterface $cfgStore) {
    $this->cfgStore = $cfgStore;
    $this->usrStore = $usrStore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager')->getStorage('user'), $container->get('config.factory'));
  }

  /**
   * The contest configuration form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = (object) $this->cfgStore->get('contest.config')->get();
    $host = (object) $this->cfgStore->get('contest.host')->get();
    $tnc = (object) $this->cfgStore->get('contest.tnc')->get();

    $usr = $this->usrStore->load($host->uid);

    $form['host'] = [
      '#type'          => 'entity_autocomplete',
      '#target_type'   => 'user',
      '#title'         => $this->t('Contest Host'),
      '#description'   => $this->t('The host for the contests on this site.'),
      '#default_value' => $usr->id() ? $usr : $this->usrStore->load(0),
      '#size'          => 30,
      '#maxlength'     => ContestStorage::STRING_MAX,
      '#required'      => TRUE,
      '#weight'        => 10,
    ];
    $form['title'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Host Title'),
      '#description'   => $this->t('The display title for the contest host.'),
      '#default_value' => $host->title,
      '#size'          => 30,
      '#maxlength'     => ContestStorage::STRING_MAX,
      '#required'      => TRUE,
      '#weight'        => 20,
    ];
    $form['dq_days'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Ineligiblity'),
      '#description'   => $this->t('The number of days that a contest winner is ineligible from winning again.'),
      '#default_value' => $config->dq_days,
      '#size'          => 30,
      '#maxlength'     => strlen((string) ContestStorage::INT_MAX) - 1,
      '#required'      => TRUE,
      '#weight'        => 40,
    ];
    $form['min_age'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Minimum Age'),
      '#description'   => $this->t('The minimum age requirement to enter a contest.'),
      '#default_value' => $config->min_age,
      '#options'       => array_combine(range(18, 100), range(18, 100)),
      '#required'      => TRUE,
      '#weight'        => 50,
    ];
    $form['notify'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Notification'),
      '#description'   => $this->t('The number of days till a winner will be notifield.'),
      '#default_value' => $config->notify,
      '#size'          => 30,
      '#maxlength'     => strlen((string) ContestStorage::INT_MAX) - 1,
      '#required'      => TRUE,
      '#weight'        => 60,
    ];
    $form['years_max'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Maximum Years Ahead'),
      '#description'   => $this->t('The maximum number of years in advance that a contest can be created.'),
      '#default_value' => $config->years_max,
      '#options'       => $this->getMaxYears(),
      '#required'      => TRUE,
      '#weight'        => 70,
    ];
    $form['profile_on_user_form'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Profile on User Form'),
      '#description'   => $this->t('Display the profile form on the user form.'),
      '#default_value' => $config->profile_on_user_form,
      '#options'       => [1 => $this->t('True'), 0 => $this->t('False')],
      '#required'      => TRUE,
      '#weight'        => 80,
    ];
    $form['tnc_fieldset'] = [
      '#title'  => $this->t('Terms & Conditions'),
      '#type'   => 'details',
      '#open'   => FALSE,
      '#weight' => 90,
    ];
    $form['tnc_fieldset']['tnc'] = [
      '#type'             => 'text_format',
      '#default_value'    => $tnc->tnc,
      '#format'           => 'plain_text',
      '#required'         => FALSE,
      'token_help'        => [
        '#dialog'       => TRUE,
        '#theme'        => 'token_tree_link',
        '#token_types'  => ['contest', 'node', 'user'],
        '#weight'       => 10,
      ],
    ];
    $form['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Submit'),
      '#weight' => 100,
    ];
    return $form;
  }

  /**
   * The form ID.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'contest_settings';
  }

  /**
   * Submit function for the contest configuration form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->cfgStore->getEditable('contest.host')
      ->set('uid', $form_state->getValue('host'))
      ->set('title', trim($form_state->getValue('title')))
      ->save();

    $this->cfgStore->getEditable('contest.config')
      ->set('dq_days', intval($form_state->getValue('dq_days')))
      ->set('min_age', intval($form_state->getValue('min_age')))
      ->set('notify', intval($form_state->getValue('notify')))
      ->set('profile_on_user_form', intval($form_state->getValue('profile_on_user_form')))
      ->set('years_max', intval($form_state->getValue('years_max')))
      ->save();

    $this->cfgStore->getEditable('contest.tnc')
      ->set('format', $form_state->getValue('tnc')['format'])
      ->set('tnc', $form_state->getValue('tnc')['value'])
      ->save();

    drupal_set_message($this->t('The contest settings have been updated.'));
  }

  /**
   * Validation function for the contest configuration form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->validField('complete_profile', $form_state->getValue('host'))) {
      $form_state->setErrorByName('host', $this->t('Please designate a valid user as the contest host.'));
    }
    if (!$this->validField('string', $form_state->getValue('title'))) {
      $form_state->setErrorByName('title', $this->t('Please enter a title for the contest host.'));
    }
    if (!$this->validField('int', $form_state->getValue('dq_days'))) {
      $form_state->setErrorByName('dq_days', $this->t('Please enter the number of days that a contest winner is ineligible from winning again.'));
    }
    if (!$this->validField('int', $form_state->getValue('min_age'))) {
      $form_state->setErrorByName('min_age', $this->t('Please enter the minimum age requirement.'));
    }
    if (!$this->validField('int', $form_state->getValue('notify'))) {
      $form_state->setErrorByName('notify', $this->t('Please enter the number of days to notify a winner.'));
    }
    if (!$this->validField('int', $form_state->getValue('years_max'))) {
      $form_state->setErrorByName('years_max', $this->t('Please enter the maximum number of years in advance that a contest can be created.'));
    }
    if ($form_state->getValue('profile_on_user_form') != 0 && $form_state->getValue('profile_on_user_form') != 1) {
      $form_state->setErrorByName('profile_on_user_form', $this->t('Please set the user profile display settings.'));
    }
  }

  /**
   * Generate an array of years ahead options.
   *
   * @return array
   *   An array of years ahead options.
   */
  protected function getMaxYears() {
    $years = range(1, (intval(date('Y', ContestStorage::INT_MAX)) - intval(date('Y', REQUEST_TIME)) - 1));
    return array_combine($years, $years);
  }

}
