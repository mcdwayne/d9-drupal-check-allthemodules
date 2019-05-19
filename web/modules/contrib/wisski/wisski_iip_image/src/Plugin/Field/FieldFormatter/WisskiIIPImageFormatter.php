<?php
 /**
 * @file
 * Definition of Drupal\wisski_iip_image\Plugin\field\formatter\WisskiIIPImageFormatter.
 */
   
  namespace Drupal\wisski_iip_image\Plugin\Field\FieldFormatter;
   
  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Field\FieldItemListInterface;
  use Drupal\Core\Field\FieldDefinitionInterface;
  use Drupal\Core\Link;
  use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
  use Drupal\Core\Session\AccountInterface;
  use Drupal\Core\Url;
  use Drupal\image\Entity\ImageStyle;
  use Symfony\Component\DependencyInjection\ContainerInterface;
  use Drupal\Core\Form\FormStateInterface;
  use Drupal\Core\Cache\Cache;
  use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;
  use Drupal\colorbox\Plugin\Field\FieldFormatter\ColorboxFormatter;
  use Drupal\Core\Template\Attribute;
    
  /**
   * Plugin implementation of the 'wisski_iip_image' formatter.
   *
   * @FieldFormatter(
   *   id = "wisski_iip_image",
   *   module = "wisski_iip_image",
   *   label = @Translation("WissKI IIP Image Viewer"),
   *   field_types = {
   *     "image"
   *   }
   * )
   */
#  class WisskiIIPImageFormatter extends ImageFormatterBase {
  class WisskiIIPImageFormatter extends ColorboxFormatter {

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode) {

      $elements = parent::viewElements($items, $langcode);    
      
      $elements['#attached']['library'][] = 'wisski_iip_image/iipmooviewer';
      $elements['#attached']['library'][] = 'wisski_iip_image/iip_integration';
      $elements['#attached']['drupalSettings']['wisski']['iip']['config'] = \Drupal::config('wisski_iip_image.config')->get();

      $files = $this->getEntitiesToView($items, $langcode);

      // Early opt-out if the field is empty.
      if (empty($files)) {
        return $elements;
      }
      
      $service = \Drupal::service('image.toolkit.manager');
      $toolkit = $service->getDefaultToolkit();
#      dpm($toolkit);
#      $config = $this->configFactory->getEditable('imagemagick.settings');
      
      if(empty($toolkit) || $toolkit->getPluginId() !== "imagemagick") {
        drupal_set_message('Your default toolkit is not imagemagick. Please use imagemagick for this module.', "error");
        return $elements;
      }
      
      $config = \Drupal::service('config.factory')->getEditable('imagemagick.settings');
      
      $formats = $config->get('image_formats');
      
      if(!isset($formats["PTIF"])) {
        drupal_set_message("PTIF was not a valid image format. We enabled it for you. Make sure it is supported by your imagemagick configuration.");
        $formats["PTIF"] = array('mime_type' => "image/tiff", "enabled" => TRUE);
        $config->set('image_formats', $formats);
        $config->save();
      }
      

      $image_style_name = 'wisski_pyramid';

      if(! $image_style = \Drupal\image\Entity\ImageStyle::load($image_style_name)) {
        $values = array('name'=>$image_style_name,'label'=>'Wisski Pyramid Style');
        $image_style = \Drupal\image\Entity\ImageStyle::create($values);
        $image_style->addImageEffect(array('id' => 'WisskiPyramidalTiffImageEffect'));
        $image_style->save();
      }

      foreach ($files as $delta => $file) {
        
        // in case of prerendered files - use these paths.        
        $prerendered_paths = \Drupal::config('wisski_iip_image.settings')->get('wisski_iip_image_prerendered_path');
        
        // if there are paths
        if(!empty($prerendered_paths)) {
          $mainbreak = FALSE;
          
          // try if any of them has files
          foreach($prerendered_paths as $prerendered_path) {
            $image_uri = $prerendered_path . $file->getFilename();
            
            // if we find anything break here
            if(file_exists($image_uri)) {
              $mainbreak = TRUE;
            }
          }
          // continue with next image
          if($mainbreak)
            continue;
          // if we did not find anything we generate a derivative
        }
                
        $image_uri = ImageStyle::load('wisski_pyramid')->buildUri($file->getFileUri());
        
        if(!file_exists($image_uri))
          $image_style->createDerivative($file->getFileUri(),$image_uri);

#        $url = Url::fromUri(file_create_url($image_uri));     

      }
#      dpm($elements);

      return $elements;

    }

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
      return [
        'wisski_inline' => 'FALSE',
      ] + parent::defaultSettings();
    }
    
    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {

      $element['wisski_inline'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Inline mode for IIP'),
        '#default_value' => $this->getSetting('wisski_inline'),
      ];
      
      $element = $element + parent::settingsForm($form, $form_state);

      return $element;
    }
    
    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
      return parent::settingsSummary();
    }
  }
