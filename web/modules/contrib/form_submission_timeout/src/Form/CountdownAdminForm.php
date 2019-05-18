<?php
/**
 * @file
 * Contains \Drupal\form_submission_timeout\Form\CountdownAdminForm.
 */

namespace Drupal\form_submission_timeout\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\form_submission_timeout\Controller\FstController;

/**
 * Countdown Admin form.
 */
class CountdownAdminForm extends FormBase {
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
    return 'form_submission_timeout_countdown_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['sub_out_timeout_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Submission Timer'),
      '#theme' => 'form_submission_timeout_config_form',
      '#tree' => TRUE,
      '#collapsed' => FALSE,
      '#collapsible' => FALSE,
    );

    // Populate form ids into the settings form.
    $form_ids = \Drupal::config('form_submission_timeout.settings')->get('sub_out_form_ids');
    foreach ($form_ids as $form_id => $form_detail) {
      $form['sub_out_timeout_fieldset'][$form_id]['sub_out_form_id_display'] = array(
        '#markup' => $form_detail['sub_out_form_id'],
      );
      $form['sub_out_timeout_fieldset'][$form_id]['sub_out_form_id'] = array(
        '#type' => 'hidden',
        '#value' => $form_detail['sub_out_form_id'],
      );
      $form['sub_out_timeout_fieldset'][$form_id]['sub_out_show_timer'] = array(
        '#type' => 'checkbox',
        '#default_value' => $form_detail['sub_out_show_timer'],
        '#title' => $this->t('Show'),
      );
      $form['sub_out_timeout_fieldset'][$form_id]['sub_out_timeout_period'] = array(
        '#type' => 'textfield',
        '#size' => 15,
        '#default_value' => $form_detail['sub_out_timeout_period'],
        '#attributes' => array(
          'class' => array('countdown-time'),
        ),
      );
      $form['sub_out_timeout_fieldset'][$form_id]['sub_out_timeout_message'] = array(
        '#type' => 'textfield',
        '#default_value' => $form_detail['sub_out_timeout_message'],
        '#attributes' => array(
          'placeholder' => $this->t('Session timed out.')
        ),
      );
      $RemoveParams = array(
        'name' => 'sub_out_form_ids',
        'form_id' => $form_id,
        'action' => 'countdown'
      );
      $RemoveUrl = Url::fromRoute('form_submission_timeout.remove', $RemoveParams);
      $RemoveLink = \Drupal::l($this->t('Remove'), $RemoveUrl);
      $form['sub_out_timeout_fieldset'][$form_id]['sub_out_remove'] = array(
        '#markup' => '<div>' . $RemoveLink . '</div>'
      );
    }

    // Input new values.
    $form['sub_out_timeout_fieldset']['new_form_entry']['sub_out_new_show_timer'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show'),
    );
    $form['sub_out_timeout_fieldset']['new_form_entry']['sub_out_new_form_id'] = array(
      '#type' => 'textfield',
      '#size' => 35,
      '#attributes' => array(
        'placeholder' => $this->t('$form_id'),
      ),
    );
    $form['sub_out_timeout_fieldset']['new_form_entry']['sub_out_new_timeout_period'] = array(
      '#type' => 'textfield',
      '#size' => 15,
      '#attributes' => array(
        'placeholder' => $this->t('300'),
        'class' => array('countdown-time'),
      ),
    );
    $form['sub_out_timeout_fieldset']['new_form_entry']['sub_out_new_timeout_message'] = array(
      '#type' => 'textfield',
      '#attributes' => array(
        'placeholder' => $this->t('Session timed out.')
      ),
    );

    $form['#attached']['library'][] = 'form_submission_timeout/base_file';

    $form['sub_out_submit'] = array(
      '#type' => 'submit',
      '#value' => 'Save configuration',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('sub_out_timeout_fieldset');
    $new_values = $values['new_form_entry'];
    $form_ids = \Drupal::config('form_submission_timeout.settings')->get('sub_out_form_ids');
    $new_form_id = $new_values['sub_out_new_form_id'];
    unset($values['new_form_entry']);

    // Validate existing form_id timer period.
    foreach ($values as $form_id => $info) {
      if (empty($info['sub_out_timeout_period'])) {
        $form_state->setErrorByName('sub_out_timeout_fieldset][' . $form_id . '][sub_out_timeout_period', $this->t('Timeout period cannot be blank'));
      }
      if (empty($info['sub_out_timeout_message'])) {
        $form_state->setErrorByName('sub_out_timeout_fieldset][' . $form_id . '][sub_out_timeout_message', $this->t('Timeout message cannot be blank'));
      }
      if (!preg_match('/^[0-9]*$/', $info['sub_out_timeout_period'])) {
        $form_state->setErrorByName('sub_out_timeout_fieldset][' . $form_id . '][sub_out_timeout_period', $this->t('Timeout value can only be numeric'));
      }
    }

    // Check for duplicate values and blank spaces for new form IDs.
    if (!empty($new_values['sub_out_new_form_id'])) {
      if (array_key_exists($new_form_id, $form_ids)) {
        $form_state->setErrorByName('sub_out_timeout_fieldset][new_form_entry][sub_out_new_form_id', $this->t('Only one rule per form is allowed.'));
      }
      else {
        if (strlen($new_form_id) != 0 && strpos($new_form_id, ' ')) {
          $form_state->setErrorByName('sub_out_timeout_fieldset][new_form_entry][sub_out_new_form_id', $this->t('Form ID cannot contain blank spaces'));
        }
      }

      if (!preg_match('/^[0-9]*$/', $new_values['sub_out_new_timeout_period'])) {
        $form_state->setErrorByName('sub_out_timeout_fieldset][new_form_entry][sub_out_new_timeout_period', $this->t('Timeout value can only be numeric'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('sub_out_timeout_fieldset');
    $new_values = $values['new_form_entry'];

    // Update existing form_id values.
    if (count($values) > 1) {
      unset($values['new_form_entry']);
      FstController::updateFormIds('sub_out_form_ids', $values, 'update');
    }

    if (!empty($new_values['sub_out_new_form_id'])) {
      $new_form_id[$new_values['sub_out_new_form_id']] = array(
        'sub_out_form_id' => $new_values['sub_out_new_form_id'],
        'sub_out_show_timer' => $new_values['sub_out_new_show_timer'],
        'sub_out_timeout_period' => empty($new_values['sub_out_new_timeout_period']) ? '300' : $new_values['sub_out_new_timeout_period'],
        'sub_out_timeout_message' => empty($new_values['sub_out_new_timeout_message']) ? 'Session timed out.' : $new_values['sub_out_new_timeout_message'],
      );

      // Save and update the settings.
      FstController::updateFormIds('sub_out_form_ids', $new_form_id, 'add');
    }
    drupal_set_message($this->t('The settings have been saved'), 'status');
    return $form;
  }
}
