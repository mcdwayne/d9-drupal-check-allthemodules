<?php
/**
 * @file
 * Contains \Drupal\collect_test\Plugin\collect\Processor\TestSpicer.
 */

namespace Drupal\collect_test\Plugin\collect\Processor;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Processor\ProcessorBase;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Configurable Collect processor used for testing.
 *
 * @Processor(
 *   id = "spicer",
 *   label = @Translation("Spicer"),
 *   description = @Translation("Spice up your life.")
 * )
 */
class Spicer extends ProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function process(CollectDataInterface $data, array &$context) {
    $context['spice'] = $this->getConfigurationItem('spice');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['spice'] = array(
      '#type' => 'textfield',
      '#title' => 'Spice',
      '#description' => 'Which spice to use. Must be one of pepper, chili or ginger.',
      '#default_value' => $this->getConfigurationItem('spice'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!in_array($form_state->getValue('spice'), ['pepper', 'chili', 'ginger'])) {
      $form_state->setError($form['spice'], 'The spice must be one of pepper, chili or ginger.');
    }
  }

}
