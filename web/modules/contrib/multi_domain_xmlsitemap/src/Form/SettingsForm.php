<?php

namespace Drupal\multi_domain_xmlsitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures multi_domain_xmlsitemap settings.
 */
class SettingsForm extends ConfigFormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'multi_domain_xmlsitemap_admin_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'multi_domain_xmlsitemap.settings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // Get current settings.
        $domain_list = \Drupal::service('entity_type.manager')->getStorage('domain')->loadByProperties();
        $i = 1;
        $form['#attached']['library'][] = 'multi_domain_xmlsitemap/multi-domain-xmlsitemap';

        $form['checkall'] = array(
            '#type' => 'checkbox',
            '#title' => t('Select / Unselect all'),
            '#attributes' => array('onclick' => 'checkUncheckAll(this);'),
            '#weight' => -1,
        );

        foreach ($domain_list as $domain_machine_name) {
            //$domain_path_array[] = $domain_machine_name->getPath();
            $form[$domain_machine_name->id()] = [
                '#type' => 'checkbox',
                '#title' => $this->t($domain_machine_name->get('name')),
                '#required' => FALSE,
                '#options' => $domain_machine_name->getPath(),
            ];
            $i++;
        }
        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        //$values = $form_state->getValues();
        //Check Validation Here
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $domain_list = \Drupal::service('entity_type.manager')->getStorage('domain')->loadByProperties();

        $values = $form_state->getValues();
        foreach ($values as $key => $value) {
            if ($value == 1 && $key != 'checkall' && null !== $domain_list[$key]) {
                $context = array('domain' => rtrim($domain_list[$key]->getPath(), '/'));
                $sitemap_hash = xmlsitemap_sitemap_get_context_hash($context);
                $ids = \Drupal::entityQuery('xmlsitemap')
                        ->condition('id', $sitemap_hash)
                        ->range(0, 1)
                        ->execute();

                //entity_delete_multiple('xmlsitemap', $ids);

                if (empty($ids)){
                    $sitemap = entity_create('xmlsitemap', [
                        'id' => $sitemap_hash,
                        'label' => $key,
                        'context' => $context,
                    ]);
                    $sitemap->save();
                }
            }
        }
         $form_state->setRedirect('xmlsitemap.admin_search');
        //return $sitemap;
    }

}
