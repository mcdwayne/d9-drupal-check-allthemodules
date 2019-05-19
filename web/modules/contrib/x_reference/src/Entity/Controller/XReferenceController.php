<?php

namespace Drupal\x_reference\Entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Returns responses for x-reference routes.
 */
class XReferenceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a XReferenceController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays add content links for available content types.
   *
   * Redirects to x_reference/add/[type] if only one content type is available.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array for a list of the x_reference types that can be added; however,
   *   if there is only one x_reference type defined for the site, the function
   *   will return a RedirectResponse to the x_reference add page for that one x_reference
   *   type.
   */
  public function addPage() {
    $build = [
      '#theme' => 'x_reference_add_list',
      '#cache' => [
        'tags' => $this->entityManager()->getDefinition('x_reference_type')->getListCacheTags(),
      ],
    ];

    $content = array();

    // Only use x_reference types the user has access to.
    foreach ($this->entityManager()->getStorage('x_reference_type')->loadMultiple() as $type) {
      $access = $this->entityManager()->getAccessControlHandler('x_reference')->createAccess($type->id(), NULL, [], TRUE);
      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the x_reference/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('x_reference.x_reference_add', array('x_reference_type' => $type->id()));
    }

    $build['#content'] = $content;

    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * @param ConfigEntityInterface $x_reference_type
   *
   * @return array
   */
  public function add(ConfigEntityInterface $x_reference_type) {
    $x_reference = $this->entityManager()->getStorage('x_reference')->create([
      'type' => $x_reference_type->id(),
    ]);

    return $this->entityFormBuilder()->getForm($x_reference);
  }

  /**
   * {@inheritdoc}
   *
   * @param ConfigEntityInterface $x_reference_type
   *
   * @return TranslatableMarkup
   */
  public function addPageTitle(ConfigEntityInterface $x_reference_type) {
    return $this->t('Create @name', ['@name' => $x_reference_type->label()]);
  }

}
