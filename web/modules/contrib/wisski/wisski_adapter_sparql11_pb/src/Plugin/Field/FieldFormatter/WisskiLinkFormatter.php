<?php

/**
 * @file
 * Contains \Drupal\wisski_adapter_sparql11_pb\Plugin\Field\FieldFormatter\WisskiLinkFormatter.
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
 * Plugin implementation of the 'wisski_link_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "wisski_link_formatter",
 *   module = "wisski_adapter_sparql11_pb",
 *   label = @Translation("WissKI Link Formatter"),
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
class WisskiLinkFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  
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
      // Add default settings with text, so we can wrap them in t().
      // in milliseconds.
      'speed' => 100,
      'collapsedHeight' => 75,
      // In pixel.
      'heightMargin' => 16,
      'moreLink' => '<a href="#">' . t('Read more') . '</a>',
      'lessLink' => '<a href="#">' . t('Close') . '</a>',
      'embedCSS' => 1,
      'sectionCSS' => 'display: block; width: 100%;',
      'startOpen' => 0,
      'expandedClass' => 'readmore-js-expanded',
      'collapsedClass' => 'readmore-js-collapsed',
      'imagecache_external_style' => '',
      'imagecache_external_link' => '',
      'use_readmore' => 1,
      'use_title_pattern' => 1,
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
    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $elements['imagecache_external_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $settings['imagecache_external_link'],
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );
    
    $elements['use_readmore'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use readmore'),
      '#description' => $this->t('Do you want to use readmore?'),
      '#default_value' => $this->getSetting('use_readmore'),
    ];

    $elements['speed'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Speed'),
      '#description' => $this->t('Speed for show / hide read more.'),
      '#default_value' => $this->getSetting('speed'),
    ];

    $elements['collapsedHeight'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Collapsed Height'),
      '#description' => $this->t('Height after which readmore will be added.'),
      '#default_value' => $this->getSetting('collapsedHeight'),
    ];

    $elements['heightMargin'] = [
      '#type' => 'number',
      '#min' => 1,
      '#title' => $this->t('Height margin'),
      '#description' => $this->t('Avoids collapsing blocks that are only slightly larger than maxHeight.'),
      '#default_value' => $this->getSetting('heightMargin'),
    ];

    $elements['moreLink'] = [
      '#type' => 'textfield',
      '#title' => $this->t('More link'),
      '#description' => $this->t('Link for more.'),
      '#default_value' => $this->getSetting('moreLink'),
    ];

    $elements['lessLink'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Less link'),
      '#description' => $this->t('Link for less.'),
      '#default_value' => $this->getSetting('lessLink'),
    ];

    $elements['embedCSS'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Embed CSS'),
      '#description' => $this->t('Insert required CSS dynamically, set this to false if you include the necessary CSS in a stylesheet.'),
      '#default_value' => $this->getSetting('embedCSS'),
    ];

    $elements['sectionCSS'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Section styling'),
      '#description' => $this->t('Sets the styling of the blocks, ignored if embedCSS is false).'),
      '#default_value' => $this->getSetting('sectionCSS'),
    ];

    $elements['startOpen'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start open'),
      '#description' => $this->t('Do not immediately truncate, start in the fully opened position.'),
      '#default_value' => $this->getSetting('startOpen'),
    ];

    $elements['expandedClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expanded class'),
      '#description' => $this->t('Class added to expanded blocks.'),
      '#default_value' => $this->getSetting('expandedClass'),
    ];

    $elements['collapsedClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collapsed class'),
      '#description' => $this->t('Class added to collapsed blocks.'),
      '#default_value' => $this->getSetting('collapsedClass'),
    ];
    
    $elements['use_title_pattern'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use title pattern'),
      '#description' => $this->t('Use title pattern for display of disambiguation.'),
      '#default_value' => $this->getSetting('use_title_pattern'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    /*
    $image_styles = image_style_options(FALSE);

    // Unset possible 'No defined styles' option.
    unset($image_styles['']);

    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$settings['imagecache_external_style']])) {
      $summary[] = t('Image style: @style', array(
        '@style' => $image_styles[$settings['imagecache_external_style']],
      ));
    }
    else {
      $summary[] = t('Original image');
    }
*/
    $link_types = array(
      'content' => t('Linked to content'),
      'file' => t('Linked to file'),
    );

    // Display this setting only if image is linked.
    if (isset($link_types[$settings['imagecache_external_link']])) {
      $summary[] = $link_types[$settings['imagecache_external_link']];
    }
    
    $summary[] = $this->t('Speed: @value', ['@value' => $this->getSetting('speed')]);
    $summary[] = $this->t('Collapsed Height: @value', ['@value' => $this->getSetting('collapsedHeight')]);
    $summary[] = $this->t('Height margin: @value', ['@value' => $this->getSetting('heightMargin')]);
    $summary[] = $this->t('More link: @value', ['@value' => $this->getSetting('moreLink')]);
    $summary[] = $this->t('Less link: @value', ['@value' => $this->getSetting('lessLink')]);
    $summary[] = $this->t('Embed CSS: @value', ['@value' => $this->getSetting('embedCSS')]);
    $summary[] = $this->t('Section styling: @value', ['@value' => $this->getSetting('sectionCSS')]);
    $summary[] = $this->t('Start open: @value', ['@value' => $this->getSetting('startOpen')]);
    $summary[] = $this->t('Expanded class: @value', ['@value' => $this->getSetting('expandedClass')]);
    $summary[] = $this->t('Collapsed class: @value', ['@value' => $this->getSetting('collapsedClass')]);

    
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
 #     dpm($delta);

#     dpm($item->getEntity());
      
      #drupal_set_message("item: " . serialize($values['value']));
      
#      $elements[$delta] = array(
        #'#theme' => 'text',
#        '#type' => 'textfield',
#        '#title' => 'dssdf',
#        '#default_value' => $values['value'],
#      );
#      dpm($item->wisskiDisamb);
#      dpm(serialize($item));

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
        
#        drupal_set_message("url: " . serialize($url));

#        drupal_set_message(serialize($item->value));

        $generated_title = "";

        // are there any bundles?
        $buns = AdapterHelper::getBundleIdsForEntityId($entity_id, TRUE);

        #dpm($buns, "buns");
        
        #dpm($settings, "yay!");

        if(!empty($buns)) {
          // if there is a bundle we have to tell the system that it should cache this!
          $the_bundle = current($buns);
          WisskiCacheHelper::putCallingBundle($entity_id,$the_bundle);
        }

        if($settings['use_title_pattern'] && !empty($buns) ) {
          
        #  dpm("I generate title for $entity_id");
          $generated_title = wisski_core_generate_title($entity_id);
        #  dpm($generated_title, "yay!");
        }
        
        if($generated_title != "") {
          $elements[$delta] = array(
            '#type' => 'link',
            '#title' => $generated_title,
            '#url' => Url::fromRoute('entity.wisski_individual.canonical', ['wisski_individual' => $entity_id]),
//          '#url' => Url::fromUri('internal:/' . $url),
          );
        } else if(!empty($buns)) { // if there is a form to link to
          $elements[$delta] = array(
            '#type' => 'link',
            '#title' => $item->value,
            '#url' => Url::fromRoute('entity.wisski_individual.canonical', ['wisski_individual' => $entity_id]),
          );
        } else {
          $elements[$delta] = array(
            '#type' => 'inline_template',
            '#template' => '{{ value|nl2br }}',
            '#context' => ['value' => $item->value],
          );
        }

#        dpm($url, "url");
        
#        drupal_set_message(serialize($elements));
      }
      
    }
/*
    // Check if the formatter involves a link.
    if ($settings['imagecache_external_link'] == 'content') {
      // TODO: convert to D8
      // $uri = entity_uri($entity_type, $entity).
    }
    elseif ($settings['imagecache_external_link'] == 'file') {
      $link_file = TRUE;
    }

    // Check if the field provides a title.
    if ($field->getType() == 'link') {
      if ($field_settings['title'] != DRUPAL_DISABLED) {
        $field_title = TRUE;
      }
    }

#    drupal_set_message(serialize($items));

    foreach ($items as $delta => $item) {
      // Get field value.
      $values = $item->toArray();

      // Set path and alt text.
      $image_alt = '';
#      drupal_set_message(serialize($field->getType()));
      if ($field->getType() == 'link') {
        $image_path = imagecache_external_generate_path($values['uri']);
        // If present, use the Link field title to provide the alt text.
        if (isset($field_title)) {
          // The link field appends the url as title when the title is empty.
          // We don't want the url in the alt tag, so let's check this.
          if ($values['title'] != $values['uri']) {
            $image_alt = isset($field_title) ? $values['title'] : '';
          }
        }
      }
      else {
        $image_path = imagecache_external_generate_path($values['value']);
      }
#      drupal_set_message(serialize($values['value']));
      $image = $this->imageFactory->get($image_path);
      $elements[$delta] = array(
        '#theme' => 'image_style',
        '#style_name' => $settings['imagecache_external_style'],
        '#width' => $image->getWidth(),
        '#height' => $image->getHeight(),
        '#uri' => $image_path,
        '#alt' => $image_alt,
        '#title' => '',
      );

    }
  */

  
    if($settings['use_readmore']) {
  
      $integer_fields = [
        'speed',
        'collapsedHeight',
        'heightMargin',
        'embedCSS',
        'startOpen',
      ];
      foreach ($integer_fields as $key) {
        $settings[$key] = (int) $settings[$key];
      }
      $field_name = $items->getFieldDefinition()->getName();
    #foreach ($items as $delta => $item) {
#      dpm($elements, "elements");

      if(!empty($elements)) {
        $unique_id = Html::getUniqueId('field-readmore-' . $field_name);
#        $elements['#prefix'] = '<div class="field-readmore ' . $unique_id . '">';
#        $elements['#suffix'] = '</div>';

        if(!empty($elements['#attributes']) && !empty($elements['#attributes']['class'])) {
          $elements['#attributes']['class'] = array_merge($elements['#attributes']['class'], array("field-readmore " . $unique_id));
        } else {
          $elements['#attributes']['class'] = array("field-readmore " . $unique_id);
        }
#        $elements['#attached']['library'][] = 'wisski_adapter_sparql11_pb/readmorejs';
        $elements['#attached']['library'][] = 'wisski_adapter_sparql11_pb/readmore';
        $elements['#attached']['drupalSettings']['readmoreSettings'][$unique_id] = $settings;
      }
    }
  
    return $elements;
  
  }
  
}                   
