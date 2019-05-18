<?php

namespace Drupal\embederator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Misc global utilities.
 */
class EmbederatorUtilities {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Token handling.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Token $token) {
    $this->entityTypeManager = $entity_type_manager;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('token')
    );
  }

  /**
   * Append a preview element to the form.
   */
  public function addFormPreview(&$form, $entity, $embed_pattern, $ssi = FALSE) {
    if ($embed_pattern && $entity) {
      // Add token identifier.
      foreach (Element::children($form) as $key) {
        $form[$key]['#attributes']['data-embederator-token'] = '[embederator:' . $key . ']';
      }

      $label = $ssi ? $this->t('Embed URL') : $this->t('Embed markup');
      $dom_id = "embederator_" . $entity->id() . '__preview';
      $form['preview'] = [
        '#type' => 'fieldset',
        '#title' => $label,
        '#attributes' => [
          'class' => [
            'embederator__preview-wrapper',
          ],
        ],
        'html' => [
          '#markup' => '<div id="' . $dom_id . '" class="embederator__preview"><div class="embederator__preview--unhighlighted">' . Html::escape($embed_pattern) . '</div></div>',
        ],
        '#weight' => 999,
      ];

      $form['#attributes']['class'][] = 'embederator-token-form';
      $form['#attached']['library'][] = 'embederator/preview';
    }
  }

  /**
   * Append parsing features.
   */
  public function addFormParser(&$form, $embed_pattern, $ssi = FALSE) {
    if ($embed_pattern) {
      $show_tokens = $ssi ? $this->t('Toggle to: show tokens in URL') : $this->t('Toggle to: show tokens in embed');
      $show_paste = $ssi ? $this->t('Toggle to: parse pasted embed URL') : $this->t('Toggle to: parse pasted embed code');
      $form['preview']['parse'] = [
        '#weight' => -99,
        '#type' => 'container',
        'paste_launch' => [
          '#type' => 'markup',
          '#markup' => '<a class="embederator__paste-launch" href="#" data-show-paste="' . $show_paste . '" data-show-tokens="' . $show_tokens . '">' . $show_paste . '</a>',
        ],
        'paste_box' => [
          '#type' => $ssi ? 'textfield' : 'textarea',
          '#wrapper_attributes' => [
            'class' => [
              'embederator__hidden',
              'embederator__paste-box',
            ],
          ],
          '#attributes' => [
            'placeholder' => $embed_pattern,
          ],
          '#title' => $ssi ? $this->t('Paste URL') : $this->t('Paste embed'),
          '#description' => $ssi ? $this->t('Embederator will attempt to parse tokens out of a pasted URL.') : $this->t('Embederator will attempt to parse tokens out of a pasted embed.'),
        ],
      ];
      $form['#attached']['library'][] = 'embederator/parse';
    }
  }

  /**
   * Add form helpers.
   */
  public function customizeForm(&$form, FormStateInterface $form_state) {
    list($entity, $bundle_id) = $this->getEntityConfig($form, $form_state);
    $bundle_config = $this->entityTypeManager->getStorage('embederator_type')->load($bundle_id);

    $embed_pattern = $this->getPreview($bundle_config);

    $form['preview'] = [];
    $this->addFormPreview($form, $entity, $embed_pattern, $bundle_config->getUseSsi());
    $this->addFormParser($form, $embed_pattern, $bundle_config->getUseSsi());
  }

  /**
   * Get the preview markup from current type.
   */
  public function getPreview($bundle_config) {
    if ($bundle_config) {
      $markup = $bundle_config->getUseSsi() ? $bundle_config->getEmbedUrl() : $bundle_config->getMarkupHtml();
      return $markup;
    }
    return NULL;
  }

  /**
   * Return entity and bundle_id.
   */
  public function getEntityConfig($form, $form_state) {
    // IEF form.
    if (isset($form['#bundle'])) {
      $bundle_id = $form['#bundle'];
      if ($form['#default_value']) {
        $entity = $form['#default_value'];
      }
      else {
        $entity_type = $form['#entity_type'];
        $entity = $this->entityTypeManager->getStorage($entity_type)->create(['type' => $bundle_id]);
      }
    }
    // Regular edit form.
    else {
      $entity = $form_state->getFormObject()->getEntity();
      $bundle_id = $entity->bundle();
    }
    return [$entity, $bundle_id];
  }

}
