<?php

namespace Drupal\wisski_mirador\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;


/**
 * Style plugin to render a mirador viewer as a 
 * views display style.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "wisskimirador",
 *   title = @Translation("WissKI Mirador"),
 *   help = @Translation("The WissKI Mirador views Plugin"),
 *   theme = "views_view_wisskimirador",
 *   display_types = { "normal" }
 * )
 */
class WisskiMirador extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['path'] = array('default' => 'wisski_mirador');
    return $options;
  }
  
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Renders the View.
   */
  public function render() {

    $view = $this->view;
    
#    dpm($view);
    
    $results = $view->result;
    
    $ent_list = array();
    $direct_load_list = array();
    
    $site_config = \Drupal::config('system.site');
    
    $to_print = "";
    
    if(!empty($site_config->get('name')))
      $to_print .= $site_config->get('name');
    if(!$site_config->get('slogan')) {
      if(!empty($to_print) && !empty($site_config->get('slogan'))) {
        $to_print .= " (" . $site_config->get('slogan') . ") ";
      } else {
        $to_print .= $site_config->get('slogan');
      }
    }
        
    global $base_url;
    
    if(empty($to_print))
      $to_print .= $base_url;   
    
    $iter = 0;
    
    foreach($results as $result) {
      $ent_list[] = array("manifestUri" => $base_url . "/wisski/navigate/" . $result->eid . "/iiif_manifest", "location" => $to_print);
#      $direct_load_list[] = array( "loadedManifest" => $base_url . "/wisski/navigate/" . $result->eid . "/iiif_manifest", "viewType" => "ImageView" );
      $direct_load_list[] = array( "loadedManifest" => $base_url . "/wisski/navigate/" . $result->eid . "/iiif_manifest", "availableViews" => array( 'ImageView'), "windowOptions" => array( "zoomLevel" => 1, "osdBounds" => array(
            "height" => 2000,
            "width" => 2000,
            "x" => 1000,
            "y" => 2000,
        )), "slotAddress" => "row1.column" . ++$iter, "viewType" => "ImageView", "bottomPanel" => false, "sidePanel" => false, "annotationLayer" => false);
    } 
    
    if(isset($view->attachment_before)) {
      $attachments = $view->attachment_before;
      
      foreach($attachments as $attachment) {
        $subview = $attachment['#view'];
        
        $subview->execute();
 
        foreach($subview->result as $res) {
          $ent_list[] = array("manifestUri" => $base_url . "/wisski/navigate/" . $res->eid . "/iiif_manifest", "location" => $to_print);
#          $direct_load_list[] = array( "loadedManifest" => $base_url . "/wisski/navigate/" . $res->eid . "/iiif_manifest", "viewType" => "ImageView" );
          $direct_load_list[] = array( "loadedManifest" => $base_url . "/wisski/navigate/" . $res->eid . "/iiif_manifest", "availableViews" => array( 'ImageView'), "windowOptions" => array( "zoomLevel" => 1, "osdBounds" => array( 
            "height" => 2000,
            "width" => 2000,
            "x" => 1000,
            "y" => 2000,
        )), "slotAddress" => "row1.column" . ++$iter, "viewType" => "ImageView", "bottomPanel" => false, "sidePanel" => false, "annotationLayer" => false );
        }
        
//        dpm($subview->result, "resi!");
        
      }
    }
    
#    dpm($ent_list, "ente gut...");

    $layout = count($ent_list);

    $layout_str = "";

    if($layout < 7) {
      $layout_str = "1x" . $layout;
    } else {
      $layout_str = "1x1";
    }
    
#    foreach($ent_list as $ent
    
    
    $form = array();
    
    $form['#attached']['drupalSettings']['wisski']['mirador']['data'] = $ent_list;
    $form['#attached']['drupalSettings']['wisski']['mirador']['layout'] = $layout_str;

    if($layout < 7) {
      $form['#attached']['drupalSettings']['wisski']['mirador']['windowObjects'] = $direct_load_list;
    }
        
    $form['#markup'] = '<div id="viewer"></div>';
    $form['#allowed_tags'] = array('div', 'select', 'option','a', 'script');
#    #$form['#attached']['drupalSettings']['wisski_jit'] = $wisski_individual;
    $form['#attached']['library'][] = "wisski_mirador/mirador";

    return $form;
  
  }
  
  
  
  
}     