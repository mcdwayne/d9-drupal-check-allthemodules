<?php

namespace Drupal\comment_ajax_pager\Page;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\BeforeCommand;

class CommentAjaxPager extends ControllerBase {
	public static function ajax_pager(){
    	$response = new AjaxResponse();
        if(!empty($_POST['ajax_pager'])){
            $config = \Drupal::config('field.field.'.$_POST['ajax_pager']['entity_type'].'.'.$_POST['ajax_pager']['bundle'].'.'.$_POST['ajax_pager']['field_name'])->get();
            if(!empty($config['settings']['per_page'])){
                $config_entity_type = \Drupal::config('core.entity_view_display.'.$_POST['ajax_pager']['entity_type'].'.'.$_POST['ajax_pager']['bundle'].'.default')->get();
                $pager_id = !empty($config_entity_type['content'][$_POST['ajax_pager']['field_name']]['settings']['pager_id']) ? $config_entity_type['content'][$_POST['ajax_pager']['field_name']]['settings']['pager_id'] : 0;
                $config = \Drupal::config('field.field.'.$_POST['ajax_pager']['entity_type'].'.'.$_POST['ajax_pager']['bundle'].'.'.$_POST['ajax_pager']['field_name'])->get();
                // ---
                $mode = $config['settings']['default_mode'];
                $comments_per_page = $config['settings']['per_page'];
                $entity = \Drupal::entityTypeManager()->getStorage($_POST['ajax_pager']['entity_type'])->load($_POST['ajax_pager']['entity_id']);

                $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('comment');
                $storage = \Drupal::entityTypeManager()->getStorage('comment');
                $comments = $storage->loadThread($entity, $_POST['ajax_pager']['field_name'], $mode, $comments_per_page, $pager_id);
                if ($comments) {
                    $build = $viewBuilder->viewMultiple($comments, 'full');
                    $build['pager']['#type'] = 'pager';
                    $entity_type = $entity->getEntityType();
                    $entity_type_id = $entity_type->id();
                    $handler = \Drupal::entityTypeManager()->getRouteProviders($entity_type_id)['html'];
                    $route_collection = $handler->getRoutes($entity_type);
                    $name = 'entity.' . $entity_type->get('id') . '.canonical';
                    $route = $route_collection->get($name);
                    $entity_url = $entity->toURL();
                    if($route){
                        $build['pager']['#route_name'] = $route;
                        $build['pager']['#route_parameters'] = $entity_url->getRouteParameters();
                    }
                    if ($pager_id) {
                        $build['pager']['#element'] = $pager_id;
                    }
                    if(empty($_POST['ajax_pager']['load_more'])){
                        $response->addCommand(new HtmlCommand('div[data-ajax_comment_pager="'.$entity->id().'"] .comments_ajax_pager_wrap', $build));
                        $response->addCommand(new AppendCommand('body', '<script>jQuery(\'html, body\').animate({ scrollTop: jQuery(\'div[data-ajax_comment_pager="'.$entity->id().'"]\').offset().top-50 }, 500);</script>'));
                    } else {
                        $response->addCommand(new InvokeCommand('div[data-ajax_comment_pager="'.$entity->id().'"] .comment_load_more_pager', 'removeClass', ['ajax-load_more']));
                        $response->addCommand(new HtmlCommand('div[data-ajax_comment_pager="'.$entity->id().'"] .comment_load_more_pager', drupal_render($build['pager'])));
                        // ---
                        unset($build['pager']);
                        $response->addCommand(new BeforeCommand('div[data-ajax_comment_pager="'.$entity->id().'"] .comment_load_more_pager', drupal_render($build)));                        
                    }
                }
            }
        }
        return $response;
  	}
}