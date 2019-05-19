<?php

namespace Drupal\user_lock\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\Url;


/**
 * Class UserLockConfigEntityForm.
 *
 * @package Drupal\user_lock\Form
 */
class UserLockConfigEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $user_lock_config_entity = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $user_lock_config_entity->label(),
      '#description' => $this->t("Label for the User lock."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_lock_config_entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\user_lock\Entity\UserLockConfigEntity::load',
      ],
      '#disabled' => !$user_lock_config_entity->isNew(),
    ];
    $form['user_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $users = _user_lock_fetch_names();
    $form['user_fieldset']['user'] = [
      '#title' => $this->t('Select User'),
      '#type' => 'select',
      '#description' => $this->t("Select User"),
      '#options' => $users,
      '#default_value' => explode(',', $user_lock_config_entity->get_user()),
      '#required' => TRUE,
      '#multiple' => TRUE,
    ];
    $form['lock_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Lock Period'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    if($user_lock_config_entity->get_lock_period_from()) {
      $default_lock_from = DrupalDateTime::createFromTimestamp($user_lock_config_entity->get_lock_period_from());
    }
    $form['lock_fieldset']['lock_period_from'] = [
      '#title' => $this->t('Lock Period From date'),
      '#type' => 'datetime',
      '#description' => $this->t("Enter user lock period from date."),
      '#required' => TRUE,
      '#default_value' => isset($default_lock_from) ? $default_lock_from : DrupalDateTime::createFromTimestamp(time()),
    ];
    if($user_lock_config_entity->get_lock_period_to()) {
      $default_lock_to = DrupalDateTime::createFromTimestamp($user_lock_config_entity->get_lock_period_to());
    }
    $form['lock_fieldset']['lock_period_to'] = [
      '#title' => $this->t('Lock Period End date'),
      '#type' => 'datetime',
      '#description' => $this->t("Enter user lock period to date."),
      '#required' => TRUE,
      '#default_value' => isset($default_lock_to) ? $default_lock_to : DrupalDateTime::createFromTimestamp(time()),
    ];
    $form['lock_message'] = [
      '#title' => $this->t('Lock Message'),
      '#type' => 'textarea',
      '#required' => TRUE,
      '#description' => $this->t("Enter Lock Message."),
      '#default_value' => $user_lock_config_entity->get_lock_message(),
    ];
    $form['redirect_url'] = [
      '#title' => $this->t('Redirect URL'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => $this->t("Enter Redirect URL."),
      '#default_value' => $user_lock_config_entity->get_redirect_url(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get Values.
    $values = $form_state->getValues();
    // Alter LockPeriod From.
    $from_date = new DrupalDateTime($values['lock_period_from']);
    $from_period = @strtotime($from_date->format('Y-m-d h:i:s a'));
    // Alter Lockperiod to.
    $to_date = new DrupalDateTime($values['lock_period_to']);
    $to_period = @strtotime($to_date->format('Y-m-d h:i:s a'));
    if($from_period > $to_period) {
      $form_state->setErrorByName('lock_period_from', $this->t('Lock Period From date should not be greater that To period.'));
    }
    $input = $form_state->getUserInput();
    $to_input_period = @strtotime($input['lock_period_to']['date'] .' '. $input['lock_period_to']['time']);
    // Check present time
    $time = DrupalDateTime::createFromTimestamp(time());
    $time_stamp = @strtotime($time->format('Y-m-d h:i:s a'));
    if($time_stamp > $to_input_period){
      $form_state->setErrorByName('lock_period_to', $this->t('Lock Period To date should be greater that present time.'));
    }
    $form_id = $values['form_id'];
    if($form_id != 'user_lock_config_entity_edit_form'){
      // Load all entities belongs to "user_default_page_config_entity".
      $entities_load = \Drupal::entityTypeManager()->getStorage('user_lock_config_entity')->loadMultiple();
      $user = $values['user'];
      // Check roles for any existence.
      $account = '';
      foreach($entities_load as $entity){
         if ($entity->get_user() ==  $user){
           global $base_url;
           $url = Url::fromUri($base_url.'/admin/structure/user_lock_config_entity/'.$entity->id().'/edit');
           $internal_link = \Drupal::l($this->t('edit'), $url);
           $account = \Drupal\user\Entity\User::load($user);
           $name = $account->getUsername();
           $form_state->setErrorByName('user', $this->t("The selected User <b>'@user'</b> is already present in @label. You can @edit here", ['@user' => $name,'@label' => $entity->get('label') ,'@edit' => $internal_link]));
         }
      }
    }
    if (!\Drupal::service('path.validator')->isValid($form_state->getValue('redirect_url'))) {
      $form_state->setErrorByName('redirect_url', $this->t("The Lock redirect path '@link_path' is either invalid or you do not have access to it.", ['@link_path' => $form_state->getValue('redirect_url')]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    //Get User input values.
    $input = $form_state->getUserInput();
    $user_input = $input['user'];
    $users_array = '';
    foreach($user_input as $key => $value){
      $users_array .= $value .',';
    }
    $user_lock_config_entity = $this->entity;
    // Alter LockPeriod From.
    $from_date = new DrupalDateTime($user_lock_config_entity->get('lock_period_from'));
    $from_period = @strtotime($from_date->format('Y-m-d h:i:s a'));
    $user_lock_config_entity->setLockFrom($from_period);
    // Alter Lockperiod to.
    $to_date = new DrupalDateTime($user_lock_config_entity->get('lock_period_to'));
    $to_period = @strtotime($to_date->format('Y-m-d h:i:s a'));
    $user_lock_config_entity->setLockTo($to_period);
    // Save entity after changes.
    $user_lock_config_entity->setUser($users_array);
    $status = $user_lock_config_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label User lock.', [
          '%label' => $user_lock_config_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label User lock.', [
          '%label' => $user_lock_config_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($user_lock_config_entity->urlInfo('collection'));
  }

}
