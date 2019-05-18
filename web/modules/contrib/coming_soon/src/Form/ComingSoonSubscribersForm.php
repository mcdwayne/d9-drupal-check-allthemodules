<?php

namespace Drupal\coming_soon\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\coming_soon\Entity\Subscriber;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection as Database;

/**
 * ComingSoonSubscribersForm form.
 */
class ComingSoonSubscribersForm extends FormBase {

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Construct.
   *
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EmailValidator $email_validator, Database $database) {
    $this->emailValidator = $email_validator;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('email.validator'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coming_soon_subscribers_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attributes'] = [
      'role' => 'form',
      'class' => ['form-inline', 'signup'],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => NULL,
      '#required' => TRUE,
      '#prefix' => '<div class="form-group">',
      '#suffix' => '</div>',
      '#theme_wrappers' => [],
      '#attributes' => [
        'class' => ['form-control'],
        'placeholder' => $this->t('Enter your email address'),
      ],
    ];

    $form['submit'] = [
      '#type' => 'button',
      '#value' => $this->t('Get notified!'),
      '#attributes' => [
        'class' => ['btn', 'btn-theme'],
      ],
      '#ajax' => [
        'callback' => [$this, 'validateEmailAjax'],
        'event' => 'click',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#suffix' => '<span class="cs-msg"></span>',
    ];

    return $form;
  }

  /**
   * Ajax callback to validate the email field.
   */
  public function validateEmailAjax(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $email = $form_state->getValue('email');
    $css = ['color' => 'red'];

    // Check if the email is valid.
    if (!$this->emailValidator->isValid($email)) {
      $message = $this->t('Please provide a valid e-mail address.');
      $response->addCommand(new CssCommand('.cs-msg', $css));
      $response->addCommand(new HtmlCommand('.cs-msg', $message));
    }
    else {
      $query = $this->database->select('subscribers', 's');
      $query->addField('s', 'email');
      $query->condition('s.email', $email);
      $result = $query->execute()->fetchField();

      if (!empty($result)) {
        $message = $this->t('You have already subscribed.');
        $response->addCommand(new CssCommand('.cs-msg', $css));
        $response->addCommand(new HtmlCommand('.cs-msg', $message));
      }
      else {
        $subscriber = Subscriber::create(['email' => $email]);
        $subscriber->save();

        $message = $this->t('You subscribed successfully, we will notify you as soon as the website is ready.');
        $css = ['color' => 'green'];
        $response->addCommand(new CssCommand('.cs-msg', $css));
        $response->addCommand(new HtmlCommand('.cs-msg', $message));
      }
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->emailValidator->isValid($form_state->getValue('email'))) {
      $form_state->setErrorByName('email', $this->t("Please provide a valid e-mail address."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Thank you for subscribing, you will be the first to know when the website is ready'));
  }

}
