<?php

namespace Drupal\deactivate_account\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\Date;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Query;
use Drupal\Core\Path\PathValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingsForm extends ConfigFormBase {
  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Class constructor.
   */
  public function __construct(PathValidator $pathValidator) {
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator')
    );
  }

  public function getFormId() {
    return 'deactivate_account_settings';
  }
  public function getEditableConfigNames() {
    return [
      'deactivate_account.settings',
    ];

  }
  public function buildForm(array $form, FormStateInterface $form_state) {
//    $account = \Drupal::currentUser();
    $config = $this->config('deactivate_account.settings');

    $form = array();
    $form['#tree'] = TRUE;
    $deactivate_account_total_options = \Drupal::state()->get('deactivate_account_total_options');
    if (empty($deactivate_account_total_options)) {
      $deactivate_account_total_options = 0;
    }

    $form['deactivate_account_time_option'] = array(
      '#type' => 'details',
      '#title' => $this->t('Time options to deactivate account'),
      '#description' => $this->t('If no time option is provided then temporary deactivate form will not be shown to user.'),
      // Set up the wrapper so that AJAX will be able to replace the fieldset.
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    );
    // Build the fieldset with the proper number of time options.
    // variable $total_options determine the number of textfields to build.
    $deactivate_account_time_option = $config->get('deactivate_account_time_option');
    for ($i = 0; $i <= $deactivate_account_total_options; $i++) {
      $form['deactivate_account_time_option']['name']['deactivate_account_time_' . $i] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Time'),
        '#description' => $this->t('Enter only number and unit is hour.'),
        '#default_value' => $deactivate_account_time_option['name']['deactivate_account_time_' . $i],
      );
    }
    $form['deactivate_account_time_option']['add_option'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add one more'),
      '#submit' => array('::deactivate_account_add_one_option'),
      // See the examples in ajax_example.module for more details on the
      // properties of #ajax.
      '#ajax' => array(
        'callback' => '::deactivate_account_add_more_callback',
        'wrapper' => 'names-fieldset-wrapper',
      ),
    );
    if ($deactivate_account_total_options > 1) {
      $form['deactivate_account_time_option']['remove_option'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => array('::deactivate_account_remove_one_option'),
        '#ajax' => array(
          'callback' => '::deactivate_account_add_more_callback',
          'wrapper' => 'names-fieldset-wrapper',
        ),
      );
    }

    // Checkbox for disabling decativated user comments.
    $form['deactivate_account_comments'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all comments of deactivated user.'),
      '#description' => $this->t('If checked then all the comments posted by the user will be hidden till the time period account gets active.'),
      '#default_value' => $config->get('deactivate_account_comments'),
    );

    // Checkbox for disabling decativated user comments.
    $form['deactivate_account_nodes'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all nodes of deactivated user.'),
      '#description' => $this->t('If checked then all the nodes published by the user will be hidden till the time period account gets active.'),
      '#default_value' => $config->get('deactivate_account_nodes'),
    );

    // Checkbox for making local tab on user page or link.
    $form['deactivate_account_tab'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create tab on user page'),
      '#description' => $this->t('If checked then deactivation form will be built under user profile as local tab else it will be menu link as specified by the admin.'),
      '#default_value' => $config->get('deactivate_account_tab'),
    );

    $form['deactivate_account_path_container'] = array(
      '#type' => 'container',
      '#states' => array(
        "visible" => array(
          "input[name='deactivate_account_tab']" => array("checked" => FALSE),
        ),
      ),
    );

    $menu = $config->get('deactivate_account_path_container');
    $form['deactivate_account_path_container']['deactivate_account_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Deactivate form link'),
      '#description' => $this->t('deactivate form link @base_url/user/', array('@base_url' => $GLOBALS['base_url'])),
      '#default_value' => $menu['deactivate_account_path'],
      '#prefix' => '',
      '#suffix' => '',
      '#validate' => array('::deactivate_account_config_form_validate'),
    );

    $form['deactivate_account_redirect'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path'),
      '#description' => $this->t('user to entered path after deactivating account @base_url/, or you can add the node path like @node_path.', ['@base_url' => $GLOBALS['base_url'], '@node_path' => '/node/*']),
      '#required' => TRUE,
      '#default_value' => $config->get('deactivate_account_redirect'),
    );
    $form['#submit'][] = '::deactivate_account_validate_blank_options';
    $form = parent::buildForm($form, $form_state);
    $form['#submit'][] = '::deactivate_account_clear_cache';

    return $form;
  }

  /**
   * Validate auto_username_settings_form form submissions.
   */
  function validateForm(array &$form, FormStateInterface $form_state) {
    $redirect_path = $form_state->getValue('deactivate_account_redirect');
    // Check if given node path is valid or not.
    if (empty($this->pathValidator->getUrlIfValid($redirect_path))) {
      $form_state->setErrorByName('deactivate_account_redirect', $this->t('Invalid node.'));
    }
  }

  public function deactivate_account_clear_cache($form, FormStateInterface $form_state) {
    drupal_flush_all_caches();
  }

  /**
   * Implements hook_form_submit().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set values in variables.
    $values = $form_state->getValues();
    $config = $this->config('deactivate_account.settings');
    $config->set('deactivate_account_time_option', $values['deactivate_account_time_option'])
      ->set('deactivate_account_comments', $values['deactivate_account_comments'])
      ->set('deactivate_account_nodes', $values['deactivate_account_nodes'])
      ->set('deactivate_account_tab', $values['deactivate_account_tab'])
      ->set('deactivate_account_path_container', $values['deactivate_account_path_container'])
      ->set('deactivate_account_redirect', $values['deactivate_account_redirect'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Function to add one textbox.
   */
  public function deactivate_account_add_one_option(array $form, FormStateInterface $form_state) {
    $time_options = \Drupal::state()->get('deactivate_account_total_options');
    $time_options++;
    \Drupal::state()->set('deactivate_account_total_options', $time_options);
    $form_state->setRebuild();
  }

  /**
   * Function to return form with existing form fields.
   */
  public function deactivate_account_add_more_callback(array $form, FormStateInterface $form_state) {
    return $form['deactivate_account_time_option'];
  }

  /**
   * Function to remove one textbox.
   */
  public function deactivate_account_remove_one_option(array $form, FormStateInterface $form_state) {
    $time_options = \Drupal::state()->get('deactivate_account_total_options');
    if ($time_options > 1) {
      $time_options--;
      \Drupal::state()->set('deactivate_account_total_options', $time_options);
    }
    $form_state->setRebuild();
  }

  /**
   * Function to check wheather deactivate form link field is required or not.
   */
  public function deactivate_account_config_form_validate(array $form, FormStateInterface $form_state) {
    $config = $this->config('deactivate_account.settings');
    if ($form_state->getValues()['deactivate_account_tab'] == 0 && empty(trim($form_state->getValues()['deactivate_account_path_container']['deactivate_account_path']))) {
      $form_state->setErrorByName('deactivate_account_tab', $this->t('Deactivate form link field is required'));
    }
    for ($i = 0; $i <= \Drupal::state()->get('deactivate_account_total_options'); $i++) {
      if ($form_state->getValues()['deactivate_account_time_option']['name']['deactivate_account_time_' . $i] == "0") {
        $form_state->setErrorByName('deactivate_account_time_option', $this->t('Invalid time option 0'));
      }
      elseif (!is_numeric($form_state->getValues()['deactivate_account_time_option']['name']['deactivate_account_time_' . $i])) {
        $form_state->setErrorByName('deactivate_account_time_option', $this->t('Time period must be numeric'));
      }
    }
  }

  /**
   * Function to validate blank time option fields.
   */
  public function deactivate_account_validate_blank_options(array $form, FormStateInterface $form_state) {
    $config = $this->config('deactivate_account.settings');
    $time_options = \Drupal::state()->get('deactivate_account_total_options');
    $blank_time_options = 0;
    for ($i = 0; $i <= $time_options; $i++) {
      if ($form_state->getValues()['deactivate_account_time_option']['name']['deactivate_account_time_' . $i] == "") {
        $blank_time_options++;
      }
    }
    $time_options = $time_options - $blank_time_options;
    if ($time_options < 0) {
      $time_options = 0;
    }
    \Drupal::state()->set('deactivate_account_total_options', $time_options);
  }

}
