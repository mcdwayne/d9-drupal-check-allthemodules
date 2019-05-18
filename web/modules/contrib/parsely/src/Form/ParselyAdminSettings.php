<?php

/**
 * @file
 * Contains \Drupal\parsely\Form\ParselyAdminSettings.
 */

namespace Drupal\parsely\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;

class ParselyAdminSettings extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'parsely_admin_settings';
    }

    /**
     * Vocab format
     * @function - formats vocabularies
     * @param Vocabulary[] $vocabularies
     * @return Vocabulary[]
     *
     */
    public static function _parsely_vocab_array_format($vocabularies = NULL) {
        $vocab_array = array();
        foreach ($vocabularies as $vocab) {
            $vocab_array[$vocab->get('vid')] = t($vocab->get('name'));
        }
        return $vocab_array;
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('parsely.settings');

        foreach (Element::children($form) as $variable) {
            $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
        }
        $config->save();

        if (method_exists($this, '_submitForm')) {
            $this->_submitForm($form, $form_state);
        }

        parent::submitForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['parsely.settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['parsely_apikey'] = [
            '#type' => 'textfield',
            '#title' => t('Parse.ly Site ID'),
            '#description' => t('You can find your Site ID on your your <a target="_blank" href="@parsely_settings">API settings page</a>.', [
                '@parsely_settings' => 'http://dash.parsely.com/to/settings/api?highlight=apikey'
            ]),
            '#default_value' => \Drupal::config('parsely.settings')->get('parsely_apikey'),
        ];



        foreach (NodeType::loadMultiple() as $type) {
            $node_types[$type->id()] = $type->label();
        }

        $form['parsely_section_logic'] = [
            '#type' => 'radios',
            '#title' => t('Use content (node) types or vocabulary (taxonomy) as sections?'),
            '#options' => [
                'node',
                'taxonomy',
            ],
            '#default_value' => \Drupal::config('parsely.settings')->get('parsely_section_logic'),
        ];


        if (\Drupal::moduleHandler()->moduleExists('node')) {

            $form['parsely_nodes_wrap'] = [
                '#type' => 'fieldset',
                '#title' => t('Node types to track'),
                '#description' => t('<em>Select which node types to track. (Note: If you selected use node types above, these will be your available sections.)</em>'),
                //'#collapsible' => TRUE,
                //'#collapsed' => FALSE,
                '#tree' => TRUE,
            ];

            $form['parsely_nodes_wrap']['parsely_nodes'] = [
                '#type' => 'checkboxes',
                '#title' => t('Available node types:'),
                '#options' => $node_types,
                '#default_value' => \Drupal::config('parsely.settings')->get('parsely_nodes_wrap')['parsely_nodes'],
            ];

        }

        if (\Drupal::moduleHandler()->moduleExists('taxonomy')) {

            $form['parsely_sections_tax_wrapper'] = [
                '#type' => 'fieldset',
                '#title' => t('Use Taxonomy Term as Section Settings'),
                '#collapsible' => TRUE,
                '#collapsed' => FALSE,
                '#tree' => TRUE
            ];

            $form['parsely_tag_vocabularies'] = [
                '#type' => 'checkboxes',
                '#title' => t('Tag Vocabularies'),
                '#options' => $this->_parsely_vocab_array_format(Vocabulary::loadMultiple()),
                '#description' => t('<em>Select which taxonomy vocabularies will be tracked as tags.</em>'),
                '#default_value' => \Drupal::config('parsely.settings')->get('parsely_tag_vocabularies'),
            ];

            $form['parsely_sections_tax_wrapper']['parsely_section_vocabulary'] = [
                '#type' => 'radios',
                '#title' => t('Section Vocabulary'),
                '#options' => $this->_parsely_vocab_array_format(Vocabulary::loadMultiple()),
                '#description' => t('Select the taxonomy vocabulary to use for Parse.ly sections. A single term from this vocabulary will be chosen for each tracked node, where applicable, using the criterion specified below.'),
                '#default_value' => \Drupal::config('parsely.settings')->get('parsely_sections_tax_wrapper')['parsely_section_vocabulary']
            ];
            $form['parsely_sections_tax_wrapper']['parsely_section_term_criterion'] = [
                '#title' => t('Section Term Selection Criteria'),
                '#type' => 'radios',
                '#options' => [
                    'first' => t('First term'),
                    'last' => t('Last term'),
                    'highest' =>t('Highest level ancestor')
                ],
                // Provide a long-winded explanation of how this impacts term selection.
                // Note that there's currently no way to say "find the last/newest term,
                // and then select its highest-level ancestor" or "use the term that
                // appears first in the set of terms for a given term/entity reference
                // field," etc.
                '#description' => t('If a given node may be associated with multiple terms from the vocabulary you selected above, this setting can help determine which term to use. "First term" and "Last term" will choose a term based on the date/time the term was created (this may differ from the relative position of a term within a term reference field).'),
                '#default_value' => \Drupal::config('parsely.settings')->get('parsely_sections_tax_wrapper')['parsely_section_term_criterion']
            ];


        }


        if (\Drupal::moduleHandler()->moduleExists('token')) {
            $form['parsely_metadata']['#description'] .= ' ' . t('You can use tokens from the list below to specify dynamic patterns for each metadata item.');
            // Set up a token browser so admins can use relevant tokens to define
            // metadata values. Update all textfields in this fieldset accordingly.
            // Note: The ability to provide token_types param to theme_token_tree_link
            // was introduced by the token module on 2014-06-19 in v. 7.x-1.5+4-dev.
            // @see https://www.drupal.org/node/2289203.
            $token_module_info = system_get_info('module', 'token');
            $token_version = $token_module_info['version'];
            if (version_compare($token_version, '7.x-1.5+4-dev', 'ge')) {
                $theme_function = 'token_tree_link';
            }
            else {
                $theme_function = 'token_tree';
            }
            $form['parsely_metadata']['tokens'] = [
                '#theme' => $theme_function,
                '#token_types' => [
                    'node'
                ],
                '#global_types' => TRUE,
                '#click_insert' => TRUE,
            ];

            foreach (Element::children($form['parsely_metadata']) as $field_key) {
                $field = $form['parsely_metadata'][$field_key];
                if ($field['#type'] == 'textfield') {
                    // Note: because we're not requiring a minimum number of tokens for
                    // these fields, the only way they can fail validation is if they
                    // include a real token that belongs to a disallowed context.
                    $field['#element_validate'] = [
                        'token_element_validate'
                    ];
                    $field['#token_types'] = ['node'];
                    $form['parsely_metadata'][$field_key] = $field;
                }
            }
        }

        // Advanced settings
        $form['parsely_optional_settings'] = [
            '#type' => 'fieldset',
            '#title' => t('Advanced Settings'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
            '#tree' => TRUE
        ];
        $form['parsely_optional_settings']['parsely_track_auth_users'] = [
            '#type' => 'radios',
            '#title' => t('Track Authenticated Users'),
            '#description' => t('By default, the Parse.ly module will track the activity of users that are logged into this site. You can change this setting to only track the activity of anonymous visitors. Note: You will no longer see the Parse.ly tracking code on your site if you browse while logged in.'),
            '#options' => [
                1 => t('Yes'),
                0 => t('No'),
            ],
            '#default_value' => \Drupal::config('parsely.settings')->get('parsely_optional_settings')['parsely_track_auth_users'],
        ];
        $form['parsely_optional_settings']['parsely_content_id_prefix'] = [
            '#type' => 'textfield',
            '#title' => t('Content ID Prefix'),
            '#description' => t('If you use more than one content management system (e.g. Drupal and WordPress), you may end up with duplicate content IDs. Adding a Content ID Prefix will ensure the content IDs from Drupal will not conflict with other content management systems.'),
            '#default_value' => \Drupal::config('parsely.settings')->get('parsely_optional_settings')['parsely_content_id_prefix'],
        ];
        $form['parsely_optional_settings']['parsely_metadata_thumbnail_url'] = [
            '#type' => 'textfield',
            '#title' => t('Override default thumbnail?'),
            '#description' => t('Enter the machine name of an image field.'),
            '#default_value' => \Drupal::config('parsely.settings')->get('parsely_optional_settings')['parsely_metadata_thumbnail_url'],
            '#maxlength' => 1024,
        ];
        $form['parsely_debug_settings'] = [
            '#type' => 'fieldset',
            '#title' => t('Debug'),
            '#collapsible' => TRUE,
            '#collapsed' => TRUE,
        ];
        $form['parsely_debug_settings']['parsely_debug'] = [
            '#type' => 'checkbox',
            '#title' => t('Debugging on?'),
            '#description' => t('Toggle this on to dump your Parsely data on every tracked page. <em>NB. You must have the Administer Parsely permission to view this data.</em>'),
            '#default_value' => \Drupal::config('parsely.settings')->get('parsely_debug'),
        ];
        return parent::buildForm($form, $form_state);
    }

}
?>
