<?php

namespace Drupal\mask_user_data\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mask_user_data\Mask\User as MaskUser;
use Drupal\user\UserStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Mask. Contains the admin form.
 *
 * @package Drupal\mask_user_data\Form
 */
class Mask extends ConfigFormBase {

  /**
   * Drupal User definition.
   *
   * @var \Drupal\user\UserStorage
   */
  protected $user;

  /**
   * Mask User definition.
   *
   * @var \Drupal\mask_user_data\Mask\User
   */
  protected $maskUserService;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MaskUser $mask_service, UserStorage $user) {
    parent::__construct($config_factory);
    $this->maskUserService = $mask_service;
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('mask_user_data.mask_user'),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mask_user_data.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mask_user_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mask_user_data.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('<b>Only enable this if you are on a 
        non-production site</b>. The process is not reversible and you might lose 
        sensitive data.<br />
        For production, it is strongly recommended to load this value from the 
        <em>settings.php</em> file like: 
        <em>$conf[\'mask_user_data_enabled\'] = FALSE;</em>
        <br /><br />
        Data masking process can be triggered via drush: <b>drush mud</b><br />
        Or else it will run via <b>cron</b> hook.
      '),
      '#default_value' => $config->get('enabled') ?: FALSE,
    ];

    $map_array = $config->get('map_array') ?: NULL;
    $map_json = $map_array ? json_encode($map_array) : '';
    $form['map'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Map'),
      '#description' => $this->t('Provide a valid JSON string where the keys are
        the field names and the values are the <a target="_blank" href="https://github.com/fzaninotto/Faker#formatters">Faker functions</a>. <br/>
        Example:
        <em>{ "mail": "email", "field_job_title": "jobTitle" }</em>.<br /><br />
        * If the string is not valid JSON then no data masking will be performed,
        or if it is valid but contains invalid field names, properties or 
        functions then those invalid ones will not be masked.
      '),
      '#default_value' => $map_json,
      '#states' => [
        'invisible' => [
          ':input[name="enabled"]' => ['checked' => FALSE],
        ],
        'disabled' => [
          ':input[name="user_id"]' => ['filled' => TRUE],
        ],
      ],
    ];

    if (
      $config->get('enabled') &&
      $config->get('already_masked') !== TRUE
    ) {
      $form['user_id'] = [
        '#type' => 'textfield',
        '#size' => 8,
        '#title' => $this->t('User ID'),
        '#description' => $this->t('Mask only this user when clicking "Mask data now". Leave empty to mask all users.'),
        '#default_value' => '',
      ];

      $map_array = $config->get('map_array') ?: NULL;
      if (!(empty($map_array) || !is_array($map_array))) {
        $form['actions']['mask_data'] = [
          '#type' => 'submit',
          '#value' => $this->t('Mask data now'),
          '#submit' => ['::maskDataNow'],
          '#weight' => 10,
        ];
      }
    }
    elseif (
      $config->get('enabled') &&
      $config->get('already_masked') === TRUE
    ) {
      $form['info'] = [
        '#markup' => $this->t('<b style="color:mediumseagreen">The data has already been masked.</b>'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Custom submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function maskDataNow(array &$form, FormStateInterface $form_state) {
    $config = $this->config('mask_user_data.settings');

    if (!empty($form_state->getValue('user_id'))) {
      $uid = $form_state->getValue('user_id');
      if ($this->user->load($uid)) {
        $map_array = $config->get('map_array') ?: NULL;
        $this->maskUserService->mask($uid, $map_array);
        drupal_set_message($this->t('User ID %uid was masked.', ['%uid' => $uid]));
      }
      else {
        drupal_set_message($this->t('User ID %uid does not exists.', ['%uid' => $uid]), 'error');
      }
    }
    else {
      \mask_user_data_setup_batch();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('mask_user_data.settings');
    $config->set('enabled', (bool) $form_state->getValue('enabled'));

    if (!empty($form_state->getValue('map'))) {
      $prev_map_array = $config->get('map_array') ?: NULL;
      $prev_map_json = $prev_map_array ? json_encode($prev_map_array) : '';

      $map = $form_state->getValue('map');
      $map_array = json_decode($map, TRUE);
      $config->set('map_array', $map_array)->save();

      if ($prev_map_json !== json_encode($map_array)) {
        $config->set('already_masked', FALSE)->save();
      }
    }
    else {
      $config->set('map_array', NULL)->save();
    }
  }

}
