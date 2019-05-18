<?php
/**
 * @file
 * Contains Drupal\favorites\Controller\MyFavController.
 */

namespace Drupal\favorites\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;;
use Drupal\Core\Controller\ControllerBase;
use Drupal\favorites\FavoriteStorage;
use Drupal\Core\Url;
use Drupal\Core\Render\Element\Link;


/**
 * Class MyFavController.
 *
 * @package Drupal\favorites\Controller
 */
class MyFavController extends ControllerBase {
	
	protected $account;
	
	public function __construct(){
            $this->account = \Drupal::currentUser();
	}
	
        /**
        * Add a favorite.
        */
	public function addFavJS(){
            global $base_url;
             if(empty($this->account->id())){
                    return;
            }
            else{
                $uid = $this->account->id();
            }
            
            $title = $_POST['title'];
            $path = $_POST['path'];
            $query = $_POST['query'];
            
            FavoriteStorage::deleteFav($this->account->id(), $path, $query);
            FavoriteStorage::addFav($this->account->id(), $path, $query, $title);
            
            $result = FavoriteStorage::getFavorites($uid);
            $message = '<ul>';
            foreach ($result as $favorite) {
                $favorite->path = \Drupal::service('path.alias_manager')->getAliasByPath('/'.trim($favorite->path,'/'));
                if($favorite->query != ''){
                    $url = $base_url.$favorite->path.'?'.$favorite->query;    
                }
                else{
                    $url = $base_url.$favorite->path;    
                }               
                $url = Url::fromUri($url);
                $url_delete = Url::fromRoute('favorites.remove',['fid'=>$favorite->fid]);
                $message .= '<li>'.\Drupal::l($favorite->title, $url).' <span id="del-'.$favorite->fid.'">'.\Drupal::l('X', $url_delete).'</span></li>';                   
            }
            $message .= '</ul>';
            
            $response = new AjaxResponse();
            $response->addCommand(new HtmlCommand('#myfavlist', $message));
            return $response;
        }
        
        /**
        * Remove a favorite.
        */
        public function remove($fid){
            $favorite = FavoriteStorage::getFav($fid);            
            $access = (\Drupal::currentUser()->hasPermission('manage favorites') && $this->account->id() == $favorite->uid);
            if($access){
                FavoriteStorage::deleteFavorite($fid);
                $options = array('list' => 'del-'.$fid);
                return new JsonResponse($options);
            }            
        }
}
 
