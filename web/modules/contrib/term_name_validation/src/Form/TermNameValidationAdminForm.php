<?php

namespace Drupal\term_name_validation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class TermNameValidationAdminForm.
 *
 * @package Drupal\term_name_validation\Form
 */
class TermNameValidationAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'term_name_validation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'term_name_validation_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get configuration value.
    $config = $this->config('term_name_validation.settings');

    // Get all vocabularies.
    $vocabularies = Vocabulary::loadMultiple();

    // Variable to display 1st fieldset collapse open.
    $i = 0;
    foreach ($vocabularies as $key => $vocabulary) {
      // Display First fieldset collapsed open.
      if ($i == 0) {
        $form[$key] = [
          '#type' => 'details',
          '#title' => $vocabulary->label(),
          '#collapsible' => TRUE,
          '#open' => TRUE,
        ];
      }
      else {
        $form[$key] = [
          '#type' => 'details',
          '#title' => $vocabulary->label(),
          '#collapsible' => TRUE,
          '#open' => FALSE,
        ];
      }
      // Increment $i for other fieldsets in collapsed closed.
      $i++;

      $form[$key]['exclude-' . $key] = [
        '#type' => 'textarea',
        '#title' => $this->t('Blacklist Characters/Words'),
        '#description' => '<p>' . $this->t("Comma separated characters or words to avoided while saving term names. Ex: !,@,#,$,%,^,&,*,(,),1,2,3,4,5,6,7,8,9,0,have,has,were,aren't.") . '</p>' . '<p>' . $this->t('If any of the blacklisted characters/words found in term name,would return validation error on term save.') . '</p>',
        '#default_value' => $config->get('exclude-' . $key) ? $config->get('exclude-' . $key) : '',
      ];

      $form[$key]['min-' . $key] = [
        '#type' => 'number',
        '#title' => $this->t("Minimum length"),
        '#description' => $this->t("Minimum number of characters term name should contain"),
        '#size' => 12,
        '#min' => 1,
        '#default_value' => $config->get('min-' . $key) ? $config->get('min-' . $key) : 1,
      ];

      $form[$key]['max-' . $key] = [
        '#type' => 'number',
        '#title' => $this->t("Maximum length"),
        '#description' => $this->t("Maximum number of characters term name should contain"),
        '#size' => 12,
        '#min' => 1,
        '#default_value' => $config->get('max-' . $key) ? $config->get('max-' . $key) : 255,
      ];

      $form[$key]['min-wc-' . $key] = [
        '#type' => 'number',
        '#title' => $this->t("Minimum Word Count"),
        '#description' => $this->t("Minimum number of words Term name should contain"),
        '#size' => 12,
        '#min' => 1,
        '#default_value' => $config->get('min-wc-' . $key) ? $config->get('min-wc-' . $key) : 1,
      ];

      $form[$key]['max-wc-' . $key] = [
        '#type' => 'number',
        '#title' => $this->t("Maximum Word Count"),
        '#description' => $this->t("Maximum number of words Term name should contain"),
        '#size' => 12,
        '#min' => 1,
        '#default_value' => $config->get('max-wc-' . $key) ? $config->get('max-wc-' . $key) : 25,
      ];

      $form[$key]['unique-' . $key] = [
        '#type' => 'checkbox',
        '#title' => $this->t("Unique term name for @type vocabulary", ['@type' => $key]),
        '#default_value' => $config->get('unique-' . $key) ? $config->get('unique-' . $key) : 0,
      ];
    }

    $form['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique term name for all vocabularies'),
      '#default_value' => $config->get('unique') ? $config->get('unique') : 0,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get all vocabularies.
    $vocabularies = Vocabulary::loadMultiple();

    // Loop for each content type & validate min, max values.
    foreach ($vocabularies as $type) {
      $max = $form_state->getValue(['max-' . $type->getOriginalId()]);
      $min = $form_state->getValue(['min-' . $type->getOriginalId()]);
      $min_wc = $form_state->getValue(['min-wc-' . $type->getOriginalId()]);
      $max_wc = $form_state->getValue(['max-wc-' . $type->getOriginalId()]);

      // Validate min is less than max value.
      if (!empty($min) && !empty($max) && $min > $max) {
        $form_state->setErrorByName('min-' . $type->getOriginalId(), $this->t("Minimum length should not be more than Maximum length"));
      }

      // Validate min is less than max value.
      if (!empty($min_wc) && !empty($max_wc) && $min_wc > $max_wc) {
        $form_state->setErrorByName('max-wc-' . $type->getOriginalId(), $this->t("Minimum word count of Term Name should not be more than Maximum word count"));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get all vocabularies.
    $vocabularies = Vocabulary::loadMultiple();

    $data = $this->config('term_name_validation.settings');
    // Store Form values in term_name_validation_config variable.
    foreach ($vocabularies as $type) {
      $data->set('exclude-' . $type->getOriginalId(), $form_state->getValue('exclude-' . $type->getOriginalId()));
      $data->set('min-' . $type->getOriginalId(), $form_state->getValue('min-' . $type->getOriginalId()));
      $data->set('max-' . $type->getOriginalId(), $form_state->getValue('max-' . $type->getOriginalId()));
      $data->set('min-wc-' . $type->getOriginalId(), $form_state->getValue('min-wc-' . $type->getOriginalId()));
      $data->set('max-wc-' . $type->getOriginalId(), $form_state->getValue('max-wc-' . $type->getOriginalId()));
      $data->set('unique-' . $type->getOriginalId(), $form_state->getValue('unique-' . $type->getOriginalId()));
    }
    $data->set('unique', $form_state->getValue('unique'));
    $data->save();

    drupal_set_message($this->t('Configurations saved successfully!'));
  }

}
