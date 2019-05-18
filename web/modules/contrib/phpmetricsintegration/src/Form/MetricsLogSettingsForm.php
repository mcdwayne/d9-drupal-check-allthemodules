<?php

namespace Drupal\phpmetricsintegration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Example add and edit forms.
 */
class MetricsLogSettingsForm extends EntityForm
{

  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   */
    public function __construct(EntityTypeManager $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        $config_factory = \Drupal::configFactory();
        $config = $config_factory->getEditable('phpmetricsintegration.settings');

        $form['report_dir'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Type directory name:'),
        '#default_value' => $config->get('phpmetricsintegration.report_dir'),
        '#description' => $this->t('Type your report directory here (it stores at sites/default/files/<your directory> path).'),
        '#size' => 90
        );

        $form['scan_dir'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Type directory name:'),
        '#default_value' => $config->get('phpmetricsintegration.scan_dir'),
        '#description' => $this->t('Provide the directory you want to scan (usually if you provide ./modules it will scan <drupal_project>/modules directory for custom modules. If you need to scan full Drupal site(including core) provide "." here (please note this will be extreme time consuming)).'),
        '#size' => 90
        );

        $form['keep_log_alive'] = array(
        '#type' => 'number',
        '#title' => $this->t('Number of Logs to keep :'),
        '#default_value' => $config->get('phpmetricsintegration.keep_log_alive'),
        '#description' => $this->t('Provide the number of logs you want to keep'),
        '#size' => 3
        );

        // You will need additional form elements for your custom properties.
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $config_factory = \Drupal::configFactory();
        $config = $config_factory->getEditable('phpmetricsintegration.settings');
        $config->set('scan_dir', $form_state->getValue('scan_dir'));
        $config->set('report_dir', $form_state->getValue('report_dir'));
        $config->set('keep_log_alive', $form_state->getValue('keep_log_alive'));
        $config->save();

        return parent::submitForm($form, $form_state);

        $form_state->setRedirect('entity.phpmetricsintegration.collection');
    }
}
