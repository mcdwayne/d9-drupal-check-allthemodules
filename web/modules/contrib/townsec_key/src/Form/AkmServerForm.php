<?php

namespace Drupal\townsec_key\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AkmServerForm extends EntityForm {

  /** @var string */
  protected $drupalRoot;

  public function __construct($drupal_root) {
    $this->drupalRoot = $drupal_root;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('app.root')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $server = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AKM Server Label'),
      '#maxlength' => 255,
      '#default_value' => $server->label,
      '#required' => TRUE,
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#description' => $this->t('This must match the name given to this AKM at initialization.'),
      '#title' => $this->t('AKM Server Name'),
      '#maxlength' => 255,
      '#default_value' => $server->name,
      '#required' => TRUE,
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hostname or IP Address'),
      '#maxlength' => 255,
      '#default_value' => $server->host,
      '#required' => TRUE,
    ];

    $form['local_cert'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Local Cert'),
      '#maxlength' => 255,
      '#default_value' => $server->local_cert,
      '#required' => TRUE,
    ];

    $form['cafile'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CA Cert'),
      '#maxlength' => 255,
      '#default_value' => $server->cafile,
      '#required' => TRUE,
    ];

    $form['user_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Port'),
      '#maxlength' => 255,
      '#default_value' => $server->user_port,
      '#required' => TRUE,
    ];

    $form['encrypt_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Encrypt Port'),
      '#maxlength' => 255,
      '#default_value' => $server->encrypt_port,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $local_cert = realpath($form_state->getValue('local_cert'));
    if ($local_cert === FALSE || !is_readable($local_cert)) {
      $form_state->setErrorByName('local_cert', 'Local Cert must be a readable file.');
    }

    $cafile = realpath($form_state->getValue('cafile'));
    if ($cafile === FALSE || !is_readable($cafile)) {
      $form_state->setErrorByName('cafile', 'CA Cert must be a readable file.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $server = $this->entity;
    $status = $server->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %name AKM server.', [
        '%name' => $server->name,
      ]));
    }
    else {
      drupal_set_message($this->t('The %name %name AKM server was not saved.', [
        '%name' => $server->name,
      ]));
    }

    $form_state->setRedirect('entity.akm_server.collection');
  }

}
