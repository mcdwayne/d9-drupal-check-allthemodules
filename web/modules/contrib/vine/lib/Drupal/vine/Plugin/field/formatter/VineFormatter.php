<?php

/**
 * @file
 * Contains \Drupal\vine\Plugin\field\formatter\VineFormatter.
 */
namespace Drupal\vine\Plugin\field\formatter;

// Field formatter annotation class
use Drupal\field\Annotation\FieldFormatter;
// Annotation translation class
use Drupal\Core\Annotation\Translation;
// FormatterBase class
use Drupal\field\Plugin\Type\Formatter\FormatterBase;
// Entityinterface
use Drupal\Core\Entity\EntityInterface;
// FieldInterface
use Drupal\Core\Entity\Field\FieldInterface;

/**
* Plugin implementation of the 'vine_formatter' Formatter
*
* @FieldFormatter(
*   id = "vine_formatter",
*   module = "vine",
*   label = @Translation("Embedded Vine"),
*   field_types = {
*     "text",
*   },
*   settings = {
*     "size" = "480px",
*     "style" = "simple"
*   }
* )
*/
class VineFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    //Initialize the element variable
    $element = array();
    //Add your select box
    $element['size'] = array(
      '#type'           => 'select',                           // Use a select box widget
      '#title'          => t('Vine Size'),                   // Widget label
      '#description'    => t('Select the size for embedded Vine'), // Helper text
      '#default_value'  => $this->getSetting('size'),              // Get the value if it's already been set
      '#options'        => array(
        '320px'  => 'Small (320px)',
        '480px' => 'Medium (480px)',
        '600px'  => 'Large (600px)',
      ),
    );
    $element['style'] = array(
      '#type'           => 'select',                           // Use a select box widget
      '#title'          => t('Vine Style'),                   // Widget label
      '#description'    => t('Select the style for embedded Vine'), // Helper text
      '#default_value'  => $this->getSetting('style'),              // Get the value if it's already been set
      '#options'        => array(
        'simple'  => 'Simple',
        'postcard' => 'Postcard',
      ),
    );
    return $element;
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = t('Use a @size embedded Vine with "@style" style', array(
      '@size'   => $this->getSetting('size'),
      '@style'  => $this->getSetting('style'),
    )); // we use t() for translation and placeholders to guard against attacks
    
    return $summary;
  }
  
  /**
   * {@inheritdoc}
   */
  public function viewElements(EntityInterface $entity, $langcode, FieldInterface $items) {
    $elements = array();
    $size = $this->getSetting('size'); // The Size setting selected in the settings form
    $style = $this->getSetting('style'); // The Style assigned in settings
  
    foreach ($items as $delta => $item) {
        $elements[$delta] = array(
          '#type' => 'markup',
          '#markup' => '<iframe class="vine-embed" src="' . $item->value . '/embed/' . $style . '" width="' . $size . '" height="' . $size . '" frameborder="0"></iframe><script async src="//platform.vine.co/static/scripts/embed.js" charset="utf-8"></script>', // Assign it to the #markup of the element
        );
    }
    return $elements;
  }
  
}
  
?>
