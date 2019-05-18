<?php
/**
 * @file
 * Contains \Drupal\GeneratorUI\Form\GenerePermissionsForm.
 *
 */

namespace Drupal\generator_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorPermissions extends Generator
{

    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'permissions';
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
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['into'] = array(
            '#markup' => t('<h2>' . 'Please fill the blanks to create your  module.permissions.yml ' . '</h2>'),
            "#weight" => -2
        );
        $form['twig_file'] = array(
            "#type" => 'hidden',
            "#value" => array('generator.permissions.yml.twig'),
        );
        $form['transformation_path'] = array(
            '#type' => 'hidden',
            '#value' => true,
        );
        $form['key'] = array(
            '#type' => 'textfield',
            '#title' => t('Permission name'),
            '#default_value' => t('administer my module'),
            '#description' => t('Machine name of the permission, used in code.'),
            '#required' => TRUE,
        );
        $form['title'] = array(
            '#type' => 'textfield',
            '#title' => t("Permission's title"),
            '#default_value' => t('Title permission'),
            '#description' => t('Title of the permission shown in the permission administration page.'),
            '#required' => TRUE,
        );
        // Change textfield to textarea
        $form['description'] = array(
            '#type' => 'textarea',
            '#title' => t("Permission's description"),
            '#description' => t('Description of permission shown in the permission administration page (optional).'),
            '#default_value' => t('Description of my permission.'),
        );
        $form['access'] = array(
            '#type' => 'select',
            '#title' => t(' restrict access'),
            "#description" => t('Indicates to Drupal core whether or not to display a warning message on the permission administration page. Based on the Drupal Security team\'s policy,
            it will not produce a security advisory for any very sensible permission that should be restricted for advanced administrators only.'),
            '#options' => array(
                'TRUE' => 'True',
                'FALSE' => 'False',
            ),
        );

        $form = parent::buildForm($form, $form_state);
        return $form;
    }
}