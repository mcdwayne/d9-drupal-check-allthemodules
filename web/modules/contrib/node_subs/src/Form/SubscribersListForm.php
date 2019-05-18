<?php

namespace Drupal\node_subs\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Url;
use Drupal\node_subs\Service\AccountService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SubscribersListForm.
 */
class SubscribersListForm extends FormBase {

  private $translation_manager;

  /**
   * Drupal\node_subs\Service\AccountService definition.
   *
   * @var \Drupal\node_subs\Service\AccountService
   */
  protected $account;

  public function __construct(TranslationManager $translationManager, AccountService $account_service) {
    $this->translation_manager = $translationManager;
    $this->account = $account_service;
  }

  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('string_translation'),
      $container->get('node_subs.account')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_subs_users_list_page';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options = array();
    $conditions = array();
    $conditions['deleted'] = 0;
    $subscribers = $this->account->loadMultiple([], $conditions, 10);

    $header = array('name' => $this->t('Name'), 'email' => $this->t('Email'), 'status' => $this->t('Status'), 'action' => $this->t('Actions'));

    foreach ($subscribers as $subscrs_ind => $subscriber) {
      $options[$subscrs_ind]['name'] = $subscriber->name;
      $options[$subscrs_ind]['email'] = $subscriber->email;
      $options[$subscrs_ind]['status'] = $subscriber->status ? $this->t('Subscribed') : $this->t('Unsubscribed');

      $options[$subscrs_ind]['action']['data'] = [
        '#theme' => 'links',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('node_subs.edit', ['subscriber_id' => $subscriber->id]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('node_subs.delete', ['subscriber_id' => $subscriber->id]),
          ],
        ],
      ];
    }

    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('Subscribers not found.'),
      '#attributes' => array('class' => array('node-subs-subscribers'))
    ];
    $form['select'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose an action to apply for the selected items'),
      '#options' => [
        'unsubscribe' => $this->t('Unsubscribe'),
        'subscribe' => $this->t('Subscribe'),
        'delete' => $this->t('Delete'),
      ],
      '#size' => 1,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $selected_items = array_filter($form_state->getValue('table'));
    if (empty($selected_items)) {
      $form_state->setErrorByName('table', $this->t('No items selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $selected_ids = array_filter($form_state->getValue('table'));
    $action = $form_state->getValue('select');
    $subscribers = $this->account->loadMultiple($selected_ids);

    switch ($action) {
      case 'unsubscribe':
        foreach ($subscribers as $subscriber) {
          $subscriber->status = 0;
          $this->account->save($subscriber);
        }
        $message = $this->translation_manager->formatPlural(count($subscribers), 'Unsubscribed 1 user', 'Unsubscribed @count users');
        drupal_set_message($message);
        break;
      case 'subscribe':
        foreach ($subscribers as $subscriber) {
          $subscriber->status = 1;
          $this->account->save($subscriber);
        }
        $message = $this->translation_manager->formatPlural(count($subscribers), 'Subscribed 1 user', 'Subscribed @count users');
        drupal_set_message($message);
        break;
      case 'delete':
        foreach ($subscribers as $subscriber) {
          $this->account->delete($subscriber);
        }
        $message = $this->translation_manager->formatPlural(count($subscribers), 'Deleted 1 user', 'Deleted @count users');
        drupal_set_message($message);
        break;
    }

  }

}
