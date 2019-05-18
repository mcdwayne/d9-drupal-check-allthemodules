<?php
/**
 * @file
 * Contains \Drupal\library_pack\Form\librarypackSettingsForm
 */
namespace Drupal\library_pack\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure library_pack settings for this site.
 */
class librarypackSettingsForm extends ConfigFormBase {
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'library_pack_admin_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'library_pack.settings',
        ];
    }

    protected function encodeConfigName($name) {
        $name = str_replace('.',':',$name);
        return $name;
    }

    protected function decodeConfigName($name) {
        $name = str_replace(':','.',$name);
        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        // Get the current route parameters @todo should routeMatch be dependancy injected?
        $theme = \Drupal::routeMatch()->getParameter('theme');

        // Save the current theme name in the form state.
        $form_state->setTemporaryValue('theme_key',$theme);

        // Load the saved config for this theme.
        $config = $this->config('library_pack.settings')->get($theme);


        // Libraries section title.
        $form['heading'] = array(
            '#type' => 'item',
            '#title' => 'Available Libraries',
            '#description' => 'Below are all the libraries registered with your site, you can load a particular library by checking the box and choosing a variant.',
        );

        $form[$theme] = array(
            '#theme' => 'table',
            '#header' => array(t('Load'), t('Machine name'), t('Library'),t('Status'), t('Version'), t('Variant'), t('Download')),
            '#rows' => array(),
            '#tree' => TRUE,
        );

        // Get all the currently registered libraries.
        $libraries = array();
        foreach (libraries_info() as $name => $info) {
            $libraries[$name] = libraries_detect($name);
        }

        // Sort the libraries by array key.
        ksort($libraries);

        // Generate the field markup for each library
        foreach ($libraries as $machine_name => $library) {

            // We can't save array keys with dots in.
            $machine_name = $this->encodeConfigName($machine_name);

            $form[$theme][$machine_name]['load'] = array(
                '#type' => 'checkbox',
                '#description' => '',
                '#default_value' => (isset($config[$machine_name]['load']) ? $config[$machine_name]['load'] : 0),
            );

            // Build the variants options array.
            $variants = array('default' => 'default');
            $variants += array_keys($library['variants']);


            $form[$theme][$machine_name]['variant'] = array(
                '#type' => 'select',
                '#description' => '',
                '#options' => $variants,
                '#default_value' => (isset($config[$machine_name]['variant']) ? $config[$machine_name]['variant'] : 'default')
            );

            // If the library isnt present then you can t load it.
            if (!$library['installed']) {
                $form[$theme][$machine_name]['load']['#disabled'] = TRUE;
                $form[$theme][$machine_name]['variant']['#disabled'] = TRUE;
            }

            // Add a table row.
            $form[$theme]['#rows'][] = array(
                'data' => array(
                    array('data' => &$form[$theme][$machine_name]['load']),
                    $machine_name,
                    t('@name<br />@file', array(
                        '@name' => $library['name'],
                        '@file' =>  $machine_name . '/' . $library['version arguments']['file'],
                    )),

                    ($library['installed'] ? t('OK') : Unicode::ucfirst($library['error'])),
                    (isset($library['version']) ? $library['version'] : ''),
                    array('data' => &$form['libraries'][$machine_name]['variant']),
                    t('<a href="@download-url">Download</a>', array('@download-url' => $library['download url'],
                        )
                    )
                ),
                'class' => array(
                    ($library['installed'] ? 'ok' : 'warning')
                )
            );
        }

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = \Drupal::service('config.factory')->getEditable('library_pack.settings');

        // Load the theme key.
        $key = $form_state->getTemporaryValue('theme_key');

        // Extract the data from the form.
        $data = $form_state->getValue($key);

        // Save the data
        $config->set($key, $data)->save();

        parent::submitForm($form, $form_state);
    }
}