<?php

namespace Drupal\tc\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Random;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure TC settings for a user.
 */
class TcSettingsForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a TcSettingsForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var Connection $connection */
    $connection = $container->get('database');
    return new static(
      $connection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tc_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $user = NULL) {
    $uid = $user->id();
    $settings = _tc_get_settings($this->connection, $uid);
    $random = new Random();
    $form['write_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Write key'),
      '#maxlength' => 15,
      '#default_value' => isset($settings['write_key']) ? $settings['write_key'] : $random->name(15),
      '#description' => $this->t('This is your write key.'),
      '#required' => TRUE,
    ];
    $fields = _tc_get_fields();
    $form['field_enabled']['#tree'] = TRUE;
    $form['field_name']['#tree'] = TRUE;
    $form['field_skip_na']['#tree'] = TRUE;
    $remote_url = '';
    foreach ($fields as $field) {
      $form['field_enabled'][$field] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable field %fieldname', ['%fieldname' => Unicode::strtoupper($field)]),
        '#default_value' => isset($settings['settings']['field_enabled'][$field]) ? $settings['settings']['field_enabled'][$field] : 0,
      ];
      $form['field_name'][$field] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name of field %fieldname', ['%fieldname' => Unicode::strtoupper($field)]),
        '#default_value' => isset($settings['settings']['field_name'][$field]) ? $settings['settings']['field_name'][$field] : '',
        '#states' => [
          'enabled' => [
            ':input[name="field_enabled[' . $field . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['field_skip_na'][$field] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Skip "N/A" and "-60.0" in field %fieldname', ['%fieldname' => Unicode::strtoupper($field)]),
        '#default_value' => isset($settings['settings']['field_skip_na'][$field]) ? $settings['settings']['field_skip_na'][$field] : '',
        '#states' => [
          'enabled' => [
            ':input[name="field_enabled[' . $field . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];
      if (!empty($settings['settings']['field_enabled'][$field])) {
        $remote_url .= '&' . $field . '=#' . _tc_typical_mapping($field);
      }
    }
    // @TODO: Display a link (local task?) for importing data.
    $form['tc_network'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#title' => $this->t('HTTP client settings for TC'),
      'server_address' => [
        '#type' => 'textfield',
        '#title' => $this->t('Server address'),
        '#disabled' => TRUE,
        '#default_value' => $_SERVER['HTTP_HOST'],
      ],
      'server_port' => [
        '#type' => 'textfield',
        '#title' => $this->t('Port'),
        '#disabled' => TRUE,
        '#default_value' => $_SERVER['SERVER_PORT'],
      ],
      // Output the URL to be copy-pasted into TC's "Remote URL" field.
      'remote_url' => [
        '#type' => 'textfield',
        '#title' => $this->t('Remote URL'),
        '#disabled' => TRUE,
        '#default_value' => 'GET /tc?w=' . $settings['write_key'] . $remote_url,
      ],
    ];
    $form['thingspeak_relaying'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#title' => $this->t('Relaying to Thingspeak'),
      'thingspeak_relay' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable relaying the first up to 8 enabled fields to Thingspeak'),
        '#default_value' => isset($settings['settings']['thingspeak_relay']) ? $settings['settings']['thingspeak_relay'] : 0,
      ],
      'thingspeak_write_key' => [
        '#type' => 'textfield',
        '#title' => $this->t('Thingspeak write key'),
        '#default_value' => isset($settings['settings']['thingspeak_write_key']) ? $settings['settings']['thingspeak_write_key'] : '',
        '#states' => [
          'enabled' => [
            ':input[name=thingspeak_relay]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    // @TODO: Update this URL dynamically based on write key and enabled fields.
    $form['actions'] = [
      '#type' => 'actions',
      'save' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];
    $form['#theme'] = 'tc_settings_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $write_key = $form_state->getValue('write_key');
    if (Unicode::strlen($write_key) <> 15) {
      $form_state->setErrorByName('write_key', $this->t('The write key must be 15 characters long.'));
    }
    // @TODO: Validate uniqueness amongst all users.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = [
      'field_enabled' => $form_state->getValue('field_enabled'),
      'field_name' => $form_state->getValue('field_name'),
      'field_skip_na' => $form_state->getValue('field_skip_na'),
      'thingspeak_relay' => $form_state->getValue('thingspeak_relay'),
      'thingspeak_write_key' => $form_state->getValue('thingspeak_write_key'),
    ];
    $uid = $this->currentUser()->id();
    $this->connection->upsert('tc_user')
      ->key('uid')
      ->fields(['uid', 'write_key', 'settings'])
      ->values([
        'uid' => $uid,
        'write_key' => $form_state->getValue('write_key'),
        'settings' => serialize($settings),
      ])
      ->execute();
  }
}
