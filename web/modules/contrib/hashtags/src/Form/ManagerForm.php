<?php

namespace Drupal\hashtags\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Url;

class ManagerForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormID() {
        return 'hashtags_manager_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $hashtags_field_name = \Drupal::config('hashtags.settings')
                                ->get('hashtags_taxonomy_terms_field_name');
        $entity_types = _hashtags_get_content_entity_types();
        // make system content entity types to go first in the list
        array_unshift($entity_types, 'comment');
        array_unshift($entity_types, 'taxonomy_term');
        array_unshift($entity_types, 'user');
        array_unshift($entity_types, 'node');
        $entity_types = array_unique($entity_types);
        $form = array();
        $form['entity_types'] = array(
            '#tree' => true,
        );
        $form['info'] = array(
            '#markup' => $this->t('Activate hashtags for corresponding bundles. After activation the hashtags will be available for Body field by default. Also you can activate hashtags for another fields that have Text type.'),
        );
        foreach ($entity_types as $entity_type) {
            $entity_type_label = _hashtags_get_entity_type_label($entity_type);
            $table_name = 'table-'. $entity_type;
            $form[$table_name] = [
                '#prefix' => '<h2>' . $entity_type_label . '</h2>',
                '#type' => 'table',
                '#header' => [
                    $this->t('Name'),
                    $this->t('Operations'),
                ],
            ];
            $bundles = \Drupal::service('entity.manager')->getBundleInfo($entity_type);
            foreach ($bundles as $bundle => $bundle_info) {
                if (is_object($bundle_info['label'])) {
                    $label = $bundle_info['label']->render();
                } else if (is_string($bundle_info['label'])) {
                    $label = $bundle_info['label'];
                }
                if (!empty($label)) {
                    $form[$table_name][$bundle]['label'] = ['#markup' => $label];
                }
                if (!_hashtags_is_field_exists($entity_type, $bundle, $hashtags_field_name)) {
                    $form[$table_name][$bundle]["{$entity_type}__{$bundle}__add"] = [
                        '#type' => 'submit',
                        '#entity_type' => $entity_type,
                        '#bundle' => $bundle,
                        '#field_name' => $hashtags_field_name,
                        '#value' => 'Activate Hashtags',
                        '#submit' => ['::addField'],
                        '#name' => "{$entity_type}__{$bundle}__add",
                    ];
                } else {
                    $form[$table_name][$bundle]["{$entity_type}__{$bundle}__add"] = [
                        '#type' => 'submit',
                        '#entity_type' => $entity_type,
                        '#bundle' => $bundle,
                        '#field_name' => $hashtags_field_name,
                        '#value' => 'Remove Hashtags',
                        '#submit' => ['::removeField'],
                        '#name' => "{$entity_type}__{$bundle}__remove",
                    ];
                }
            }
        }
        $form['actions'] = array('#type' => 'actions', '#tree' => FALSE);
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(array &$form, FormStateInterface $form_state) {
        $hashtags_vid = \Drupal::config('hashtags.settings')
            ->get('hashtags_vid');
        $clicked_button = $form_state->getTriggeringElement();
        $entity_type = $clicked_button['#entity_type'];
        $bundle = $clicked_button['#bundle'];
        $field_name = $clicked_button['#field_name'];
        // create field storage or take existing one
        $field_storage = $this->isFieldStorageExists($entity_type, $field_name);
        if (!$field_storage) {
            $field_storage = $this->createFieldStorage($entity_type, $field_name);
        }
        $field = _hashtags_is_field_exists($entity_type, $bundle, $field_name);
        if (!$field) {
            $this->createField($field_storage, $bundle, 'Hashtags', [$hashtags_vid => $hashtags_vid]);
            $this->updateFieldFormDisplay($entity_type, $bundle, $field_name);
            $body_field = \Drupal::entityTypeManager()->getStorage('field_config')->load("{$entity_type}.{$bundle}.body");
            $entity_type_label = _hashtags_get_entity_type_label($entity_type);
            $bundle_label = _hashtags_get_bundle_label($entity_type, $bundle);
            $source = $entity_type !== $bundle ?
                ($entity_type_label . ' > ' . $bundle_label) :
                $entity_type_label;
            if (!empty($body_field)) {
                $body_field->setThirdPartySetting('hashtags', 'hashtags_activate', TRUE);
                $body_field->save();
                \Drupal::messenger()->addMessage("Hashtags have been activated for Body field. Also you can activate Hashtags for another Text fields of {$source}.");
            } else {
                \Drupal::messenger()->addMessage("Body field is not found for {$source}. Create some Text field and activate Hashtags manually for it.");
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeField(array &$form, FormStateInterface $form_state) {
        $clicked_button = $form_state->getTriggeringElement();
        $entity_type = $clicked_button['#entity_type'];
        $bundle = $clicked_button['#bundle'];
        $form_state->setRedirectUrl(new Url('hashtags.delete_form', [
            'entity_type' => $entity_type, 'bundle' => $bundle]));
    }

    /**
     * {@inheritdoc}
     */
    public function isFieldStorageExists($entity_type, $field_name) {
        $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
        if (empty($field_storage) ) {
            return FALSE;
        }
        return $field_storage;
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldStorage($entity_type, $field_name) {
        $field_storage = FieldStorageConfig::create(array(
            'field_name' => $field_name,
            'entity_type' => $entity_type,
            'type' => 'entity_reference',
            'settings' => array('target_type' => 'taxonomy_term'),
            'cardinality' => -1,
            'locked' => false,
            'translatable' => true,
        ));
        $field_storage->save();
        return $field_storage;
    }

    /**
     * {@inheritdoc}
     *
     * Create a hashtags taxonomy term field
     *
     * @param $field_storage
     * @param $bundle
     * @param $label
     * @param array $term_bundles
     * @return \Drupal\Core\Entity\EntityInterface|static
     */
    public function createField($field_storage, $bundle, $label, $term_bundles = ['hashtags' => 'hashtags']) {
        $field = FieldConfig::create([
            'field_storage' => $field_storage,
            'bundle' => $bundle,
            'label' => $label,
            'required' => false,
            'field_type' => 'entity_reference',
            'settings' => [
                'handler' => 'default:taxonomy_term',
                'handler_settings' => [
                    'target_bundles' => $term_bundles,
                    'sort' => [
                        'field' => 'name',
                        'direction' => 'asc',
                    ],
                    'auto_create' => true,
                    'auto_create_bundle' => '',
                ]
            ],
        ]);
        $field->save();
        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function updateFieldFormDisplay($entity_type, $bundle, $field_name) {
        $form_display = entity_get_form_display($entity_type, $bundle, 'default');
        $form_display->setComponent($field_name, [
            'type' => 'entity_reference_autocomplete_tags',
            'weight' => 10,
        ]);
        $form_display->save();
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

    }
}
