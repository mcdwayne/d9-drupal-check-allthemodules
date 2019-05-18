# PostUp Mail

PostUp Mail module provides a integration with [PostUp.com](https://www.postup.com/) service.

## How to use:

\Drupal::service('postup_mail.sender')->send_mail_template('example@example.com');

or dinamyc

/**
 * {@inheritdoc}
 */
public static function create(ContainerInterface $container) {
  return new static(
    $container->get('postup_mail.sender')
  );
}

/**
 * Constructs a new RegisterForm.
 *
 * @param \Drupal\postup_mail\SenderInterface $sender
 *   The PostUp mail sender.
 */
public function __construct(Connection $database, RequestStack $request_stack, PreRegistrationInfo $pre_registration_info, SenderInterface $sender) {
  $this->sender = $sender;
}

public function submitForm(array &$form, FormStateInterface $form_state) {
  $this->sender->send_mail_template($email);
}
