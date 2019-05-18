<?php

/**
 * @file
 * Contains \Drupal\hide_preview\Form\HidePreviewConfigForm.
 */

namespace Drupal\hide_preview\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class HidePreviewConfigForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'hide_preview_config_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);
        $config = $this->config('hide_preview.settings');

        $formNames = $config->get('hide_preview.form_names');
        $formNames = join(PHP_EOL, $formNames);

        $form['form_names'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Form names'),
            '#default_value' => $formNames,
            '#description' => "<ul><li>Write only one form name per line</li>
                <li>Do not use comma as a separator</li>
                <li>Use either a form name as a string or a regular expression.<ul>
                <li>Check if the <i>form_id</i> begins with the pattern <i>contact_message_</i></li>
                <li>Check if the <i>form_id</i> matches the regexp <i>/contact_message_*/</i></li>
                </ul></ul>",
            '#required' => false,
        );
        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $formNames = $form_state->getValue('form_names');
        $formNames = $this->multiline2Array($formNames);

        foreach ($formNames as &$name) {
            $name = trim($name);
            preg_match('/[^\w]+/', $name, $matches);
            if (count($matches)) {
                if (@preg_match($name, null) === false) {
                    $form_state->setErrorByName('form_names', t('Form name "%name" contains non wordy characters and is not a regexp.', array('%name' => $name)));
                }
            }
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $formNames = $form_state->getValue('form_names');
        $formNames = $this->multiline2Array($formNames);

        $config = $this->config('hide_preview.settings');
        $config->set('hide_preview.form_names', $formNames);

        $config->save();

        return parent::submitForm($form, $form_state);
    }

    /**
     * Get a string from a textarea and set every new line in an array
     * @param $multiline
     * @return array
     */
    protected function multiline2Array($multiline)
    {
        $array = preg_split("/\r\n/", $multiline);
        $array = array_filter($array);

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'hide_preview.settings',
        ];
    }
}