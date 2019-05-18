<?php
/**
 * @file
 * Contains \Drupal\form_submission_timeout\Form\TimedAdminForm.
 */

namespace Drupal\form_submission_timeout\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\form_submission_timeout\Controller\FstController;

/**
 * Timed Admin form.
 */
class TimedAdminForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('form_submission_timeout.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_submission_timeout_timed_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = NULL) {
    /**
     * One Time - Form submission will be activated only once for given date and time.
     * Everyday - Form submission will activate everyday during a specific time period.
     * Weekdays - Form submission will activate on weekdays only.
     * Weekends - Form submission will activate on weekends only.
     */
    $form['sub_out_stop_submission_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Timed Submission'),
      '#theme' => 'form_submission_timeout_timed_config_form',
      '#tree' => TRUE,
      '#collapsed' => FALSE,
      '#collapsible' => FALSE,
    );

    // Populate form ids into the settings form.
    $form_ids = \Drupal::config('form_submission_timeout.settings')->get('sub_out_stop_form_ids');

    foreach ($form_ids as $form_id => $form_detail) {
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_stop_form_id_display'] = array(
        '#markup' => $form_detail['sub_out_stop_form_id'],
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_stop_form_id'] = array(
        '#type' => 'hidden',
        '#value' => $form_detail['sub_out_stop_form_id'],
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_timeout_frequency'] = array(
        '#type' => 'select',
        '#options' => array(
          'none' => $this->t('None'),
          'once' => $this->t('Once'),
          'everyday' => $this->t('Everyday'),
          'weekdays' => $this->t('Weekdays'),
          'weekends' => $this->t('Weekends')
        ),
        '#default_value' => $form_detail['sub_out_timeout_frequency'],
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_start_date'] = array(
        '#type' => 'date',
        '#default_value' => $form_detail['sub_out_start_date'],
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_start_timeout_period'] = array(
        '#type' => 'textfield',
        '#default_value' => $form_detail['sub_out_start_timeout_period'],
        '#size' => 5,
        '#maxlength' => 5
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_stop_date'] = array(
        '#type' => 'date',
        '#default_value' => $form_detail['sub_out_stop_date'],
        '#states' => array(
          'invisible' => array(
            ':input[name="sub_out_stop_submission_fieldset[' . $form_id . '][sub_out_timeout_frequency]"]' => array('value' => 'once'),
          ),
        ),
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_stop_timeout_period'] = array(
        '#type' => 'textfield',
        '#default_value' => $form_detail['sub_out_stop_timeout_period'],
        '#size' => 5,
        '#maxlength' => 5
      );
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_stop_timeout_message'] = array(
        '#type' => 'textfield',
        '#size' => 15,
        '#default_value' => $form_detail['sub_out_stop_timeout_message'],
        '#attributes' => array(
          'placeholder' => $this->t('Session timed out.'),
          'class' => array('fst-text'),
        ),
      );

      $timedRemoveParams = array(
        'name' => 'sub_out_stop_form_ids',
        'form_id' => $form_id,
        'action' => 'timed'
      );
      $timedRemoveUrl = Url::fromRoute('form_submission_timeout.remove', $timedRemoveParams);
      $timedRemoveLink = \Drupal::l($this->t('Remove'), $timedRemoveUrl);
      $form['sub_out_stop_submission_fieldset'][$form_id]['sub_out_stop_remove'] = array(
        '#markup' => '<div>' . $timedRemoveLink . '</div>'
      );
    }

    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_stop_form_id'] = array(
      '#type' => 'textfield',
      '#size' => 25,
      '#attributes' => array(
        'placeholder' => $this->t('$form_id'),
        'class' => array('fst-text'),
      ),
      '#description' => $this->t('Form id'),
    );
    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_timeout_frequency'] = array(
      '#type' => 'select',
      '#options' => array(
        'none' => $this->t('None'),
        'once' => $this->t('Once'),
        'everyday' => $this->t('Everyday'),
        'weekdays' => $this->t('Weekdays'),
        'weekends' => $this->t('Weekends')
      ),
      '#description' => $this->t('Frequency'),
    );

    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_start_date'] = array(
      '#type' => 'date',
      '#description' => $this->t('Session start date'),
    );

    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_start_timeout_period'] = array(
      '#type' => 'textfield',
      '#size' => 5,
      '#maxlength' => 5,
      '#attributes' => array(
        'placeholder' => '00:00',
        'id' => 'start-time',
      ),
      '#description' => $this->t('24 hour format e.g, hh:mm'),
    );

    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_stop_date'] = array(
      '#type' => 'date',
      '#description' => $this->t('Session stop date'),
      '#states' => array(
        'invisible' => array(
          ':input[name="sub_out_stop_submission_fieldset[new_form_entry][sub_out_new_timeout_frequency]"]' => array('value' => 'once'),
        ),
      ),
    );

    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_stop_timeout_period'] = array(
      '#type' => 'textfield',
      '#size' => 5,
      '#maxlength' => 5,
      '#attributes' => array(
        'placeholder' => '00:00',
        'id' => 'end-time',
      ),
      '#description' => $this->t('24 hour format e.g, hh:mm'),
    );

    $form['sub_out_stop_submission_fieldset']['new_form_entry']['sub_out_new_stop_timeout_message'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#attributes' => array(
        'placeholder' => 'Session timeout.',
        'id' => 'session-msg',
        'class' => array('fst-text'),
      ),
      '#description' => $this->t('Message to show on <br>form submission timeout'),
    );
    
    $form['#attached']['library'][] = 'form_submission_timeout/base_file';

    $form['sub_out_submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save Configuration',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fieldset = $form_state->getValue('sub_out_stop_submission_fieldset');
    $new_values = $fieldset['new_form_entry'];
    $form_ids = \Drupal::config('form_submission_timeout.settings')->get('sub_out_stop_form_ids');
    $new_form_id = $new_values['sub_out_new_stop_form_id'];
    unset($fieldset['new_form_entry']);

    // Validate editable forms.
    if ($form_ids) {
      foreach ($fieldset as $val) {

        // Check for time format inputs.
        if (!preg_match("#^([01]?[0-9]|2[0-3]):[0-5][0-9]?$#", $val['sub_out_start_timeout_period'])) {
          $form_state->setErrorByName('sub_out_stop_submission_fieldset][' . $val['sub_out_stop_form_id'] . '][sub_out_start_timeout_period', $this->t('Timeout value can only be in 24 hour time format e.g, hh:mm.'));
        }
        if (!preg_match("#^([01]?[0-9]|2[0-3]):[0-5][0-9]?$#", $val['sub_out_stop_timeout_period'])) {
          $form_state->setErrorByName('sub_out_stop_submission_fieldset][' . $val['sub_out_stop_form_id'] . '][sub_out_stop_timeout_period', $this->t('Timeout value can only be in 24 hour time format e.g, hh:mm.'));
        }

        // Start date and time should not be equal to End date and time.
        if ($val['sub_out_start_timeout_period'] == $val['sub_out_stop_timeout_period']) {
          $form_state->setErrorByName('sub_out_stop_submission_fieldset][' . $val['sub_out_stop_form_id'] . '][sub_out_stop_timeout_period', $this->t('Timeout value can not be same as Time start value.'));
        }
        if ($val['sub_out_start_date'] == $val['sub_out_stop_date'] &&
           $val['sub_out_start_timeout_period'] == $val['sub_out_stop_timeout_period']) {
          $form_state->setErrorByName('sub_out_stop_submission_fieldset][' . $val['sub_out_stop_form_id'] . '][sub_out_stop_date', $this->t('Start date can not be same as End date.'));
        }
      }
    }
 
    // Check for duplicate values and blank spaces for new form IDs.
    if ($form_state->hasValue($new_values['sub_out_new_stop_form_id'])) {
      if (array_key_exists($new_form_id, $form_ids)) {
        $form_state->setErrorByName('sub_out_stop_submission_fieldset][new_form_entry][sub_out_new_stop_form_id', $this->t('Only one rule per form is allowed.'));
      }
      else {
        if (strlen($new_form_id) != 0 && strpos($new_form_id, ' ')) {
          $form_state->setErrorByName($new_values['sub_out_new_stop_form_id'], $this->t('Form ID cannot contain blank spaces.'));
        }
      }

      // Check for time format inputs.
      if (!preg_match("#^([01]?[0-9]|2[0-3]):[0-5][0-9]?$#", $new_values['sub_out_new_start_timeout_period'])) {
        $form_state->setErrorByName('sub_out_stop_submission_fieldset][new_form_entry][sub_out_new_start_timeout_period', $this->t('Timeout value can only be in 24 hour time format e.g, hh:mm.'));
      }
      if (!preg_match("#^([01]?[0-9]|2[0-3]):[0-5][0-9]?$#", $new_values['sub_out_new_stop_timeout_period'])) {
        $form_state->setErrorByName('sub_out_stop_submission_fieldset][new_form_entry][sub_out_new_stop_timeout_period', $this->t('Timeout value can only be in 24 hour time format e.g, hh:mm.'));
      }

      // Start date and time should not be equal to End date and time.
      if ($new_values['sub_out_new_start_timeout_period'] == $new_values['sub_out_new_stop_timeout_period']) {
        $form_state->setErrorByName('sub_out_stop_submission_fieldset][new_form_entry][sub_out_new_stop_timeout_period', $this->t('Timeout value can not be same as Time start value.'));
      }
      if ($new_values['sub_out_new_start_date'] == $new_values['sub_out_new_stop_date'] && $new_values['sub_out_new_start_timeout_period'] == $new_values['sub_out_new_stop_timeout_period']) {
        $form_state->setErrorByName('sub_out_stop_submission_fieldset][new_form_entry][sub_out_new_stop_date', $this->t('Start date can not be same as End date.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fieldset = $form_state->getValue('sub_out_stop_submission_fieldset');
    $new_values = $fieldset['new_form_entry'];
    
    if (count($fieldset) > 1) {
      unset($fieldset['new_form_entry']);
      FstController::updateFormIds('sub_out_stop_form_ids', $fieldset, 'update');
    }

    if (!empty($new_values['sub_out_new_stop_form_id'])) {
      $data_config[$new_values['sub_out_new_stop_form_id']] = array(
        'sub_out_stop_form_id' => $new_values['sub_out_new_stop_form_id'],
        'sub_out_timeout_frequency' => $new_values['sub_out_new_timeout_frequency'],
        'sub_out_start_date' => $new_values['sub_out_new_start_date'],
        'sub_out_start_timeout_period' => $new_values['sub_out_new_start_timeout_period'],
        'sub_out_stop_date' => $new_values['sub_out_new_stop_date'],
        'sub_out_stop_timeout_period' => $new_values['sub_out_new_stop_timeout_period'],
        'sub_out_stop_timeout_message' => empty($new_values['sub_out_new_stop_timeout_message']) ? 'Session expired.' : $new_values['sub_out_new_stop_timeout_message'],
      );

      // Save and update the settings.
      FstController::updateFormIds('sub_out_stop_form_ids', $data_config, 'add');
    }
    drupal_set_message($this->t('The settings have been saved'), 'status');
    return $form;
  }
}
