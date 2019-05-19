<?php

/**
 * @file
 * Contains \Drupal\book\Controller\BookController.
 */

namespace Drupal\subsite\Controller;

use Drupal\subsite\SubsiteManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller routines for subsite routes.
 */
class SubsiteController extends ControllerBase {

  /**
   * The subsite manager.
   *
   * @var \Drupal\subsite\SubsiteManagerInterface
   */
  protected $subsiteManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a SubsiteController object.
   *
   * @param \Drupal\subsite\SubsiteManagerInterface $subsiteManager
   *   The subsite manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(SubsiteManagerInterface $subsiteManager, RendererInterface $renderer) {
    $this->subsiteManager = $subsiteManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('subsite.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Returns an administrative overview of all books.
   *
   * @return array
   *   A render array representing the administrative page content.
   *
   */
  public function adminOverview() {
    return array(
      '#markup' => t('Nothing to see here.'),
    );
//    $rows = array();
//
//    $headers = array(t('Book'), t('Operations'));
//    // Add any recognized books to the table list.
//    foreach ($this->bookManager->getAllBooks() as $book) {
//      /** @var \Drupal\Core\Url $url */
//      $url = $book['url'];
//      if (isset($book['options'])) {
//        $url->setOptions($book['options']);
//      }
//      $row = array(
//        $this->l($book['title'], $url),
//      );
//      $links = array();
//      $links['edit'] = array(
//        'title' => t('Edit order and titles'),
//        'url' => Url::fromRoute('book.admin_edit', ['node' => $book['nid']]),
//      );
//      $row[] = array(
//        'data' => array(
//          '#type' => 'operations',
//          '#links' => $links,
//        ),
//      );
//      $rows[] = $row;
//    }
//    return array(
//      '#type' => 'table',
//      '#header' => $headers,
//      '#rows' => $rows,
//      '#empty' => t('No books available.'),
//    );
  }

  /**
   * Prints a listing of all books.
   *
   * @return array
   *   A render array representing the listing of all books content.
   */
  public function bookRender() {
    $book_list = array();
    foreach ($this->bookManager->getAllBooks() as $book) {
      $book_list[] = $this->l($book['title'], $book['url']);
    }
    return array(
      '#theme' => 'item_list',
      '#items' => $book_list,
      '#cache' => [
        'tags' => \Drupal::entityManager()->getDefinition('node')->getListCacheTags(),
      ],
    );
  }

  /**
   * Generates representations of a book page and its children.
   *
   * The method delegates the generation of output to helper methods. The method
   * name is derived by prepending 'bookExport' to the camelized form of given
   * output type. For example, a type of 'html' results in a call to the method
   * bookExportHtml().
   *
   * @param string $type
   *   A string encoding the type of output requested. The following types are
   *   currently supported in book module:
   *   - html: Printer-friendly HTML.
   *   Other types may be supported in contributed modules.
   * @param \Drupal\node\NodeInterface $node
   *   The node to export.
   *
   * @return array
   *   A render array representing the node and its children in the book
   *   hierarchy in a format determined by the $type parameter.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function bookExport($type, NodeInterface $node) {
    $method = 'bookExport' . Container::camelize($type);

    // @todo Convert the custom export functionality to serializer.
    if (!method_exists($this->bookExport, $method)) {
      drupal_set_message(t('Unknown export format.'));
      throw new NotFoundHttpException();
    }

    $exported_book = $this->bookExport->{$method}($node);
    return new Response($this->renderer->renderRoot($exported_book));
  }

}
