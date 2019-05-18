<?php

namespace Drupal\paragraphs_entity_embed\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Controller that handle the CKEditor embed form for paragraphs.
 */
class ParagraphsEntityEmbedController extends ControllerBase {

  /**
   * The embedded paragraphs storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $embeddedParagraphsStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('embedded_paragraphs')
    );
  }

  /**
   * Constructs a EmbeddedParagraphs object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $embedded_paragraphs_storage
   *   The custom embedded paragraphs storage.
   */
  public function __construct(EntityStorageInterface $embedded_paragraphs_storage) {
    $this->embeddedParagraphsStorage = $embedded_paragraphs_storage;
  }

  /**
   * Presents the embedded paragraphs creation form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\editor\EditorInterface|null $editor
   *   The WYSIWYG editor.
   * @param \Drupal\embed\EmbedButtonInterface|null $embed_button
   *   The embed button.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(
  Request $request,
  EditorInterface $editor = NULL,
  EmbedButtonInterface $embed_button = NULL) {
    if (($return_html = $this->controllerCalledOutsideIframe($request))) {
      return $return_html;
    }

    $embedded_paragraphs = $this->embeddedParagraphsStorage->create([]);

    $form_state['editorParams'] = [
      'editor' => $editor,
      'embed_button' => $embed_button,
    ];

    return $this->entityFormBuilder()
      ->getForm($embedded_paragraphs, 'paragraphs_entity_embed', $form_state);
  }

  /**
   * Presents the embedded paragraphs update form.
   *
   * @param string $embedded_paragraphs_uuid
   *   The UUID of Embedded paragraphs we are going to edit via CKE modal form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param \Drupal\editor\EditorInterface|null $editor
   *   The WYSIWYG editor.
   * @param \Drupal\embed\EmbedButtonInterface|null $embed_button
   *   The embed button.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function editForm(
  $embedded_paragraphs_uuid,
  Request $request,
  EditorInterface $editor = NULL,
  EmbedButtonInterface $embed_button = NULL) {
    if (($return_html = $this->controllerCalledOutsideIframe($request))) {
      return $return_html;
    }

    $entity = $this->embeddedParagraphsStorage
      ->loadByProperties(['uuid' => $embedded_paragraphs_uuid]);
    $embedded_paragraph = current($entity);

    $form_state['editorParams'] = [
      'editor' => $editor,
      'embed_button' => $embed_button,
    ];

    return $this->entityFormBuilder()
      ->getForm($embedded_paragraph, 'paragraphs_entity_embed', $form_state);
  }

  /**
   * Checks whether the current request is performed inside iframe.
   *
   * If its inside iframe nothing is returned, otherwise we return html markup
   * for showing iframe.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array|null
   *   Array of the response data or nothing if the controller is called inside
   *   iframe.
   */
  private function controllerCalledOutsideIframe(Request $request) {
    if (!$request->query->get('paragraphs_entity_embed_inside_iframe')) {
      $parsed_url = UrlHelper::parse($request->getRequestUri());
      if (isset($parsed_url['query']['_wrapper_format'])) {
        unset($parsed_url['query']['_wrapper_format']);
      }
      $parsed_url['query']['paragraphs_entity_embed_inside_iframe'] = 1;

      $iframe_source = $parsed_url['path'] . '?' . UrlHelper::buildQuery($parsed_url['query']);

      return [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'src' => $iframe_source,
          'width' => '480',
          'height' => '450',
          'frameBorder' => 0,
        ],
        '#attached' => ['library' => ['editor/drupal.editor.dialog']],
      ];
    }
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $results = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      try {
        $query = db_select('embedded_paragraphs', 'ep');
        $result = $query->fields('ep', ['uuid', 'label'])
          ->condition('ep.label', '%' . $query->escapeLike($typed_string) . '%', 'LIKE')
          ->orderBy('label')
          ->execute()
          ->fetchAll();
      }
      catch (Exception $e) {
        var_dump($e->getMessage());
      }
      foreach ($result as $item) {
        $results[] = [
          'value' => $item->uuid,
          'label' => $item->label,
        ];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Returns a page title.
   *
   * @param \Drupal\embed\EmbedButtonInterface|null $embed_button
   *   The embed button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function getEditTitle(EmbedButtonInterface $embed_button = NULL) {
    return  $this->t('Edit %title', ['%title' => $embed_button->label()]);
  }

  /**
   * Returns a page title.
   *
   * @param \Drupal\embed\EmbedButtonInterface|null $embed_button
   *   The embed button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title.
   */
  public function getAddTitle(EmbedButtonInterface $embed_button = NULL) {
    return  $this->t('Select %title to Embed', ['%title' => $embed_button->label()]);
  }

}
