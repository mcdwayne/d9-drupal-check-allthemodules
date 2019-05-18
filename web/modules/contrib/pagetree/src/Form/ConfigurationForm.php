<?php
/**
 * @file
 * Contains Drupal\pagetree\Form\ConfigurationForm
 *
 */
namespace Drupal\pagetree\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Class ConfigurationForm
 *
 * @package Drupal\pagetree\Form
 */
class ConfigurationForm extends ConfigFormBase
{

    /**
     * The default page tree types.
     *
     * @var array
     */
    protected $_defaultTypes = array('iqbm_page', 'iqbm_row', 'iqbm_component', 'iqbm_content');

    /**
     * {@inheritDoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'pagetree.settings',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getFormId()
    {
        return 'pagetree';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $values = $this->config($this->getEditableConfigNames()[0]);

        $form['general'] = array(
            '#type' => 'details',
            '#open' => true,
            '#title' => t('General settings'),
            '#description' => t('Set the general settings for the page tree modules.'),
        );

        $menus = Menu::loadMultiple();
        $menusList = [];
        foreach ($menus as $menu) {
            $menusList[$menu->id()] = $menu->label();
        }
        asort($menusList);

        $form['general']['menus'] = array(
            '#type' => 'checkboxes',
            '#title' => t('Menus'),
            '#description' => t('Select the menus to be displayed in the page tree.'),
            '#options' => $menusList,
            '#default_value' => $values->get('menus'),
        );

        $languages = \Drupal::service('language_manager')->getLanguages();
        $languagesList = [];
        foreach ($languages as $language) {
            $languagesList[$language->getId()] = $language->getName();
        }
        asort($languagesList);

        $form['general']['languages'] = array(
            '#type' => 'checkboxes',
            '#title' => t('Languages'),
            '#description' => t('Select the languages to be displayed in the page tree.'),
            '#options' => $languagesList,
            '#default_value' => $values->get('languages'),
        );

        $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
        $contentTypesList = [];
        foreach ($contentTypes as $contentType) {
            $contentTypesList[$contentType->id()] = $contentType->label();
        }
        $form['general']['contenttypes'] = array(
            '#type' => 'checkboxes',
            '#title' => t('Content types'),
            '#description' => t('Select the content types you want to process with the page tree module.'),
            '#options' => $contentTypesList,
            '#default_value' => $values->get('contentTypes'),
        );

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritDoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        return parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);
        // Store set values
        \Drupal::configFactory()->getEditable(self::getEditableConfigNames()[0])
            ->set('menus', array_filter($form_state->getValue('menus')))
            ->set('languages', array_filter($form_state->getValue('languages')))
            ->set('contentTypes', array_filter($form_state->getValue('contenttypes')))
            ->save();
    }
}
