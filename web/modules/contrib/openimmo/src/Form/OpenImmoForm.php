<?php

namespace Drupal\openimmo\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenImmoForm.
 *
 * @package Drupal\openimmo\Form
 */
class OpenImmoForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $openimmo = $this->entity;
    $feed_config = $openimmo->getFeedConfig();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $openimmo->label(),
      '#description' => $this->t("Label for the OpenImmo Source."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $openimmo->id(),
      '#machine_name' => [
        'exists' => '\Drupal\openimmo\Entity\OpenImmo::load',
      ],
      '#disabled' => !$openimmo->isNew(),
    ];

    $feed_type = $openimmo->isNew() ? $this->getRequest()->query->get('feed_type', 'false') : $openimmo->getFeedType();
    $form['feed_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Feed Type'),
      '#options' => ['file' => 'File', 'ftp' => 'FTP'],
      '#maxlength' => 255,
      '#default_value' => $feed_type,
      '#description' => $this->t("Source of OpenImmo XML data."),
      '#required' => FALSE,
    ];

    $form['feed_config'] = [
      '#type' => 'fieldset',
    ];

    $form['feed_config']['file_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File path'),
      '#maxlength' => 255,
      '#default_value' => isset($feed_config['file_path']) ? $feed_config['file_path'] : '',
      '#description' => $this->t("Fle path."),
      '#states' => [
        'visible' => [
          'select[name="feed_type"]' => ['value' => 'file'],
        ],
      ],
    ];

    $form['feed_config']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#maxlength' => 255,
      '#default_value' => isset($feed_config['username']) ? $feed_config['username'] : '',
      '#description' => $this->t("User name."),
      '#states' => [
        'visible' => [
          'select[name="feed_type"]' => ['value' => 'ftp'],
        ],
      ],
    ];
    $form['feed_config']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#default_value' => isset($feed_config['password']) ? $feed_config['password'] : '',
      '#description' => $this->t("Password."),
      '#states' => [
        'visible' => [
          'select[name="feed_type"]' => ['value' => 'ftp'],
        ],
      ],
    ];
    $form['feed_config']['hostname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname'),
      '#maxlength' => 255,
      '#default_value' => isset($feed_config['hostname']) ? $feed_config['hostname'] : '',
      '#description' => $this->t("Hostname."),
      '#states' => [
        'visible' => [
          'select[name="feed_type"]' => ['value' => 'ftp'],
        ],
      ],
    ];
    $form['feed_config']['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#maxlength' => 25,
      '#default_value' => isset($feed_config['port']) ? $feed_config['port'] : '22',
      '#description' => $this->t("Port."),
      '#states' => [
        'visible' => [
          'select[name="feed_type"]' => ['value' => 'ftp'],
        ],
      ],
    ];
    $form['feed_config']['server_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server path'),
      '#maxlength' => 255,
      '#default_value' => isset($feed_config['server_path']) ? $feed_config['server_path'] : '',
      '#description' => $this->t("Server path."),
      '#states' => [
        'visible' => [
          'select[name="feed_type"]' => ['value' => 'ftp'],
        ],
      ],
    ];
    // todo: add fields for sftp, http.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $openimmo = $this->entity;

    $feed_config = [];
    if ('file' == $form_state->getValue('feed_type')) {
      $feed_config['file_path'] = $form_state->getValue('file_path');
    }
    elseif ('ftp' == $form_state->getValue('feed_type')) {
      $feed_config['username'] = $form_state->getValue('username');
      $feed_config['password'] = $form_state->getValue('password');
      $feed_config['hostname'] = $form_state->getValue('hostname');
      $feed_config['port'] = $form_state->getValue('port');
      $feed_config['server_path'] = $form_state->getValue('server_path');
    }
    // todo: add data for sftp, http.
    $openimmo->addFeedConfig($feed_config);

    $status = $openimmo->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label OpenImmo Source.', [
          '%label' => $openimmo->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label OpenImmo Source.', [
          '%label' => $openimmo->label(),
        ]));
    }
    $form_state->setRedirectUrl($openimmo->toUrl('collection'));
  }

}
