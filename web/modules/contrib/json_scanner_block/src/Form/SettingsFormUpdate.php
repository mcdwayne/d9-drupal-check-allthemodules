<?php

namespace Drupal\json_scanner_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\json_scanner_block\Controller\SettingsListController;
use Drupal\json_scanner_block\DbStorage\DbActions;
use Drupal\Component\Utility\UrlHelper;

/**
 * UI to update a record.
 */
class SettingsFormUpdate extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'json_scanner_block_admin_settings_update';
    }

    /**
     * UI to update a record.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        // Wrap the form in a div.
        $form = [
            '#prefix' => '<div id="updateJsonform">',
            '#suffix' => '</div>',
        ];
        // Add some explanatory text to the form.
        $form['message'] = [
            '#markup' => $this->t('Update operation for Json Scanner Block.'),
        ];
        // Query for items to display.
        $entries = DbActions::load('json_scanner_block');
        // Tell the user if there is nothing to display.
        if (empty($entries)) {
            $form['no_values'] = [
                '#value' => $this->t('No entries exist.'),
            ];
            return $form;
        }

        $keyed_entries = [];
        foreach ($entries as $entry) {
            $options[$entry->id] = $this->t('@name', [
                '@name' => $entry->name,
            ]);
            $keyed_entries[$entry->id] = $entry;
        }

        // Grab the id.
        $id = $form_state->getValue('id');
        // Use the id to set the default entry for updating.
        $default_entry = !empty($id) ? $keyed_entries[$id] : $entries[0];

        // Save the entries into the $form_state. We do this so the AJAX callback
        // doesn't need to repeat the query.
        $form_state->setValue('entries', $keyed_entries);

        $form['id'] = [
            '#type' => 'select',
            '#options' => $options,
            '#title' => $this->t('Choose data to update'),
            '#default_value' => $default_entry->id,
            '#ajax' => [
                'wrapper' => 'updateJsonform',
                'callback' => [$this, 'updateCallback'],
            ],
        ];

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Updated Title'),
            '#size' => 15,
            '#default_value' => $default_entry->name,
        ];

        $form['json_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Updated JSON data location url'),
            '#size' => 80,
            '#default_value' => $default_entry->json_url,
        ];

        $form['avail_for_twig'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Make it Available for Twig.'),
            '#size' => 1,
            '#description' => $this->t('Check it if you want to use available variables in twig template.'),
            '#default_value' => $default_entry->avail_for_twig,
        );

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Update'),
        ];
        return $form;
    }

    /**
     * AJAX callback handler for the id select.
     *
     * When the id changes, populates the defaults from the database in the form.
     */
    public function updateCallback(array $form, FormStateInterface $form_state) {
        // Gather the DB results from $form_state.
        $entries = $form_state->getValue('entries');
        // Use the specific entry for this $form_state.
        $entry = $entries[$form_state->getValue('id')];
        // Setting the #value of items is the only way I was able to figure out
        // to get replaced defaults on these items. #default_value will not do it
        // and shouldn't.
        foreach (['name', 'json_url', 'avail_for_twig'] as $item) {
            $form[$item]['#value'] = $entry->$item;
        }
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
       $values = $form_state->getValues();

        // Validate URL.
        if (!empty($values['json_url']) && !UrlHelper::isValid($values['json_url'], TRUE)) {
            $form_state->setErrorByName('json_url', $this->t('Invalid json url.'));
        }

        if (!empty($values['auth_url']) && !UrlHelper::isValid($values['auth_url'], TRUE)) {
            $form_state->setErrorByName('auth_url', $this->t('Invalid auth url.'));
        }        
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Gather the current user so the new record has ownership.
        $account = $this->currentUser();
        // Save the submitted entry.
        $entry = [
            'id' => $form_state->getValue('id'),
            'name' => $form_state->getValue('name'),
            'json_url' => $form_state->getValue('json_url'),
            'avail_for_twig' => $form_state->getValue('avail_for_twig'),
        ];
        $count = DbActions::update($entry);
        $this->messenger()->addMessage($this->t('Updated entry @entry (@count row updated)', [
                    '@count' => $count,
                    '@entry' => print_r($entry, TRUE),
        ]));
    }

}
