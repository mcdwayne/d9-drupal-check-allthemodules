<?php

namespace Drupal\system_tags\TwigExtension;

use Drupal\system_tags\SystemTagFinder\SystemTagFinderManagerInterface;

/**
 * Class SystemTagsTwigExtension.
 */
class SystemTagsTwigExtension extends \Twig_Extension {

  /**
   * The system tag finder manager.
   *
   * @var \Drupal\system_tags\SystemTagFinder\SystemTagFinderManager
   */
  protected $systemTagFinderManager;

  /**
   * Constructs a new SystemTagTwigExtension object.
   *
   * @param \Drupal\system_tags\SystemTagFinder\SystemTagFinderManagerInterface $system_tag_finder_manager
   *   The system tag finder manager.
   */
  public function __construct(SystemTagFinderManagerInterface $system_tag_finder_manager) {
    $this->systemTagFinderManager = $system_tag_finder_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'system_tags.twig_extension';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('system_tag_url', [$this, 'getSystemTagUrl']),
    ];
  }

  /**
   * Renders the url of a tagged entity.
   *
   * @param string $systemTagId
   *   The system tag id.
   * @param string $entityTypeId
   *   The entity type id.
   * @param array $options
   *   See \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *   the available options.
   *
   * @return string
   *   The url of the tagged entity or '#'.
   */
  public function getSystemTagUrl($systemTagId, $entityTypeId = 'node', array $options = []) {
    /** @var \Drupal\system_tags\SystemTagFinder\SystemTagFinderInterface $systemTagFinder */
    $systemTagFinder = $this->systemTagFinderManager->getInstance(['entity_type' => $entityTypeId]);

    if ($entity = $systemTagFinder->findOneByTag($systemTagId)) {
      return $entity->toUrl('canonical', $options);
    }

    return '#';
  }

}
