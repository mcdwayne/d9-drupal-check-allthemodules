<?php
/**
 * @file
 * Contains \Drupal\entity_expiration\Form\SpectraExpirationPolicySettingsForm.
 */

namespace Drupal\entity_expiration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SpectraExpirationPolicySettingsForm.
 *
 * @package Drupal\entity_expiration\Form
 *
 * @ingroup entity_expiration
 */
class EntityExpirationPolicySettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'entity_expiration_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Empty implementation of the abstract submit class.
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['entity_expiration_settings']['#markup'] = 'Settings form for Spectra Expiration Policy Settings. Manage field settings here.';
        return $form;
    }

}
