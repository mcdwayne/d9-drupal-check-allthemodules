<?php

namespace Drupal\astrology\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\astrology\Controller\UtilityController;

/**
 * Class AstrologySignEditTextForm.
 */
class AstrologySignEditTextForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'astrology_sign_edit_text';
  }

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Class constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory) {
    $this->connection = $connection;
    $this->config = $config_factory;
    $this->utility = new UtilityController($this->connection, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $astrology_id = NULL, $sign_id = NULL, $text_id = NULL) {

    $astrology_config = $this->config('astrology.settings');
    $this->format_character = $astrology_config->get('format_character');
    $this->astrology_id = $astrology_id;
    $this->astrology_sign_id = $sign_id;
    $this->text_id = $text_id;

    $options = $this->utility->getAstrologySignArray($sign_id, $astrology_id);
    $result = $this->utility->isValidText($sign_id, $text_id);

    $form['label'] = [
      '#markup' => $this->t('Name <strong>:name</strong>', [
        ':name' => $options['name'],
      ]),
    ];
    $form['text'] = [
      '#type' => 'text_format',
      '#format' => $result->text_format,
      '#title' => $this->t('Enter Text'),
      '#required' => TRUE,
      '#default_value' => $result->text,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $text = $form_state->getValue('text');
    $this->connection->update('astrology_text')
      ->fields([
        'text' => $text['value'],
        'text_format' => $text['format'],
      ])
      ->condition('id', $this->text_id, '=')
      ->execute();
    $form_state->setRedirect('astrology.astrology_sign_list_text', ['astrology_id' => $this->astrology_id]);
    drupal_set_message($this->t('Text updated.'));
  }

}
