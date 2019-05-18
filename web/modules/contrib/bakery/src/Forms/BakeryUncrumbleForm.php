<?php

namespace Drupal\bakery\Forms;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bakery\BakeryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Password\PasswordInterface;

/**
 * Contribute form.
 */
class BakeryUncrumbleForm extends FormBase implements ContainerInjectionInterface {

  /**
   * For generating hashed password we check with database.
   *
   * @var PasswordInterface $passwordHasher
   */
  protected $passwordHasher;

  /**
   * For different bakery serviceses.
   *
   * @var BakeryService $bakeryService
   */
  protected $bakeryService;

  /**
   * Class constructor.
   */
  public function __construct(PasswordInterface $password_hasher, BakeryService $bakeryService) {
    $this->passwordHasher = $password_hasher;
    $this->bakeryService = $bakeryService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('password'),
      $container->get('bakery.bakery_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bakery_forms_uncrumble_form';
  }

  /**
   * Form to let users repair minor problems themselves.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_name = \Drupal::config('system.site')->get('name');
    $cookie = $this->bakeryService->validateCookie();
    // Analyze.
    $query = db_select('users_field_data', 'u')
      ->fields('u', array('uid', 'name', 'mail'))
      ->condition('u.uid', 0, '!=')
      ->condition('u.mail', '', '!=')
      ->where("LOWER(u.mail) = LOWER(:mail)", array(':mail' => $cookie['mail']));
    $result = $query->execute();
    $samemail = $result->fetchObject();

    $query = db_select('users_field_data', 'u')
      ->fields('u', array('uid', 'name', 'mail'))
      ->condition('u.uid', 0, '!=')
      ->where("LOWER(u.name) = LOWER(:name)", array(':name' => $cookie['name']));
    $result = $query->execute();
    $samename = $result->fetchObject();

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#value' => $cookie['name'],
      '#disabled' => TRUE,
      '#required' => TRUE,
    );

    $form['mail'] = array(
      '#type' => 'item',
      '#title' => t('Email address'),
      '#value' => $cookie['mail'],
      '#required' => TRUE,
    );

    $form['pass'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#description' => t('Enter the password that accompanies your username.'),
      '#required' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Repair account'),
      '#weight' => 2,
    );

    $help = '';

    $count = db_select('users_field_data', 'u')->fields('u', array('uid'))->condition('init', $cookie['init'], '=')
      ->countQuery()->execute()->fetchField();
    if ($count > 1) {
      drupal_set_message(t('Multiple accounts are associated with your master account. This must be fixed manually. <a href="@contact">Please contact the site administrator.</a>', array('%email' => $cookie['mail'], '@contact' => \Drupal::config('bakery.settings')->get('bakery_master') . 'contact')));
      $form['pass']['#disabled'] = TRUE;
      $form['submit']['#disabled'] = TRUE;
    }
    elseif ($samename && $samemail && $samename->uid != $samemail->uid) {
      drupal_set_message(t('Both an account with matching name and an account with matching email address exist, but they are different accounts. This must be fixed manually. <a href="@contact">Please contact the site administrator.</a>', array('%email' => $cookie['mail'], '@contact' => \Drupal::config('bakery.settings')->get('bakery_master') . 'contact')));
      $form['pass']['#disabled'] = TRUE;
      $form['submit']['#disabled'] = TRUE;
    }
    elseif ($samename) {
      $help = t("An account with a matching username was found. Repairing it will reset the email address to match your master account. If this is the correct account, please enter your %site password.", array('%site' => $site_name));
      // This is a borderline information leak.
      // $form['mail']['#value'] = $samename->mail;.
      $form['mail']['#value'] = t('<em>*hidden*</em>');
      $form['mail']['#description'] = t('Will change to %new.', array('%new' => $cookie['mail']));
    }
    elseif ($samemail) {
      $help = t("An account with a matching email address was found. Repairing it will reset the username to match your master account. If this is the correct account, please enter your %site password.", array('%site' => $site_name));
      $form['name']['#value'] = $samemail->name;
      $form['name']['#description'] = t('Will change to %new.', array('%new' => $cookie['name']));
    }

    $form['help'] = array('#weight' => -10, '#markup' => $help);

    return $form;
  }

  /**
   * Validation for bakery_uncrumble form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*
     * We are ignoring blocked status on purpose.
     * The user is being repaired, not logged in.
     */
    $account = user_load_by_name($form_state->getValue('name'));
    if (!($account && $account->id()) || $this->passwordHasher->check($form_state->getValue('pass'), $account->getPassword())) {
      \Drupal::logger('bakery')
        ->notice('Login attempt failed for %user while running uncrumble.', array(
          '%user' => $form_state->getValue('name'),
        ));
      /*
       * Can't pretend that it was the "username or password"
       * so let's be helpful instead.
       */
      $form_state->setErrorByName('pass', t('Sorry, unrecognized password. If you have forgotten your %site password, please <a href="@contact">contact the site administrator.</a>', array('%site' => \Drupal::config('system.site')->get('name'), '@contact' => \Drupal::config('bakery.settings')->get('bakery_master') . ' contact')));

    }
    else {
      $form_state->setValue('bakery_uncrumble_account', $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $form_state->getValue('bakery_uncrumble_account');
    $form_state->unsetValue('bakery_uncrumble_account');
    $cookie = $this->bakeryService->validateCookie();
    db_update('users_field_data')->fields(array('init' => $cookie['init']))
      ->condition('uid', $account->id(), '=')
      ->execute();
    \Drupal::logger('bakery')->notice('uncrumble changed init field for uid %uid from %oldinit to %newinit', array(
      '%oldinit' => $account->get('init')->value,
      '%newinit' => $cookie['init'],
      '%uid' => $account->id(),
    ));
    $account->setEmail($cookie['mail']);
    $account->save();
    \Drupal::logger('bakery')
      ->notice('uncrumble updated name %name_old to %name_new, mail %mail_old to %mail_new on uid %uid.', array(
        '%name_old' => $account->getUsername(),
        '%name_new' => $cookie['name'],
        '%mail_old' => $account->getEmail(),
        '%mail_new' => $cookie['mail'],
        '%uid' => $account->id(),
      ));
    drupal_set_message(t('Your account has been repaired.'));
    $form_state->setRedirect('user.page');
  }

}
