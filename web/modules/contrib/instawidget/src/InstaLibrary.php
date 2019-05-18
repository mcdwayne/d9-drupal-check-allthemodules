<?php
namespace Drupal\instawidget;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ListBooks.
 *
 * @package Drupal\instawidget
 */
class InstaLibrary {
    
    public function __construct(ConfigFactoryInterface $config_factory)
    {
     $instaconfig = $config_factory->get('config.instawidget_settingsconfig');
     $this->insta_client_id = $instaconfig->get('insta_client_id');
     $this->insta_redirect_uri = $instaconfig->get('insta_redirect_uri');
    }
    
    public function getInstaAcessToken() {
    $client_id = $this->insta_client_id;
    $insta_uri = $this->insta_redirect_uri;
    $insta_token_generator_url = "https://www.instagram.com/oauth/authorize/?client_id=$client_id&redirect_uri=$insta_uri&response_type=token&scope=public_content";
    return $insta_token_generator_url;
    }
    
    
}