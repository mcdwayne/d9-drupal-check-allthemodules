<?php



/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorEventSubscriber.
 */

namespace Drupal\generator_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\generator_ui\Controller\GeneratorController;

class  GeneratorEventSubscriber extends Generator
{

    /*
    **
    * Returns a unique string identifying the form.
    *
    * @return string
    *   The unique string identifying the form.
    */
    public function getFormId()
    {
        return 'generator_event_subscriber';
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
            '#markup' => $this->t('<h2>' . 'Please fill the blanks to create your Event Subscriber' . '</h2>'),
            "#weight" => -2
        );

        $form['service_name'] = array(
            '#type' => 'textfield',
            '#title' => 'Service name',
            '#required' => true,
            '#description' => $this->t('Example: module.default'),
        );
        $form['class_name'] = array(
            '#type' => 'textfield',
            '#title' => 'Class Name',
            '#required' => true,
        );
        // Add Field To load services from the container
        $form['events_name'] = array(
            '#type' => 'select',
            '#options' => $this->getEventsList(),
            '#title' => t('Event Name'),
            '#description' => $this->t('Choice the event name')
        );
        $form['function'] = array(
            '#type' => 'textfield',
            '#required' => true,
            '#title' => t('Callback function name'),
            '#description' => $this->t('Callback function name to handle event')
        );



        $form['twig_file'] = array(
            "#type" => 'hidden',
            "#value" => array(
                'service_name' => 'service_name.services.yml.twig',
                'class_name' => 'eventsubscriber.php.twig',
            ),
        );

        // Add Field To load services from the container
        $form['servicesForm'] = array(
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'generator.services_autocomplete',
            '#title' => t('Dependency injection'),
            '#description' => $this->t('Do you want to load services from the container?')
        );

        if (($form_state->getValues())):
            $form['containers'] = array(
                "#type" => 'hidden',
                "#value" => $this->getContainer($form_state->getValue('servicesForm'))
            );
        endif;
        $form = parent::buildForm($form, $form_state);
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {


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
    public function submitForm(array &$form, FormStateInterface $form_state) {
        return parent::submitForm($form, $form_state);
    }
}