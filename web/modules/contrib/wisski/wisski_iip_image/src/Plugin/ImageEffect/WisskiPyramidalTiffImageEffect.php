<?php
/**
 * @file
 * Contains \Drupal\wisski_iip_image\Plugin\ImageEffect\WisskiPyramidalTiffImageEffect.
 */
 
// Ensure the namespace here matches your own modules namespace and directory structure.
namespace Drupal\wisski_iip_image\Plugin\ImageEffect;


// The various classes we will be using for the definition and application of our ImageEffect.
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\ConfigurableImageEffectBase;


/**
 * A description of the image effect plugin.
 * 
 * The annotation below is the mechanism that all plugins use. It allows you to specify metadata
 * about the class. You'll need to update this to match your use case.
 *
 * @ImageEffect(
 *   id = "WisskiPyramidalTiffImageEffect",
 *   label = @Translation("WissKI Pyramidal Tiff Convert "),
 *   description = @Translation("Creates Pyramidal Tiff Derivates.")
 * )
 */
 
class WisskiPyramidalTiffImageEffect extends ConfigurableImageEffectBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'level' => 10,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'image_effects_convolution_sharpen_summary',
      '#data' => $this->configuration,
      ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['level'] = array(
      '#type' => 'number',
      '#title' => t('Sharpen level'),
      '#description' => t('Typically 1 - 50.'),
      '#default_value' => $this->configuration['level'],
      '#required' => TRUE,
      '#allow_negative' => FALSE,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['level'] = $form_state->getValue('level');
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // Apply any effects to the image here.

    $image->apply('pyramid', array());
#    drupal_set_message("yay, I am here!");
    
#    drupal_set_message(serialize($image->apply('pyramid', array())));

#    drupal_set_message("done.");
    
#    $source = $image->getSource();
    
#    $result = shell_exec("convert " . $source . " -define tiff:tile-geometry=256x256 -compress jpeg 'ptif:" . escapeshellarg($destination) . "'";);
    
    return TRUE;
  }
}
