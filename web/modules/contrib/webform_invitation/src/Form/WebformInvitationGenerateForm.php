<?php

namespace Drupal\webform_invitation\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to generate invitation codes.
 */
class WebformInvitationGenerateForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_invitation_generate_form';
  }

  /**
   * Constructs a new WebformInvitationGenerateForm instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, TimeInterface $time) {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {

    $form['webform_invitation'] = [
      '#type' => 'details',
      '#title' => $this->t('Webform Invitation'),
      '#open' => TRUE,
    ];
    $form['webform_invitation']['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of codes to generate'),
      '#min' => 1,
      '#default_value' => 25,
      '#required' => TRUE,
    ];
    $form['webform_invitation']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Type of tokens'),
      '#options' => [
        'md5' => $this->t('MD5 hash (32 characters)'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => 'md5',
      '#required' => TRUE,
    ];
    $form['webform_invitation']['length'] = [
      '#type' => 'number',
      '#title' => $this->t('Length of tokens (number of characters)'),
      '#min' => 5,
      '#max' => 64,
      '#default_value' => 32,
      '#required' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="type"]' => [
            'value' => 'md5',
          ],
        ],
      ],
    ];
    $form['webform_invitation']['chars'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Characters to be used for tokens'),
      '#options' => [
        1 => $this->t('Lower case letters (a-z)'),
        2 => $this->t('Upper case letters (A-Z)'),
        3 => $this->t('Digits (0-9)'),
        4 => $this->t('Punctuation (.,:;-_!?)'),
        5 => $this->t('Special characters (#+*=$%&|)'),
      ],
      '#default_value' => [1, 2, 3],
      '#required' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="type"]' => [
            'value' => 'md5',
          ],
        ],
      ],
    ];
    $form['webform'] = [
      '#type' => 'value',
      '#value' => $webform,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $form_state->getValue('webform');
    $webform_id = $webform->id();

    $number = $form_state->getValue('number');
    $type = $form_state->getValue('type');
    $length = $form_state->getValue('length');
    $chars = $form_state->getValue('chars');

    // Prepare character set for custom code.
    $set = '';
    if (!empty($chars[1])) {
      $set .= 'abcdefghijklmnopqrstuvwxyz';
    }
    if (!empty($chars[2])) {
      $set .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if (!empty($chars[3])) {
      $set .= '0123456789';
    }
    if (!empty($chars[4])) {
      $set .= '.,:;-_!?';
    }
    if (!empty($chars[5])) {
      $set .= '#+*=$%&|';
    }

    $i = $l = 1;
    // Process all requested tokens.
    while ($i <= $number && $l < $number * 10) {

      $code = '';
      // Code generation.
      switch ($type) {
        case 'md5':
          $code = md5(microtime(1) * rand());
          break;

        case 'custom':
          for ($j = 1; $j <= $length; $j++) {
            $code .= $set[rand(0, strlen($set) - 1)];
          }
          break;
      }

      try {
        // Insert code to DB.
        $this->database->insert('webform_invitation_codes')->fields([
          'webform' => $webform_id,
          'code' => $code,
          'created' => $this->time->getRequestTime(),
        ])->execute();
        $i++;
      }
      catch (\Exception $e) {
        // The generated code is already in DB, make another one.
      }
      $l++;
    }

    // Output number of generated codes.
    $codes_count = $i - 1;
    if ($l >= $number * 10) {
      drupal_set_message($this->t('Due to unique constraint, only @ccount codes have been generated.', [
        '@ccount' => $codes_count,
      ]), 'error');
    }
    elseif ($codes_count == 1) {
      drupal_set_message($this->t('A single code has been generated.'));
    }
    else {
      drupal_set_message($this->t('A total of @ccount codes has been generated.', [
        '@ccount' => $codes_count,
      ]));
    }

    // Redirect user to list of codes.
    $form_state->setRedirect('entity.webform.invitation_codes', [
      'webform' => $webform_id,
    ]);
  }

}
