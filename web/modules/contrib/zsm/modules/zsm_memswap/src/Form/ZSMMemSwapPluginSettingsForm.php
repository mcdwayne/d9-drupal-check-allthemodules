<?php
/**
 * @file
 * Contains \Drupal\zsm_memswap\Form\ZSMMemSwapPluginSettingsForm.
 */

namespace Drupal\zsm_memswap\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ZSMMemSwapPluginSettingsForm.
 *
 * @package Drupal\zsm_memswap\Form
 *
 * @ingroup zsm_memswap
 */
class ZSMMemSwapPluginSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'zsm_memswap_settings';
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
        $form['zsm_memswap_settings']['#markup'] = 'Settings form for ZSM MemSwapPlugin Settings. Manage field settings here.';
        return $form;
    }

}
