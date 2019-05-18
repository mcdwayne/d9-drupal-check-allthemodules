<?php

/**
 * @file
 * Contains Drupal\nys_unav\Form\NysUNavForm
 */

namespace Drupal\nys_unav\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

class NysUNavForm extends ConfigFormBase {

    /**
     * {@inheritdoc}.
     */
    public function getFormId() {
        return 'nys_unav_form';
    }

    /**
     * {@inheritdoc}.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Form constructor
        $form = parent::buildForm($form, $form_state);
        // Default settings
        $config = $this->config('nys_unav.settings');

        $form['nys_unav_auto'] = $this->_nys_unav_auto_field();
        $form['nys_unav_interactive'] = $this->_nys_unav_interactive_field();

        return $form;
    }

    /**
     * {@inheritdoc}.
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

    }

    /**
     * {@inheritdoc}.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = \Drupal::configFactory()->getEditable('nys_unav.settings');
        $config->set('nys_unav.nys_unav_auto', $form_state->getValue('nys_unav_auto'));
        $config->set('nys_unav.nys_unav_interactive', $form_state->getValue('nys_unav_interactive'));
        $config->save();
        return parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}.
     */
    protected function getEditableConfigNames()
    {
        return [
            'nys_unav.settings',
        ];
    }


    /**
     * NYS Universal Navigation footer automatic insertion field.
     *
     * @return array
     *   Form API element for field.
     */
    public function _nys_unav_auto_field() {
        $config = $this->config('nys_unav.settings');
        return array(
            '#type' => 'checkbox',
            '#title' => t('Universal navigation footer automatic insertion'),
            '#default_value' => $config->get('nys_unav.nys_unav_auto'),
            '#multiple' => FALSE,
            '#description' => t('Select if the universal navigation header and footer are to be automatically inserted into the page.  If not selected, make sure to use the NYS Universal Navigation blocks'),
        );
    }

    /**
     * NYS Universal Navigation interactive/static header selection.
     *
     * @return array
     *   Form API element for field.
     */
    public function _nys_unav_interactive_field() {
        $config = $this->config('nys_unav.settings');
        $header_options = array(0 => t('Static'), 1 => t('Interactive'));
        return array(
            '#type' => 'radios',
            '#title' => t('Universal navigation header format'),
            '#options' => $header_options,
            '#default_value' => $config->get('nys_unav.nys_unav_interactive'),
            '#description' => t('Select which header format to use, interactive or static.'),
        );
    }


}