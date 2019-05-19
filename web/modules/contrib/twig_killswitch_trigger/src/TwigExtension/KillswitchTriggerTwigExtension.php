<?php

namespace Drupal\twig_killswitch_trigger\TwigExtension;

use Drupal\Core\Render\RendererInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;

/**
 * Class KillswitchTriggerTwigExtension.
 */
class KillswitchTriggerTwigExtension extends \Twig_Extension {

    
   /**
    * Drupal\Core\PageCache\ResponsePolicyInterface definition.
    *
    * @var ResponsePolicyInterface
    */
    protected $pageCacheKillSwitch;

   /**
    * Constructs a new KillswitchTriggerTwigExtension object.
    */
    public function __construct(RendererInterface $renderer, ResponsePolicyInterface $page_cache_kill_switch) {
        $this->pageCacheKillSwitch = $page_cache_kill_switch;
    }
    
   /**
    * {@inheritdoc}
    */
    public function getTokenParsers() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getNodeVisitors() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getFilters() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getTests() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getFunctions() {
      return [
        new \Twig_SimpleFunction('killswitchTrigger', [$this, 'killswitchTrigger']),        
      ];
    }

   /**
    * {@inheritdoc}
    */
    public function getOperators() {
      return [];
    }

   /**
    * {@inheritdoc}
    */
    public function getName() {
      return 'twig_killswitch_trigger.twig.extension';
    }


    /**
     * Disables internal page cache for the current request.
     */
    public function killswitchTrigger() {
      $this->pageCacheKillSwitch->trigger();
    }    

}
