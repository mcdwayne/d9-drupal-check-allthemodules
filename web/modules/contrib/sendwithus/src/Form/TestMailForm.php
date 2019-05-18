<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\sendwithus\ApiManager;
use Drupal\sendwithus\Entity\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to manage sendwithus settings.
 */
class TestMailForm extends FormBase {

  /**
   * The api manager.
   *
   * @var \Drupal\sendwithus\ApiManager
   */
  protected $apiManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\sendwithus\ApiManager $apiManager
   *   The api key service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ApiManager $apiManager, MailManagerInterface $mailManager) {
    $this->apiManager = $apiManager;
    $this->mailManager = $mailManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('sendwithus.api_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sendwithus_test_mail';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('To'),
      '#default_value' => $this->config('system.site')->get('mail'),
      '#description' => $this->t('The emails separated with comma'),
      '#required' => TRUE,
    ];

    $form['template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template ID'),
      '#default_value' => '',
      '#description' => $this->t('The sendwithus <a href="@url">template id</a>.', [
        '@url' => 'https://app.sendwithus.com/#/templates',
      ]),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send message'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $to = $form_state->getValue('to');

    // Create a new temporary template entity to allow DefaultTemplateResolver
    // to find the template.
    $entity = Template::create([
      'id' => $form_state->getValue('template_id'),
      'module' => 'sendwithus',
      'key' => 'test_mail',
    ]);
    $entity->save();

    $result = $this->mailManager->mail('sendwithus', 'test_mail', $to, $this->currentUser()->getPreferredLangcode());

    if (!empty($result['result'])) {
      drupal_set_message($this->t('Sendwithus mail sent succesfully!'));
    }
    $entity->delete();
  }

}
