<?php

namespace Drupal\contact_tools\Plugin\Filter;

use DOMDocument;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contact tools filter.
 *
 * @Filter(
 *   id = "contact_tools_modal_link",
 *   title = @Translation("Contact Tools modal links"),
 *   description = @Translation("Attach Modal API to links with href='/contact-tools/CONTACT_FORM'."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class ModalLinkFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ModalLinkFilter constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RendererInterface $renderer, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $dom = new DOMDocument(NULL, 'UTF-8');
    $dom->encoding = 'UTF-8';
    @$dom->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
    $links = $dom->getElementsByTagName('a');

    foreach ($links as $link) {
      $href = $link->getAttribute('href');
      if (preg_match('/\/contact-tools\//s', $href)) {
        // Attach library.
        $attached = ['#attached' => ['library' => ['core/drupal.dialog.ajax']]];
        $this->renderer->render($attached);

        $classes = $link->getAttribute('class');
        if (!preg_match('/use-ajax/', $classes)) {
          $classes .= ' use-ajax';
          $link->setAttribute('class', $classes);
        }

        // Check if set dialog type. If not, set to modal as default.
        if (!$link->getAttribute('data-dialog-type')) {
          $link->setAttribute('data-dialog-type', 'modal');
        }

        // Manage data-dialog-options.
        $data_dialog_options = $link->getAttribute('data-dialog-options');
        if ($data_dialog_options) {
          $dialog_options = Json::decode($data_dialog_options);
          if (empty($dialog_options['width'])) {
            $dialog_options['width'] = 500;
          }
          if (empty($dialog_options['dialogClass'])) {
            $dialog_options['dialogClass'] = 'contact-tools-modal';
          }
        }
        else {
          $dialog_options = [
            'width' => 'auto',
            'dialogClass' => 'contact-tools-modal',
          ];
        }

        $context = [
          'type' => 'filter_link',
        ];

        $this->moduleHandler->alter('contact_tools_modal_link_options', $dialog_options, $context);
        $link->setAttribute('data-dialog-options', Json::encode($dialog_options));
      }
    }
    // DOMDocument is always adds <!DOCTYPE> and <html><body> tags. This is
    // invalidate the whole page.
    $text = preg_replace('~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i', '', $dom->saveHTML());
    $result->setProcessedText($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Attach Modal API to links with href="/contact-tools/CONTACT_FORM".');
  }

}
