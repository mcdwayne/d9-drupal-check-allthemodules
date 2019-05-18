<?php

namespace Drupal\composerize\Form;

use Drupal\composerize\Generator;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GenerateForm extends FormBase {

  /**
   * The generator service.
   *
   * @var \Drupal\composerize\Generator
   */
  protected $generator;

  /**
   * GenerateForm constructor.
   *
   * @param \Drupal\composerize\Generator $generator
   *   The generator srevice.
   */
  public function __construct(Generator $generator) {
    $this->generator = $generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('composerize.generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'composerize_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $form['json'] = [
        '#type' => 'textarea',
        '#rows' => 40,
        '#default_value' => $this->generator->generate(),
      ];
    }
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here.
  }

}
