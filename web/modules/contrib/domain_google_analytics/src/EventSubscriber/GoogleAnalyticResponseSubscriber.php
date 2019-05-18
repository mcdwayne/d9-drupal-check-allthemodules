<?php

namespace Drupal\multidomain_google_analytics\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Class GoogleAnalyticResponseSubscriber.
 *
 * @package Drupal\multidomain_google_analytics\EventSubscriber
 */
class GoogleAnalyticResponseSubscriber implements EventSubscriberInterface {

  /**
   * The config object for the multidomain_google_analytics settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The DomainNegotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $activeDomain;

  /**
   * Constructs a new Google Analytics response subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, DomainNegotiatorInterface $negotiator) {
    $this->config = $configFactory->get('multidomain_google_analytics.settings');
    if ($negotiator->getActiveDomain()) {
      $this->activeDomain = $negotiator->getActiveId();
    }
  }

  /**
   * Add a tags in boby.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *
   *   Set response.
   */
  public function addTag(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    $compact = '';
    if ($this->activeDomain) {
      $compact = $this->config->get($this->activeDomain);
    }

    // Insert snippet after the opening body tag.
    if ($compact) {
      $response_text = preg_replace('@<body[^>]*>@', '$0' . $this->getTag($compact), $response->getContent(), 1);
      $response->setContent($response_text);
    }
  }

  /**
   * Return the text for the tag.
   *
   * @param bool $compact
   *   Whether or not the tag should be compacted (whitespace removed).
   *
   * @return string
   *   The full text of the Google Analytic script/embed.
   */
  public function getTag($compact = FALSE) {
    // Build script tags.
    $script = [];
    $script = <<<EOS
      <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
        ga('create', '$compact', 'auto');
        ga('require', 'displayfeatures');
        ga('send', 'pageview');
      </script>
EOS;

    if ($compact) {
      $script = str_replace(["\n", '  '], '', $script);
    }
    $script = <<<EOS
    <!-- Google Analytics -->
    $script
    <!-- End Google Analytics -->
EOS;

    return $script;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['addTag', -500];
    return $events;
  }

}
