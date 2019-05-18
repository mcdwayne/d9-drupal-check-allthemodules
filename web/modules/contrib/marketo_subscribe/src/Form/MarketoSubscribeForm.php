<?php

namespace Drupal\marketo_subscribe\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\marketo_ma\Service\MarketoMaApiClientInterface;

/**
 * Subscribe to a Marketo list.
 */
class MarketoSubscribeForm extends FormBase {

  use MessengerTrait;

  /**
   * The ID for this form.
   *
   * Set as class property so it can be overwritten as needed.
   *
   * @var string
   */
  private $formId = 'marketo_subscribe_form';

  /**
   * The Marketo list ID for this form.
   *
   * @var int
   */
  private $listId = NULL;

  /**
   * The Marketo API client.
   *
   * @var \Drupal\marketo_ma\Service\MarketoMaApiClientInterface
   */
  private $client;

  /**
   * MarketoSubscribeForm constructor.
   *
   * @param \Drupal\marketo_ma\Service\MarketoMaApiClientInterface $client
   *   The Marketo API client.
   */
  public function __construct(MarketoMaApiClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   * Sets the ID for this form.
   *
   * @param string $formId
   *   The ID that should be used for this form.
   */
  public function setFormId($formId) {
    $this->formId = $formId;
  }

  /**
   * Sets the (Marketo) list ID for this form.
   *
   * The List Id can be obtained from the URL of the list in the UI, where the
   * URL will resemble https://app-***.marketo.com/#ST1001A1. In this URL, the
   * id is 1001, it will always be between the first set of letters in the URL
   * and the second set of letters.
   *
   * @param int $listId
   *   The ID of the Marketo list.
   */
  public function setListId($listId) {
    $this->listId = $listId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['#attributes'] = ['class' => ['marketo-subscribe-form']];

    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email address'),
      '#required' => TRUE,
      '#default_value' => '',
      '#size' => 20,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $this->client->getLeadByEmail($email);

    if ($this->listId && method_exists($this->client, 'addLeadToListByEmail')) {
      $this->client->addLeadToListByEmail($this->listId, $email);
    }

    $form_state->setRedirectUrl(Url::fromRoute('<current>'));

    $this->messenger()->addMessage($this->t('Thank you for your subscription. We will stay in touch!'));
  }

}
