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

/**
 * Promotes a merci_line_item.
 *
 * @Action(
 *   id = "merci_line_item_checkin_action",
 *   label = @Translation("Checkin item."),
 *   type = "merci_line_item"
 * )
 */
class CheckinMerciLineItem extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  use DependencyTrait;
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }
  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $checkin_date = new DrupalDateTime($this->configuration['checkin_date']);
    $checkin_date->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $checkin_string = $checkin_date->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $checkin_date_field = 'field_checkin';
    $entity->set($checkin_date_field, $checkin_string);
    $entity->set('field_reservation_status', 'checked_in');
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'checkin_date' => 'now',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['checkin_date'] = [
      '#type' => 'textfield',
      '#title' => t('Default checkin date and time.'),
      '#default_value' => $this->configuration['checkin_date'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['checkin_date'] = $form_state->getValue('checkin_date');
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

