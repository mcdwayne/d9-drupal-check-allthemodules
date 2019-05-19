<?php

namespace Drupal\yaml_sandbox\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Symfony\Component\DependencyInjection\ContainerInterface;


class YAMLSandboxForm extends FormBase {

  /**
   * The YAML parser plugin manager.
   *
   * @var \Drupal\Component\Serialization\Yaml
   */
  protected $parser;

  /**
   * Constructs a new YAMLSandboxForm.
   *
   * @param \Drupal\Component\Serialization\Yaml $parser
   *   The yaml parser.
   */
  public function __construct(Yaml $parser) {
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serialization.yaml')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yaml_sandbox_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check for stored data from submitted forms. If it's present we'l load the
    // YAML serialization serivce and use that to convert the YAML to PHP and
    // display it do the user.
    $stored = $form_state->get('yaml');
    if (isset($stored)) {
      try {
        $parsed = $this->parser->decode($stored);

        // This is a bit of a hack to capture the var_dump output.
        ob_start();
        var_dump($parsed);
        $string = ob_get_contents();
        ob_end_clean();
      }
      // If we can't parse the string we can at least tell the user why.
      catch (InvalidDataTypeException $e) {
        $string = $e->getMessage();
      }

      // If xdebug is enabled it modifies the ouput of var_dump into some valid
      // HTML. If it's not enabled, we need to take care of that.
      if (function_exists('xdebug_is_enabled') && !xdebug_is_enabled()) {
        $string = htmlspecialchars($string, ENT_QUOTES);
      }

      $form['output'] = array(
        '#type' => 'markup',
        '#markup' => '<pre>' . $string . '</pre>',
      );
    }

    $form['yaml'] = array(
      '#type' => 'textarea',
      '#title' => 'YAML',
      '#default_value' => isset($stored) ? $stored : '',
    );

    $form['parent']['actions'] = array(
      '#type' => 'actions'
    );
    $form['parent']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Store the submitted values so we can display them and rebuild the form.
    $form_state->set('yaml', $form_state->getValue('yaml'));
    $form_state->setRebuild(TRUE);
  }

}
