<?php

namespace Drupal\contest\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\contest\ContestHelper;
use Drupal\contest\ContestInterface;
use Drupal\contest\ContestStorage;
use Drupal\contest\ContestUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The contest body, entry form and t&c with validation and submit.
 */
class ContestViewForm extends FormBase {
  use ContestValidateTrait;

  protected $cfgStore;
  protected $contestStorage;
  protected $mailMgr;
  protected $request;
  protected $token;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $cfgStore
   *   The config factory dependency injection.
   * @param \Drupal\Core\Entity\EntityStorageInterface $contestStorage
   *   The contest storage dependency injection.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailMgr
   *   The mail manager dependency injection.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The request dependency injection.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(ConfigFactoryInterface $cfgStore, EntityStorageInterface $contestStorage, MailManagerInterface $mailMgr, RequestStack $request, Token $token) {
    $this->cfgStore = $cfgStore;
    $this->contestStorage = $contestStorage;
    $this->mailMgr = $mailMgr;
    $this->request = $request;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager')->getStorage('contest'),
      $container->get('plugin.manager.mail'),
      $container->get('request_stack'),
      $container->get('token')
    );
  }

  /**
   * The contest body, entry form and terms and conditions.
   *
   * @param array $form
   *   A Drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   * @param \Drupal\contest\ContestInterface $contest
   *   The ContestInterface object.
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The Request object.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContestInterface $contest = NULL, Request $request = NULL) {
    $fieldset_title = $this->t('Contestant Profile');
    $states = ContestHelper::getStates($this->cfgStore->get('system.date')->get('country.default'));
    $usr = new ContestUser($this->currentUser()->id());

    // If the contest isn't running.
    if (REQUEST_TIME < $contest->start->value || $contest->end->value < REQUEST_TIME) {
      $form['body'] = [
        '#type'   => 'markup',
        '#markup' => $contest->body->value,
        '#weight' => -1,
      ];
      // If results are published.
      if (ContestStorage::getPublished($contest->id())) {
        $form['results'] = [
          '#type'   => 'markup',
          '#theme'  => 'contest_results',
          '#weight' => -1,
        ];
      }
      return $form;
    }
    $form['contest'] = [
      '#type'  => 'value',
      '#value' => $contest,
    ];
    $form['uid'] = [
      '#type'   => 'hidden',
      '#value'  => $usr->uid,
      '#weight' => -10,
    ];
    $form['cid'] = [
      '#type'   => 'hidden',
      '#value'  => $contest->id(),
      '#weight' => -10,
    ];
    $form['alert'] = [
      '#type'   => 'markup',
      '#markup' => $usr->uid ? '' : '<p><b>' . $this->t('Please fill in and submit the form below, (all fields are required).') . '</b><br />' . $this->t('If you already have an account click @link to log in and skip filling in the form.', ['@link' => $this->l($this->t('here'), Url::fromRoute('user.login'))]) . '</p>',
      '#weight' => -5,
    ];
    $form['body'] = [
      '#type'   => 'markup',
      '#markup' => $contest->body->value,
      '#weight' => -1,
    ];
    $form['fieldset'] = [
      '#prefix' => "<div id=\"contest-profile-wrapper\"><fieldset><legend><a href=\"#\" id=\"contest-profile-toggle\">$fieldset_title<span class=\"contest-raquo\">&raquo;</span></a></legend><div id=\"contest-profile\" class=\"" . ($usr->completeProfile() ? 'complete-profile' : 'incomplete-profile') . '">',
      '#suffix' => '</div></fieldset></div>',
      '#type'   => 'markup',
      '#weight' => 2,
    ];
    $form['fieldset']['contest_name'] = [
      '#title'         => $this->t('Name'),
      '#type'          => 'textfield',
      '#attributes'    => ['pattern' => '^\s*[\s\w\-\.]+\s*$'],
      '#default_value' => $usr->fullName,
      '#size'          => 30,
      '#maxlength'     => 100,
      '#required'      => TRUE,
      '#weight'        => 0,
    ];
    $form['fieldset']['contest_address'] = [
      '#title'         => $this->t('Address'),
      '#type'          => 'textfield',
      '#attributes'    => ['pattern' => '^.+$'],
      '#default_value' => $usr->address,
      '#size'          => 30,
      '#maxlength'     => 100,
      '#required'      => TRUE,
      '#weight'        => 1,
    ];
    $form['fieldset']['contest_city'] = [
      '#title'         => $this->t('City'),
      '#type'          => 'textfield',
      '#attributes'    => ['pattern' => '^\s*[\s\w\-\.]+\s*$'],
      '#default_value' => $usr->city,
      '#size'          => 30,
      '#maxlength'     => 50,
      '#required'      => TRUE,
      '#weight'        => 2,
    ];
    if (!empty($states)) {
      $form['fieldset']['contest_state'] = [
        '#title'         => $this->t('State'),
        '#type'          => 'select',
        '#attributes'    => ['pattern' => '^.+$'],
        '#options'       => array_merge(['' => $this->t('-Select-')], $states),
        '#default_value' => $usr->state,
        '#required'      => TRUE,
        '#weight'        => 3,
      ];
    }
    else {
      $form['fieldset']['contest_state'] = [
        '#title'         => $this->t('Province'),
        '#type'          => 'textfield',
        '#attributes'    => ['pattern' => '^.+$'],
        '#default_value' => $usr->state,
        '#size'          => 30,
        '#maxlength'     => 50,
        '#required'      => FALSE,
        '#weight'        => 3,
      ];
    }
    $form['fieldset']['contest_zip'] = [
      '#title'         => $this->t('Zip'),
      '#type'          => 'textfield',
      '#attributes'    => ['pattern' => '^\s*\d+\s*$'],
      '#default_value' => $usr->zip,
      '#size'          => 30,
      '#maxlength'     => 5,
      '#required'      => TRUE,
      '#weight'        => 4,
    ];
    $form['fieldset']['mail'] = [
      '#title'         => $this->t('Email'),
      '#type'          => 'textfield',
      '#attributes'    => ['pattern' => '^\s*[\w\-\.]+@[\w\-\.]+\.\w+\s*$'],
      '#default_value' => !empty($usr->mail) ? $usr->mail : '',
      '#size'          => 30,
      '#maxlength'     => 100,
      '#required'      => TRUE,
      '#weight'        => 5,
    ];
    $form['fieldset']['contest_phone'] = [
      '#title'         => $this->t('Phone'),
      '#type'          => 'textfield',
      '#default_value' => $usr->phone,
      '#size'          => 30,
      '#maxlength'     => 20,
      '#required'      => FALSE,
      '#weight'        => 6,
    ];
    $form['fieldset']['contest_birthdate'] = [
      '#title'               => $this->t('Birthday'),
      '#description'         => $this->t('Format: YYYY-MM-DD'),
      '#type'                => 'date',
      '#default_value'       => is_numeric($usr->birthdate) ? date('Y-m-d', $usr->birthdate) : '',
      '#date_format'         => 'Y-m-d',
      '#date_increment'      => ContestStorage::DAY,
      '#date_label_position' => 'invisible',
      '#date_timezone'       => date('T'),
      '#date_year_range'     => '-100:+0',
      '#required'            => TRUE,
      '#weight'              => 7,
    ];
    $form['contest_optin'] = [
      '#title'         => $this->t('Opt In'),
      '#type'          => 'checkbox',
      '#description'   => $this->t("I'd like to receive information about contests and special offers from the sponsor and promoter."),
      '#attributes'    => ['pattern' => '^\d$'],
      '#prefix'        => '<div id="contest-optin">',
      '#suffix'        => '</div>',
      '#default_value' => 1,
      '#required'      => TRUE,
      '#weight'        => 8,
    ];
    $form['clear_both'] = [
      '#prefix' => '<div class="clr">',
      '#suffix' => '</div>',
      '#type'   => 'markup',
      '#value'  => '&nbsp;',
      '#weight' => 9,
    ];
    $form['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Enter Contest'),
      '#weight' => 11,
    ];
    $form['tnc_fieldset'] = [
      '#theme'  => 'contest_tnc',
      '#data'   => [
        'title' => $this->t('Terms and Conditions'),
        'tnc'   => Xss::filter($this->token->replace($this->cfgStore->get('contest.tnc')->get('tnc'))),
      ],
      '#weight' => 12,
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
    return 'contest_view_form';
  }

  /**
   * Submit function for the contest entry form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = new ContestUser($this->currentUser()->id());
    $periods = ContestStorage::getPeriodOptions();

    // Users must have a complete profile to enter a contest.
    $form_state->setValue('uid', $this->contestant($form_state));

    $usr = new ContestUser($form_state->getValue('uid'));

    // If Incomplete profile give explanation and redirect.
    if ($user->uid != $usr->uid && !$usr->completeProfile()) {
      drupal_set_message($this->t('You already have an account with an incomplete profile. The easiest way to enter the contest is to log in and come back and enter the contest.'), 'warning');
      drupal_set_message($this->t("Once your profile is complete you won't have to do this again."));
      drupal_set_message($this->t('If you have problems logging in click the "Request new password" link and a login link will be sent to your email.'));
      return new RedirectResponse("/user");
    }
    // I don't think this should ever get used, but...
    elseif (!$usr->completeProfile()) {
      drupal_set_message($this->t('You must have a complete profile to enter a contest.'), 'warning');
      return new RedirectResponse('/user/' . $usr->uid . '/edit');
    }
    // We'll check to see if the contest is running by getting the entry period.
    $period = $this->contestStorage->getPeriod($form_state->getValue('cid'));

    if (!$period) {
      drupal_set_message($this->t('This contest is closed.'), 'warning');
      return new RedirectResponse("/contest");
    }
    // Check to see if they've entered today.
    $entered = $this->usrEntered($form_state->getValue('uid'), $form_state->getValue('cid'), $period);

    if ($entered) {
      $args = ['@period' => strtolower($periods[$period])];
      drupal_set_message($this->t('You can enter the contest @period. We already have an entry for you during this period.', $args), 'warning');
      return new RedirectResponse('/contest/' . $form_state->getValue('cid'));
    }
    // Enter them into the contest.
    $fields = [
      'cid'     => $form_state->getValue('cid'),
      'uid'     => $usr->uid,
      'created' => REQUEST_TIME,
      'ip'      => $this->request->getCurrentRequest()->getClientIp(),
    ];
    $this->contestStorage->saveEntry($fields);

    drupal_set_message($this->t('Your you have been entered into the contest.'));
  }

  /**
   * Validation function for the contest entry form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Find or add a contestant then return the uid.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   *
   * @return int
   *   The entrant's user ID.
   */
  protected function contestant(FormStateInterface $form_state) {
    $user = new ContestUser($this->currentUser()->id());
    $usr = new ContestUser($form_state->getValue('uid'));

    // If no user try to get the user by email.
    if (!$usr->uid) {
      $usr = ContestUser::loadByMail($form_state->getValue('mail'));
    }
    // If we have them and the match the global user update their information.
    if ($usr->uid && $usr->uid == $user->uid) {
      $this->usrSave($usr, $form_state);
      return $usr->uid;
    }
    // If we have a valid user return their ID.
    elseif ($usr->uid) {
      return $usr->uid;
    }
    // If they don't exist, create them.
    $usr = ContestUser::create($form_state->getValue('contest_name'), $form_state->getValue('mail'));
    $this->usrSave($usr, $form_state);

    // Build message variables, send welcome email and set welcome message.
    $acct = $usr->getAccount();
    $mail = $this->mailMgr->mail('user', 'register_no_approval_required', $usr->mail, $usr->lang, ['account' => $acct], ContestHelper::getSiteMail());

    if (empty($mail)) {
      drupal_set_message($this->t("There was an error sending your confirmation email."), 'error');
    }
    $tokens = [
      '@password'  => $usr->pass,
      '@site_name' => $this->cfgStore->get('system.site')->get('name'),
      '@username'  => $usr->name,
    ];
    drupal_set_message($this->t("You have been added to the @site_name website. Below is your login information.<br>Username: @username<br>Password: @password.<br>Please keep this information for you records. If you have a problem logging in, use the password recovery tool located at the top of the user's login page.", $tokens));

    user_login_finalize($acct);

    return $usr->uid;
  }

  /**
   * Return true if entered in the contest during this period, (configuarble).
   *
   * @param int $uid
   *   The user's ID.
   * @param int $cid
   *   The node ID.
   * @param int $period
   *   The seconds allowed between entries.
   *
   * @return bool
   *   True if the user has entered the contest already during this period.
   */
  protected function usrEntered($uid, $cid, $period) {
    $fmt = ContestStorage::getPeriodFormats();
    $periods = ContestStorage::getPeriodOptions();

    // If it's a one entry contest check for an entry and return.
    if ($periods[$period] == $this->t('Once')) {
      return $this->contestStorage->usrEnteredOnce($cid, $uid);
    }
    // If we can't figure out the format, we'll assume TRUE.
    if (empty($fmt[$period])) {
      return TRUE;
    }
    // Determine if the user has already enter the contest.
    $today = date($fmt[$period], REQUEST_TIME);
    $entered = date($fmt[$period], $this->contestStorage->latestUsrEntryDate($cid, $uid));

    return ($entered >= $today) ? TRUE : FALSE;
  }

  /**
   * Save the contest profile fields to the user object.
   *
   * @param \Drupal\contest\ContestUser $usr
   *   A ContestUser object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  protected function usrSave(ContestUser $usr, FormStateInterface $form_state) {
    if (empty($usr->uid)) {
      return;
    }
    $usr->fullName = $form_state->getValue('contest_name');
    $usr->address = $form_state->getValue('contest_address');
    $usr->city = $form_state->getValue('contest_city');
    $usr->state = $form_state->getValue('contest_state');
    $usr->zip = $form_state->getValue('contest_zip');
    $usr->phone = $form_state->getValue('contest_phone');
    $usr->birthdate = strtotime($form_state->getValue('contest_birthdate'));
    $usr->optin = $form_state->getValue('contest_optin');

    $usr->save();
  }

}
