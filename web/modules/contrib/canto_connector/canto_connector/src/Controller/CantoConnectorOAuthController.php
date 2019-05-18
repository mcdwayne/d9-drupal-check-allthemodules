<?php

namespace Drupal\canto_connector\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\canto_connector\CantoConnectorRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CantoConnectorOAuthController extends ControllerBase {
    
    
    protected $repository;
    public function __construct(CantoConnectorRepository $repository) {
        $this->repository = $repository;
    }
    
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('canto_connector.repository') ,
            $container->get('string_translation'));
    }
    
  
    public function saveAccessToken(Request $request) {
        \Drupal::logger('canto_connector')->notice('saveAccessToken');
         $user =  \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $userId= $user->get('uid')->value;
        $env=$this->config('canto_connector.settings')->get('env');

        $entry = [
            'accessToken' => $request->request->get('accessToken'),
            'tokenType' => $request->request->get('tokenType'),
            'subdomain'=>$request->request->get('subdomain'),
            'uid'=>$userId,
            'env'=> is_null($env)?"canto.com":$env
        ]; 
        

        $return_value = $this->repository->insert($entry);

        return new JsonResponse($return_value);
    }
    
    
    public function deleteAccessToken(Request $request) {
        \Drupal::logger('canto_connector')->notice('deleteAccessToken');
        $user =  \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $userId= $user->get('uid')->value;
        $entry = [
            'accessToken' => $request->request->get('accessToken'),
            'uid'=>$userId,
            'env'=>$request->request->get('env')
        ]; 
        \Drupal::logger('canto_connector')->notice('delete AccessToken'.$request->request->get('accessToken'));
        \Drupal::logger('canto_connector')->notice('env'.$request->request->get('env'));
/*         $env=$request->request->get('env');
        $accessToken=$request->request->get('accessToken');
        
        $database = \Drupal::database(); */
        $return_value = $this->repository->delete($entry);
        
        return new JsonResponse($return_value);

    }
    
 
    
}
 
