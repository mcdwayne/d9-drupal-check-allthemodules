<?php

namespace Drupal\spamicide\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Spamicide form.
 *
 * @property \Drupal\spamicide\SpamicideInterface $entity
 */
class SpamicideForm extends EntityForm {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * SpamicideForm constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Constructor.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Create spamicide.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Create spamicide.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $spamicide = $this->entity;

    // Support to set a default form_id through a query argument.
    $request = $this->requestStack->getCurrentRequest();
    if ($spamicide->isNew() && !$spamicide->id() && $request->query->has('form_id')) {
      $spamicide->set('formId', $request->query->get('form_id'));
      $spamicide->set('label', $request->query->get('form_id'));
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form ID name'),
      '#maxlength' => 255,
      '#default_value' => $spamicide->label(),
      '#description' => $this->t('Label for the spamicide.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $spamicide->id(),
      '#machine_name' => [
        'exists' => '\Drupal\spamicide\Entity\Spamicide::load',
      ],
      '#disabled' => !$spamicide->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $spamicide->status(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new spamicide %label.', $message_args)
      : $this->t('Updated spamicide %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
