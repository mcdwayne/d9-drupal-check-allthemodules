<?php

namespace Drupal\postup_mail_example\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\postup_mail\SenderInterface;

class PostUpMailExampleForm extends FormBase {

  /**
   * The PostUp mail sender.
   *
   * @var \Drupal\postup_mail\SenderInterface
   */
  protected $sender;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('postup_mail.sender')
    );
  }

  /**
   * Constructs a new PostUpMailExampleForm.
   *
   * @param \Drupal\postup_mail\SenderInterface $sender
   *   The PostUp mail sender.
   */
  public function __construct(SenderInterface $sender) {
    $this->sender = $sender;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'postup_mail_example_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $this->sender->send_mail_template($email);
  }

}
