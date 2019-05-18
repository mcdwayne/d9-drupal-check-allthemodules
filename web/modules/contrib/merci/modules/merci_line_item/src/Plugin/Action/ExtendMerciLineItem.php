<?php

namespace Drupal\merci_line_item\Plugin\Action;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;

/**
 * Promotes a merci_line_item.
 *
 * @Action(
 *   id = "merci_line_item_extend_action",
 *   label = @Translation("Extend item."),
 *   type = "merci_line_item"
 * )
 */
class ExtendMerciLineItem extends ConfigurableActionBase implements ContainerFactoryPluginInterface {


  use ViewsBulkOperationsFormTrait;
  /**
   * User private temporary storage factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  use DependencyTrait;

  public function __construct(PrivateTempStoreFactory $tempStoreFactory, AccountInterface $currentUser, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStoreFactory = $tempStoreFactory;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('current_user'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $extend_interval = $this->configuration['extend_interval'];
    $date_field = 'merci_reservation_date';
    $end_date = new DrupalDateTime($entity->{$date_field}->getValue()[0]['end_value'] . ' ' . $extend_interval);
    $end_date_string = $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $date = $entity->{$date_field}->getValue();
    $date[0]['end_value'] = $end_date_string;
    $entity->{$date_field}->setValue($date);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    $form['validate_entities'] = [
      '#title' => $this->t('Validate before processing'),
      '#type' => 'checkbox',
      '#default_value' => isset($values['validate_entities']) ? $values['validate_entities'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'extend_interval' => '+1 days',
    ];
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  //    $form_state ->setErrorByName('extend_interval', t('Fix the errors or override validation.'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form_data = $form_state->get('views_bulk_operations');

    $form['extend_interval'] = [
      '#type' => 'textfield',
      '#title' => t('Default checkin date and time.'),
      '#default_value' => $this->configuration['extend_interval'],
      '#required' => TRUE,
    ];

    $validation_errors = $this->getTempstore($form_data['view_id'], $form_data['display_id'])->get('validation_errors');

    if ($validation_errors) {
      foreach ($validation_errors as $entity_id => $violations) {
        $label = $violations->getEntity()->label();
        drupal_set_message(t('Errors for %label', ['%label' => $label]));
        foreach ($violations as $violation) {
          drupal_set_message($violation->getMessage());
        }
      }
      $form['override_validation'] = [
        '#type' => 'checkbox',
        '#title' => t('Override Validation Errors'),
        '#default_value' => $form_state->getValue('override_validation'),
      ];
      $this->getTempstore($form_data['view_id'], $form_data['display_id'])->delete('validation_errors');
    }

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['extend_interval'] = $form_state->getValue('extend_interval');
    $violations = array();
    if ($form_data = $form_state->get('views_bulk_operations') and $form_data['preconfiguration']['validate_entities'] and $form_state->getValue('override_validation') == FALSE) {
      $form_data = $form_state->get('views_bulk_operations');
      if (!$form_data) {
        return;
      }
      $entity_ids = array();
      foreach ($form_data['list'] as $item) {
        $entity_ids[] = $item[0];
      }
      $entities = \Drupal::entityTypeManager()->getStorage('merci_line_item')->loadMultiple($entity_ids);
      $extend_interval = $form_state->getValue('extend_interval');
      $date_field = 'merci_reservation_date';

      foreach ($entities as $entity) {
        $end_date = new DrupalDateTime($entity->{$date_field}->getValue()[0]['end_value'] . ' ' . $extend_interval);
        $end_date_string = $end_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
        $date = $entity->{$date_field}->getValue();
        $date[0]['end_value'] = $end_date_string;
        $entity->{$date_field}->setValue($date);
        $violation = $entity->validate();
        if ($violation->has(0) == TRUE) {
          $violations[$entity->id()] = $violation;
        }
      }
    }
    if (count($violations)) {
      $this->getTempstore($form_data['view_id'], $form_data['display_id'])->set('validation_errors', $violations);
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\merci_line_item\NodeInterface $object */
    $access = $object->access('update', $account, TRUE);
    return $return_as_object ? $access : $access->isAllowed();
  }

}
