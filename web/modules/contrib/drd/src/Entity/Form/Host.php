<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Host edit forms.
 *
 * @ingroup drd
 */
class Host extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\drd\Entity\HostInterface $entity */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getLangCode(),
      '#languages' => Language::STATE_ALL,
    ];

    $form['ssh2']['ssh2-settings'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          '#edit-ssh2-value' => ['checked' => FALSE],
        ],
      ],
    ] + $this->buildSshSettingsForm();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\drd\Entity\HostInterface $entity */
    $entity = $this->entity;
    $settings = [
      'host' => $form_state->getValue('ssh2_host'),
      'port' => $form_state->getValue('ssh2_port'),
      'auth' => [
        'mode' => $form_state->getValue('ssh2_auth_mode'),
        'username' => $form_state->getValue('ssh2_auth_username'),
        'password' => $form_state->getValue('ssh2_auth_password'),
        'file_public_key' => $form_state->getValue('ssh2_auth_file_public_key'),
        'file_private_key' => $form_state->getValue('ssh2_auth_file_private_key'),
        'key_secret' => $form_state->getValue('ssh2_auth_key_secret'),
      ],
    ];
    $entity->setSshSettings($settings);
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Host.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Host.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.drd_host.canonical', ['drd_host' => $entity->id()]);
  }

  /**
   * Build the SSH settings form.
   */
  private function buildSshSettingsForm() {
    /* @var \Drupal\drd\Entity\HostInterface $host */
    $host = $this->entity;
    $settings = $host->getSshSettings();
    $form = [];

    $form['ssh2_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname or IP'),
      '#default_value' => $settings['host'],
    ];

    $form['ssh2_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#default_value' => $settings['port'],
    ];

    $form['ssh2_auth_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#default_value' => $settings['auth']['username'],
    ];

    $auth_options = [
      '1' => $this->t('Username and password'),
      '2' => $this->t('Public key'),
      '3' => $this->t('Agent'),
    ];
    $form['ssh2_auth_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Auth mode'),
      '#options' => $auth_options,
      '#default_value' => $settings['auth']['mode'],
    ];

    $form['ssh2_auth_mode_1'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="ssh2_auth_mode"]' => ['value' => '1'],
        ],
      ],
    ];
    $form['ssh2_auth_mode_1']['ssh2_auth_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $settings['auth']['password'],
    ];

    $form['ssh2_auth_mode_2'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="ssh2_auth_mode"]' => ['value' => '2'],
        ],
      ],
    ];
    $form['ssh2_auth_mode_2']['ssh2_auth_file_public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key filename'),
      '#default_value' => $settings['auth']['file_public_key'],
    ];
    $form['ssh2_auth_mode_2']['ssh2_auth_file_private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key filename'),
      '#default_value' => $settings['auth']['file_private_key'],
    ];
    $form['ssh2_auth_mode_2']['ssh2_auth_key_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Passphrase for encrypted private key'),
      '#default_value' => $settings['auth']['key_secret'],
    ];

    return $form;
  }

}
