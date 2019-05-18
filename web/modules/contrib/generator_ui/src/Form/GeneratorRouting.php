<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorRouting.
 *
 */

namespace Drupal\generator_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;

class GeneratorRouting extends Generator
{

    public static function validate_route($element, FormStateInterface $form_state)
    {
        $route = $element['#value'];
        $form_state->setValueForElement($element, $route);
        $module_name = $form_state->getValue('module_name');
        $routing_file = drupal_get_path('module', $module_name) . '/' . $module_name . '.routing.yml';
        //Convert yml file to an array
        $routing_array = Yaml::parse($routing_file);
        foreach ($routing_array as $route_item => $route_item) {
            if ($route_item == $route) {
                $form_state->setError($element, t('The route "%route" is already  exist.', array('%route' => $route)));
            }
        }

    }

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'routing';
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
            '#markup' => $this->t('<h2>' . 'Please fill the blanks to create your routing, Controllers and form files' . '</h2>'),
            "#weight" => -2
        );
        $form['type_routing'] = array(
            '#type' => 'select',
            '#title' => $this->t("Routing's type"),
            '#options' => array('controller' => 'Controller', 'form_' => 'Form'),
            '#description' => $this->t('Wether you will directly call a form or call a page.'),
            '#executes_submit_callback' => FALSE,
            '#ajax' => array(
                'callback' => '::choiceRouting',
                'wrapper' => 'ajax_choice',
            ),
        );
        $form['route'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Route name'),
            '#default_value' => 'key',
            '#element_validate' => array(array(get_class($this), 'validate_route')),
            '#required' => TRUE,
            '#description' => t('The route name <strong>must</strong> be unique,so the
        route name starts with the module name followed by an unique key. The
        machine name of the module will be handled automatically.'),
            '#field_prefix' => 'module_name.'
        );
        $form['pathh'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Path'),
            '#default_value' => $this->t('hello'),
            '#required' => TRUE,
            '#description' => $this->t('URL of the page relative to the domain name. Do not include trailing slash.'),
        );
        $form['argums'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Arguments in path'),
            '#states' => array(
                'visible' => array(
                    ':input[name="type_routing"]' => array('value' => 'controller'),
                ),
            ),
            '#description' => $this->t('Dynamic values will be sent. Arguments must be separated
        by / and putted in braces,Ex: {first_argument}/{second_argument}. Do not include trailing slash.'),
        );
        $form['title'] = array(
            '#type' => 'textfield',
            '#default_value' => 'Page title',
            '#title' => $this->t('Page title'),
        );
        // Ajax choice for form or normal page controller.
        $form['control_method'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('The controller & its method'),
            '#collapsible' => TRUE,
            '#prefix' => '<div id="ajax_choice">',
            '#suffix' => '</div>',
        );
        if ($form_state->getValue('type_routing') == "form_") {
            $form['control_form'] = array(
                '#type' => 'fieldset',
                '#title' => $this->t('The form'),
                '#collapsible' => TRUE,
                '#prefix' => '<div id="ajax_choice">',
                '#suffix' => '</div>',
            );
            $form['twig_file'] = array(
                "#type" => 'hidden',
                "#value" => array(
                    'form' => 'form.php.twig',
                    'generator.routing.yml.twig',
                ),
            );
            $form['control_form']['form'] = array(
                '#type' => 'textfield',
                '#default_value' => 'ExampleForm',
                '#title' => $this->t('Name of the form class'),
                '#description' => $this->t('Path of form class: module/src/Form'),
            );
            $form['control_form']['form_id_'] = array(
                '#type' => 'textfield',
                '#default_value' => 'form_id',
                '#title' => $this->t('Form id'),
            );

            // Add Field To load services from the container
            $form['control_form']['servicesForm'] = array(
                '#type' => 'textfield',
                '#autocomplete_route_name' => 'generator.services_autocomplete',
                '#title' => t('Dependency injection'),
                '#description' => $this->t('Do you want to load services from the container?')
            );
            if (($form_state->getValue('servicesForm'))) {

            $form['containers'] = array(
                "#type" => 'hidden',
                "#value" => $this->getContainer($form_state->getValue('servicesForm'))
            );
            }
        } else {
            $form['twig_file'] = array(
                "#type" => 'hidden',
                "#value" => array(
                    'controller' => 'controller.php.twig',
                    'generator.routing.yml.twig',
                ),
            );

            if (($form_state->getValue('servicesController'))):
                $form['containers'] = array(
                    "#type" => 'hidden',
                    "#value" => $this->getContainer($form_state->getValue('servicesController'))
                );
            endif;
            $form['control_method']['controller'] = array(
                '#type' => 'textfield',
                '#title' => $this->t('Name of the controller class'),
                '#default_value' => 'Example',
                '#description' => $this->t('Must ends with "Controller" word.'),
            );
            $form['control_method']['method'] = array(
                '#type' => 'textfield',
                '#default_value' => 'build',
                '#title' => $this->t('Method to call'),
                '#description' => $this->t('Name of the method that will be called in the controller.')
            );
            // Add Field To load services from the container
            $form['control_method']['servicesController'] = array(
                '#type' => 'textfield',
                '#autocomplete_route_name' => 'generator.services_autocomplete',
                '#title' => t('Dependency injection'),
                '#description' => $this->t('Do you want to load services from the container?')
            );

            $form['module_name'] = array(
                '#type' => 'textfield',
                '#autocomplete_route_name' => 'generator.autocomplete',
                '#title' => t('Module machine name'),
                '#required' => TRUE,
                '#element_validate' => array(array(get_class($this), 'validate_module')),
                "#weight" => -1,
                '#machine_name' => array(
                    'source' => array('module_'),
                ),
            );
            $types_back = array('html' => 'html', 'json' => 'json');
            $form['control_method']['method_return_type'] = array(
                '#type' => 'select',
                '#options' => $types_back,
                '#title' => $this->t('Return type'),
                '#description' => $this->t('The return type of the method declared in the controller')
            );
        }

        $form['csrf_token'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('CSRF token'),
            '#description' => $this->t('The generated token is based on the session ID of the current user. Normally, anonymous users do not have a session, so the generated token will be different on every page request. To generate a token for users without a session, manually start a session prior to calling this function.Optional, false by default.'),
        );
        $form['permission'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Permission'),
            '#default_value' => 'access content',
            '#description' => $this->t('If you have a permission in your module, you can
        use it, else you can declare a permission from core /
        another module or let it empty and have free access (beware of security issues)'
            ),
        );
        $form['role'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Role'),
            '#description' => $this->t('Optional , not recommended , preferably , use of permissions is recommended that the roles, example: anonymous, authenticated'),
        );
        // Wrapper and AJAX checkbox to call permission generator.
        $form['GeneratorPermissions'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Create a new permission.'),
            '#prefix' => '<div id="add-perm">',
            '#suffix' => '</div>',
        );
        $form['GeneratorPermissions']['call_GeneratorPermissions'] = array(
            '#type' => 'checkbox',
            '#title' => t('Add permission'),
            '#ajax' => array(
                'callback' => '::renderGenerator',
                'wrapper' => 'add-perm'
            ),
            '#description' => t('Generate a permission file. Please fill correctly all
    the form as the form will be auto-filled.'),
        );
        // Test with modal dialog.
        $form['links'] = array(
            '#theme' => 'links',
            '#links' => array(
                'link6' => array(
                    'title' => t('Add permission'),
                    'url' => \Drupal\Core\Url::fromRoute('generator.permissions'),
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
        // Wrapper and AJAX checkbox to call permission generator.
        $form['GeneratorTasks'] = array(
            '#type' => 'fieldset',
            '#title' => t('Promote this link as a tab.'),
            '#prefix' => '<div id="generator-tasks">',
            '#suffix' => '</div>',
        );
        $form['GeneratorTasks']['call_GeneratorTasks'] = array(
            '#type' => 'checkbox',
            '#title' => t('Add task link'),
            '#ajax' => array(
                'callback' => '::renderGenerator',
                'wrapper' => 'generator-tasks'
            ),
            '#description' => t('Task link allows you to add a tab link for this path
        against another path.'),
        );
        $form = parent::buildForm($form, $form_state);
        return $form;
    }

    // Validation of existence of the route in routing.yml file

    public function redirectModal()
    {
        $response = new AjaxResponse();
        $form_permission = \Drupal::formBuilder()
            ->getForm('\Drupal\generator_ui\Form\GenerePermissionsForm');
        $response->addCommand(new OpenModalDialogCommand('Add a permission', \Drupal::service('renderer')
            ->render($form_permission)));
        return $response;
    }

    /**
     * return type of routing: Form routing and controller routing.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   return choice of routing form.
     */
    public function choiceRouting(array $form, FormStateInterface $form_state)
    {
        $choiceRouting = $form_state->getValue('type_routing');
        switch ($choiceRouting) {
            case "controller" :
                return $form['control_method'];
                break;
            case "form_" :
                return $form['control_form'];
                break;
        }
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