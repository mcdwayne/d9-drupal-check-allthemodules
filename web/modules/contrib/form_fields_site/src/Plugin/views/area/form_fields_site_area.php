<?php

namespace Drupal\form_fields_site_area\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\DefaultSummary;

/**
 * Views area handler to display some configurable result summary.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("form_fields_site_area")
 */
class form_fields_site_area extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {

    $options = parent::defineOptions();

    $options['content'] = [
      'default' => $this->t('Displaying Form Fields and their labels'),
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    parent::buildOptionsForm($form, $form_state);

    $optionsArray = array();

    $this->getExposedFilterOptions =  $this->exposedFilterOptionsForView();

    foreach($this->getExposedFilterOptions as $key=>$value) {
        $optionsArray[] = "@$key -- $value";       
    }


 
    $options = [
      '#theme' => 'item_list',
      '#items' => $optionsArray
    ];

    $list = \Drupal::service('renderer')->render($options);
    $form['content'] = [
      '#title' => $this->t('Display'),
      '#type' => 'textarea',
      '#rows' => 3,
      '#default_value' => $this->options['content'],
      '#description' => $this->t('You may use HTML code in this field. The following tokens are supported:') . $list,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // Must have options and does not work on summaries.
    if (!isset($this->options['content']) || $this->view->style_plugin instanceof DefaultSummary) {
      return [];
    }
 
     $replacements = [];

     $output = '';

     $filterOptions = $this->getExposedValuesDisplayResult();
     $exposedInput = $this->view->getExposedInput();
     $keysFilterOptions = array_keys($filterOptions);



     foreach( $exposedInput as $key=>$value) {

           
           if( in_array($key, $keysFilterOptions) )  {

              if ( $filterOptions[$key]['pluginid'] == 'taxonomy_index_tid') {
            
                       $taxonomyValue = '';

                      if ( is_array($exposedInput[$key] )) {
   
                         foreach($exposedInput[$key] as $value) 
                         $taxonomyValue  = $taxonomyValue ." , ". $this->getTaxonomyName($value);
                    }
                                        
                     else
                         $taxonomyValue = $this->getTaxonomyName($exposedInput[$key]);

                      $replacements["@$key"]  = trim($taxonomyValue," , ");
              }

            else if (  $filterOptions[$key]['pluginid'] == 'boolean' )
                      $replacements["@$key"] = $filterOptions[$key]['label']; 
 
            else if (  $filterOptions[$key]['pluginid'] == 'datetime' ) {

                           $datetime = '';
 
                      if ( is_array($exposedInput[$key]) )  {

                         foreach ( $exposedInput[$key] as $value )
                             $datetime = $datetime. " ".$value;
                      }
                      else
                             $datetime = $exposedInput[$key]; 
                             $replacements["@$key"] = $datetime; 
                       
                }
             else
                      $replacements["@$key"]  = $exposedInput[$key];
   
               }

}


  foreach($filterOptions as $key=>$value)  {


   $searchkey = "@$key";


  
   if ( !in_array ($searchkey, array_keys($replacements)) ) {
         $replacements[$searchkey] = "";
  }
 }





    $format = $this->options['content'];


    if (!empty($replacements)) {
      $output .= Xss::filterAdmin(str_replace(array_keys($replacements), array_values($replacements), $format));
      // Return as render array.
      return [
        '#markup' => $output,
      ];
    }

    return [];
  }



  private function getExposedValuesDisplayResult() {


  $viewsArray = array();

  foreach ($this->view->display_handler->getHandlers('filter') as $label => $filter)  {

      if (!$filter->isExposed()) {
        continue;
      }

   $machinename = $filter->options['expose']['identifier'];
   $pluginid = $filter->options['plugin_id'];
   $viewsArray[$machinename]['pluginid'] = $pluginid;
   $viewsArray[$machinename]['label'] = $filter->options['expose']['label'];
  }

   return $viewsArray;

  }



 private function exposedFilterOptionsForView() {


  $viewsArray = array();

  foreach ($this->view->display_handler->getHandlers('filter') as $label => $filter)  {

      if (!$filter->isExposed()) {
        continue;
      }


   $label = $filter->options['expose']['label'];
   $machinename = $filter->options['expose']['identifier'];
   $viewsArray[$machinename] = $label;

  }
   return $viewsArray;
}





 private function getTaxonomyName($tid) {


    $connection = \Drupal::database();
    $query = $connection->select('taxonomy_term_field_data', 'term_data');

    $query->condition('term_data.tid', $tid);
    $query->fields('term_data', ['name']);

    $result = $query->execute()->fetchField();;

    return $result;
  }




}
