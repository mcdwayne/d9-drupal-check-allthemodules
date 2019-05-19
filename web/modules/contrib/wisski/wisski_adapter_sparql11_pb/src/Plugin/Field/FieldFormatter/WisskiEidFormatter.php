<?php

/**
 * @file
 * Contains \Drupal\wisski_adapter_sparql11_pb\Plugin\Field\FieldFormatter\WisskiEidFormatter.
 */
   
namespace Drupal\wisski_adapter_sparql11_pb\Plugin\Field\FieldFormatter;
   
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\Component\Utility\Html;
use Drupal\wisski_core\WisskiCacheHelper;
   
/**
 * Plugin implementation of the 'wisski_eid_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "wisski_eid_formatter",
 *   module = "wisski_adapter_sparql11_pb",
 *   label = @Translation("WissKI Eid Formatter"),
 *   field_types = {
 *     "link",
 *     "text",
 *     "string",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class WisskiEidFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  
  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ImageFactory $image_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

  #  $this->imageFactory = $image_factory;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'use_eid' => 0,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $elements = [];
/*
    $image_styles = image_style_options(FALSE);
    $elements['imagecache_external_style'] = array(
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $settings['imagecache_external_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
    );
*/

    $elements['use_eid'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Print eid'),
      '#description' => $this->t('Print just the eid'),
      '#default_value' => $this->getSetting('use_eid'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();

    $summary[] = $this->t('Use Eid: @value', ['@value' => $this->getSetting('use_eid')]);
    
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * TODO: fix link functions.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    
    $settings = $this->getSettings();
    $field = $items->getFieldDefinition();
    $field_settings = $this->getFieldSettings();
    $elements = [];
//    drupal_set_message(serialize($field->getLabel() == "Entity name"));
#    drupal_set_message(serialize($items[0]->getParent()->getEntity()->id()));

#    drupal_set_message("yay!" . microtime());
    
    foreach($items as $delta => $item) {
#      dpm($item);
      $values = $item->toArray();

      $parentid = 0;
      // the parentid might also be relevant in case of entity name
      if($field->getLabel() == "Entity name") {
        $parent = $item->getParent();

        if($parent)
          $parentid = $item->getParent()->getEntity()->id();
      }
      
      if(empty($item->wisskiDisamb) && empty($parentid)) {
        $elements[$delta] = array(
          '#type' => 'inline_template',
          '#template' => '{{ value|nl2br }}',
          '#context' => ['value' => $item->value],
        );
      } else if(!empty($parentid)) {
        $elements[$delta] = array(
          '#type' => 'link',
          '#title' => $item->value,
          '#url' => Url::fromRoute('entity.wisski_individual.canonical', ['wisski_individual' => $parentid]),
//          '#url' => Url::fromUri('internal:/' . $url),
        );
      } else {
        #drupal_set_message("got: " . serialize($item->wisskiDisamb));
        
        $url = $item->wisskiDisamb;
#        $url = str_replace('/', '\\', $url);
        $entity_id = AdapterHelper::getDrupalIdForUri($url);
        $url = 'wisski/navigate/' . $entity_id . '/view';

        // if we want the eid, it should be like that!
        if($settings['use_eid']) {
#          dpm("use the eid!");
          $elements[$delta] = array(
            '#type' => 'item',
            '#markup' => $entity_id,
          );
          
        }
        
      }
      
    }

#    dpm($elements, "elements");
  
    return $elements;
  
  }
  
}                   
