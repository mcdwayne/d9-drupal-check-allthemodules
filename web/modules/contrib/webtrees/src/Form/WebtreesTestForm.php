<?php

/**
 * @file
 * Contains \Drupal\webtrees\Form\WebtreesTestForm.
 */

namespace Drupal\webtrees\Form;

use Drupal\webtrees\Helper\WebtreesUser;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class WebtreesTestForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs a WebtreesFairTestForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webtrees_test';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webtrees.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form=array();

    if ($form_state->isRebuilding()) {
      $form_state->setRebuild(false);

      $form['state'] = array (
        '#type' => 'value',
        '#value' => 'result',
      );
      $form['results'] = array(
        '#type' => 'details',
        '#title' => t('User test results'),
        '#open' => TRUE,
        'result' => $form_state->get('my_form_result'),
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => $this
          ->t('Try another'),
        '#button_type' => 'primary',
      );
    } else {
      $webtrees = new WebtreesUser();

      $error=$webtrees->connectError();
      if ($error) {
        $webtrees->close();

        return array( array (
          '#type' => 'item',
          '#title' => t('Connection failed: ').$error,
          '#markup' => t('Make sure the database settings are correct and try again.'),
          ));
      }

      $webtrees->close();

      $form['state'] = array (
        '#type' => 'value',
        '#value' => 'query',
      );

      $form['login'] = array(
        '#type' => 'details',
        '#title' => t('User login test'),
        '#open' => TRUE,
      );
      $form['login'][] = array (
        '#type' => 'item',
        '#title' => t('Note: '),
        '#markup' => t('This form can be used to test acess Webtrees user database. It will not log in the user or synchronize the user databases.'),
      );
      $form['login']['user'] = array(
	'#type' => 'textfield',
	'#title' => t('User name or email'),
	'#required' => TRUE,
      );
      $form['login']['password'] = array(
	'#type' => 'password',
	'#title' => t('Password'),
	'#description' => t('Compare to the Webtrees user password if provided.'),
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
	'#type' => 'submit',
	'#value' => $this
	  ->t('Test login'),
	'#button_type' => 'primary',
      );
    }

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('state')!='query') {
      return;
    }

    $webtrees = new WebtreesUser();

    if ($webtrees->connectError()) {
      drupal_set_message(t('Connection failed: ').$webtrees->connectError(),'error');
    } else {
      $user=$form_state->getValue('user');
      $password=$form_state->getValue('password');

      if ($webtrees->load($user,'name_or_email')) {
        $form_state->setRebuild();

        $rows[]=[t('User ID'),$webtrees->user_id];
        $rows[]=[t('User name'),$webtrees->user_name];
        $rows[]=[t('Email'),$webtrees->email];
        $rows[]=[t('Password'),$webtrees->hash];

        foreach($webtrees->settings as $key => $value){
          $rows[]=[$key,$value];
        }
        $result[]= array (
           '#type' => 'table',
           '#header' => [t('Name'),t('Value')],
           '#rows' => $rows,
           );

        $form_state->set('my_form_result',$result);

        if ($password) {
          if ($webtrees->passwordVerify($password)) {
            drupal_set_message(t('Password matches'));
          } else {
            drupal_set_message(t('Password DOES NOT MATCH').$password.'-','error');
          }
        }
        
        $drupal_user = user_load_by_name($webtrees->user_name);
        if ($drupal_user) {
          drupal_set_message(t('Drupal user matches by Webtrees user name'));
          if($webtrees->email!=$drupal_user->getEmail()) {
            drupal_set_message(t('Emails do not match'),'error');
          }
        } else {
          $drupal_user = user_load_by_mail($webtrees->email);
          if ($drupal_user) {
            drupal_set_message(t('Drupal user matches by Webtrees email'));
            if($webtrees->user_name!=$drupal_user->getUsername()) {
              drupal_set_message(t('Usernames do not match'),'error');
            }
          }
        }
        if ($drupal_user) {
          // check if blocked
        }
      } else {
        drupal_set_message(t('User not found: ').$user,'error');
      }
    }

    $webtrees->close();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }
}

