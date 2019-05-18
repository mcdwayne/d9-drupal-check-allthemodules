<?php

namespace Drupal\mailing_list\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\mailing_list\MailingListInterface;
use Drupal\mailing_list\SubscriptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mailing list export.
 */
class MailingListExportForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mailing list to work with.
   *
   * @var \Drupal\mailing_list\MailingListInterface
   */
  protected $mailingList;

  /**
   * The current export result.
   *
   * @var array
   */
  protected $result = [];

  /**
   * Constructs a MailingListExportForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mailing_list_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MailingListInterface $mailing_list = NULL) {
    $this->mailingList = $mailing_list;
    $form['#title'] = $this->t('Export subscriptions from %label mailing list', ['%label' => $mailing_list->label()]);

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Filter by status'),
      '#options' => [
        '' => $this->t('- any -'),
        'active' => $this->t('Active'),
        'inactive' => $this->t('Inactive'),
      ],
      '#default_value' => $form_state->getValue('status', ''),
    ];

    if (!empty($this->result)) {
      $form['result'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Result'),
        '#rows' => 10,
        '#resizable' => 'vertical',
        '#value' => implode("\n", $this->result),
      ];
    }

    $form['actions']['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $emails = [];
    $storage = $this->entityTypeManager->getStorage('mailing_list_subscription');
    $query = $storage->getQuery()
      ->condition('mailing_list', $this->mailingList->id());

    // Filter by status.
    if ($status = $form_state->getValue('status')) {
      $query->condition('status', $status === 'active' ? SubscriptionInterface::ACTIVE : SubscriptionInterface::INACTIVE);
    }

    /** @var \Drupal\mailing_list\SubscriptionInterface $subscription */
    foreach ($storage->loadMultiple($query->execute()) as $subscription) {
      $emails[] = $subscription->getEmail();
    }

    drupal_set_message(empty($emails)
      ? $this->t('No subscriptions found.')
      : $this->formatPlural(count($emails), 'Found 1 subscription.', 'Found @count subscriptions', ['@count' => count($emails)]));

    $this->result = $emails;
    $form_state->setRebuild(TRUE);
  }

}
