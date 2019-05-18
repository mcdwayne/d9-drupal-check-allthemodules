<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorInfo .
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary libraries
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Url;
use Drupal\generator_ui\Controller\GeneratorController;


class GeneratorInfo extends Generator
{

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'info';

    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['into'] = array(
            '#markup' => t('<h2>' . 'Please fill the blanks to create your module in D8' . '</h2>'),
            "#weight" => -2
        );

        $form['transformation_path'] = array(
            '#type' => 'hidden',
            '#value' => true,
        );
        $form['module_'] = array(
            '#type' => 'textfield',
            '#default_value' => 'Module',
            '#title' => t('Module name'),
            '#description' => t('Human readable description of the modules printed on the extend page.<br/>
        Machine name is the machine name of the module used in coding. Ex. : my_module.'),
            '#required' => TRUE,
        );
        $form['core'] = array(
            '#type' => 'textfield',
            '#title' => 'Core',
            '#default_value' => '8.x',
            '#disabled' => TRUE,
        );
        // Change textfield to textarea
        $form['description'] = array(
            '#type' => 'textarea',
            '#default_value' => 'description',
            '#title' => $this->t('Module description printed one the extend page.')
        );
        $form['package'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Package'),
            '#default_value' => 'package',
            '#description' => t('Module package in the modules pages to group modules together.'),
        );
        // @TODO : remove and create something better.
        $form['links'] = array(
            '#theme' => 'links',
            '#links' => array(
                'link6' => array(
                    'title' => t('Add Config page'),
                    'url' => \Drupal\Core\Url::fromRoute('generator.routing'),
                    'attributes' => array(
                        'class' => array('use-ajax'),
                        'data-dialog-type' => 'modal',
                        'data-dialog-options' => json_encode(array(
                            'width' => 800,
                            'height' => 600,
                        ))
                    ),
                ),
            ),
        );
        $form['config'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Configuration page'),
            '#description' => t('Link to config page from the extend page. Enter the route, not the path. Ex. : your_module.config.'),
        );

        $form['version'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Version'),
            '#default_value' => '1.0',
            '#description' => t('Version of the module. Ex: alpha-1, beta-2, 2.0,etc..'),
        );

        $form['dependencies'] = array(
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'generator.autocomplete',
            '#title' => $this->t('Dependencies'),
            '#default_value' => 'block,node',
            '#description' => t('Modules on which your module depends on. Module\'s Machine name Required.<br />If you have multiple dependencies, separate them with a comma. Ex: block,node'),
        );


        $form['twig_file'] = array(
            "#type" => 'hidden',
            "#value" => array(
                'generator.info.yml.twig',
            ),
        );
        $form = parent::buildForm($form, $form_state);
        $form['module_name']['#type'] = 'machine_name';
        unset($form['module_name']['#element_validate']);
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        return parent::validateForm($form, $form_state);
    }

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        return parent::submitForm($form, $form_state);
    }
}