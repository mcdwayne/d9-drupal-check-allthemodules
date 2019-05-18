<?php

namespace Drupal\bisnode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\bisnode\BisnodeServiceInterface;

/**
 * Class BisnodeTestConnectionForm.
 */
class BisnodeTestConnectionForm extends FormBase {

  /**
   * Drupal\bisnode\BisnodeServiceInterface definition.
   *
   * @var \Drupal\bisnode\BisnodeServiceInterface
   */
  protected $bisnodeWebapi;
  /**
   * Constructs a new BisnodeTestConnectionForm object.
   */
  public function __construct(BisnodeServiceInterface $bisnode_webapi) {
    $this->bisnodeWebapi = $bisnode_webapi;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bisnode.webapi')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bisnode_test_connection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Directory'),
      '#maxlength' => 255,
      '#size' => 64,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('bisnode.bisnodeconfig');

    $url = $config->get('bisnode_url');
    if (!$url) {
      $form_state->setErrorByName('', $this->t('A bisnode url has not been configured.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $result = $this->bisnodeWebapi->getDirectory($form_state->getValue('search'));

      drupal_set_message($this->t('Webservice response: <pre>%data</pre>', ['%data' => print_r($result, TRUE)]));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('An error has occurred: %message', ['%message' => $e->getMessage()]), 'error');
    }

  }

}
