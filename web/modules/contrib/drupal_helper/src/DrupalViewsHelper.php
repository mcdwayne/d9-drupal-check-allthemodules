<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:51 PM
 */
namespace Drupal\drupal_helper;

use Drupal\views\Views;

class DrupalViewsHelper
{
     function render_view_mode($entity_type,$entity , $mode_view){
       return \Drupal::entityTypeManager()->getViewBuilder($entity_type)
         ->view($entity, $mode_view);
     }
     function field_render($field_name,$entity,$entity_type='node'){
         $source = null;
         if(is_object($entity)){
             $source =$entity ;
         }else{
             if(is_numeric($entity)){
                 $source = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity);
             }
         }
         $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
         $output = '';

         if ($source && $source->hasField($field_name) && $source->access('view')) {
             $value = $source->get($field_name);
             $output = $viewBuilder->viewField($value, 'full');
             $output['#cache']['tags'] = $source->getCacheTags();
         }
         return $output ;
     }

     function render_views($view_name,$diplay_id,$argument=[]){
         $view = Views::getView($view_name);
         $view->setDisplay($diplay_id);
         $view->preExecute();
         $view->setArguments($argument);
         $view->execute();
         return $view->buildRenderable($diplay_id,$argument);
     }
     /**
      * @return views object
     **/
     function views_result_by_pager_size($view_name,$diplay_id,$pager_item_size = 200){
       // $view = Views::getView($view_name);
       // $view->storage->load($view_name);
        $view = Views::getView($view_name);
        $view->setDisplay($diplay_id);
        $view->setItemsPerPage($pager_item_size);
        $view->execute();
        return $view;
      }

     function render_view_exposed_form($view_name){
         $view = Views::getView($view_name);
         $view->initHandlers();
         $form_state = new \Drupal\Core\Form\FormState();
         $form_state->setFormState([
             'view' => $view,
             'display' => $view->display_handler->display,
             'exposed_form_plugin' => $view->display_handler->getPlugin('exposed_form'),
             'method' => 'GET',
             'rerender' => TRUE,
             'no_redirect' => TRUE,
             'always_process' => TRUE, // This is important for handle the form status.
         ]);
         return \Drupal::formBuilder()->buildForm('Drupal\views\Form\ViewsExposedForm', $form_state);
     }

}