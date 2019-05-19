<?php
/**
 * @file
 * Contains \Drupal\zsm\Form\ZSMSettingsForm.
 */

namespace Drupal\zsm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContentEntityExampleSettingsForm.
 *
 * @package Drupal\zsm\Form
 *
 * @ingroup zsm
 */
class ZSMSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_settings';
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
        $form['zsm_core_settings']['#markup'] = 'Settings form for the ZSM Module. Manage field settings here.';
        return $form;
    }

}
