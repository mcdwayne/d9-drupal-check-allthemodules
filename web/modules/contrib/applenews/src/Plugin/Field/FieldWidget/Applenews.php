<?php

namespace Drupal\applenews\Plugin\Field\FieldWidget;

use Drupal\applenews\ApplenewsManager;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Provides a default applenews widget.
 *
 * @FieldWidget(
 *   id = "applenews_default",
 *   label = @Translation("Apple News"),
 *   field_types = {
 *     "applenews_default"
 *   }
 * )
 */
class Applenews extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $items->getName();
    $default_channels = unserialize($items[$delta]->channels);
    $channels = $this->getChannels();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $items->getEntity();
    $element['#attached']['library'][] = 'applenews/drupal.applenews.admin';
    $templates = $this->getTemplates($entity);
    $article = ApplenewsManager::getArticle($entity, $field_name);

    if (empty($channels)) {
      $element['message'] = [
        '#markup' => $this->t('There are no channels available. To set up a channel, review the <a href=":url">Apple news Settings</a>.', [':url' => Url::fromRoute('entity.applenews_template.collection')->toString()]),
      ];
    }
    elseif (!$templates) {
      $element['message'] = [
        '#markup' => $this->t('Add a template to %type type. Check Apple news Template <a href=":url">configuration</a> page.', ['%type' => $entity->bundle(), ':url' => Url::fromRoute('entity.applenews_template.collection')->toString()]),
      ];
    }
    else {
      $element += [
        '#element_validate' => [[get_class($this), 'validateFormElement']],
      ];
      $element['status'] = [
        '#type' => 'checkbox',
        '#title' => t('Publish to Apple News'),
        '#default_value' => $items->status,
        '#attributes' => [
          'class' => ['applenews-publish-flag'],
        ],
      ];
      if ($article) {
        $element['article'] = [
          '#type' => 'value',
          '#value' => $article,
        ];
        $element['created'] = [
          '#type' => 'item',
          '#title' => $this->t('Apple News post date'),
          '#markup' => $article->getCreatedFormatted(),
        ];
        $element['share_url'] = [
          '#type' => 'item',
          '#title' => $this->t('Share URL'),
          '#markup' => $this->t('<a href=":url">:url</a>', [':url' => $article->getShareUrl()]),
        ];
        $delete_url = Url::fromRoute('applenews.remote.article_delete', ['entity_type' => $entity->getEntityTypeId(), 'entity' => $entity->id()]);
        $element['delete'] = [
          '#type' => 'item',
          '#title' => $this->t('Delete'),
          '#markup' => $this->t('<a href=":url">Delete</a> this article from Apple News.', [':url' => $delete_url->toString()]),
        ];
      }
      $element['template'] = [
        '#type' => 'select',
        '#title' => t('Template'),
        '#default_value' => $items->template,
        '#options' => $templates,
        '#description' => $this->t('Select template to use for Applenews'),
        '#states' => [
          'visible' => [
            ':input[name="' . $items->getName() . '[' . $delta . '][status]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $element['channels'] = [
        '#type' => 'container',
        '#title' => $this->t('Default channels and sections'),
        '#group' => 'fieldset',
        '#states' => [
          'visible' => [
            ':input[name="' . $items->getName() . '[' . $delta . '][status]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      // $default_channels = $items->get('channels')[0]->getValue();
      foreach ($this->getChannels() as $channel) {
        /** @var \Drupal\applenews\Entity\ApplenewsChannel $channel */
        $channel_key = $channel->getChannelId();
        $element['channels'][$channel_key] = [
          '#type' => 'checkbox',
          '#title' => $channel->getName(),
          '#default_value' => isset($default_channels[$channel_key]),
          '#attributes' => [
            'data-channel-id' => $channel_key,
          ],
          '#states' => [
            'visible' => [
              ':input[name="' . $items->getName() . '[' . $delta . '][status]"]' => ['checked' => TRUE],
            ],
            'checked' => [
              ':input[data-section-of="' . $channel_key . '"]' => ['checked' => TRUE],
            ],
          ],
        ];
        foreach ($channel->getSections() as $section_id => $section_label) {
          $section_key = $channel_key . '-section-' . $section_id;
          $element['sections'][$section_key] = [
            '#type' => 'checkbox',
            '#title' => $section_label,
            '#default_value' => isset($default_channels[$channel_key][$section_id]),
            '#attributes' => [
              'data-section-of' => $channel_key,
              'class' => ['applenews-sections'],
            ],
            '#states' => [
              'visible' => [
                ':input[name="' . $items->getName() . '[' . $delta . '][status]"]' => ['checked' => TRUE],
              ],
            ],
          ];
        }
      }
      $element['is_preview'] = [
        '#title' => $this->t('<strong>Content visibility</strong>: Exported articles will be visible to members of my channel only.'),
        '#type' => 'checkbox',
        '#default_value' => $items->is_preview,
        '#description' => $this->t('Indicates whether this article should be public (live) or should be a preview that is only visible to members of your channel. Uncheck this to publish the article right away and make it visible to all News users. <br/><strong>Note:</strong>  If your channel has not yet been approved to publish articles in Apple News Format, unchecking this option will result in an error.'),
        '#weight' => 1,
        '#states' => [
          'visible' => [
            ':input[name="' . $items->getName() . '[' . $delta . '][status]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      if ($article && extension_loaded('zip')) {
        $url_preview = Url::fromRoute('applenews.preview_download', [
          'entity_type' => $entity->getEntityTypeId(),
          'entity' => $entity->id(),
          'revision_id' => $entity->getLoadedRevisionId(),
          'template_id' => $items->template,
        ]);
        $element['preview'] = [
          '#type' => 'item',
          '#title' => $this->t('Preview'),

          // @todo: Fix route, to support other than node.
          '#markup' => $this->t('<a href=":url">Download</a> the Apple News generated document (use the News Preview app to preview the article).', [':url' => $url_preview->toString()]),
        ];

      }
    }
    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      // Override widget title to be helpful for end users.
      $element['#title'] = $this->t('Applenews settings');

      $element += [
        '#type' => 'details',
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['applenews-' . Html::getClass($entity->getEntityTypeId()) . '-settings-form'],
        ],
      ];
    }

    return $element;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array $element, FormStateInterface $form_state) {
    $status = $element['status']['#value'];
    if ($status) {
      // If status exist, at least one channel should be selected.
      $has_channel = FALSE;
      $has_section = FALSE;
      foreach (Element::children($element['channels']) as $key) {
        if ($element['channels'][$key]['#value']) {
          $has_channel = TRUE;
        }
      }
      // If a channel selected, at least one section should selected.
      foreach (Element::children($element['sections']) as $key) {
        if ($element['sections'][$key]['#value']) {
          $has_section = TRUE;
        }
      }

      // Show consolidated message, if no channel AND sections selected.
      if (!$has_channel && !$has_section) {
        $form_state->setError($element['channels'], t('Apple News: At least one channel and a section should be selected to publish.'));
      }
      elseif (!$has_channel) {
        $form_state->setError($element['channels'], t('Apple News: At least one channel should be selected to publish.'));
      }
      elseif (!$has_section) {
        $form_state->setError($element['sections'], t('Apple News: At least one section should be selected to publish.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $enabled = $this->getSetting('status');
    if ($enabled) {
      $summary[] = t('Template: !template', ['!template' => $this->getSetting('template')]);
    }

    return $summary;
  }

  /**
   * Generate channel options.
   *
   * @return array
   *   An array of channel indexed by id.
   */
  protected function getChannels() {
    $channels = [];

    try {
      $storage = \Drupal::entityTypeManager()->getStorage('applenews_channel');
      $entity_ids = $storage->getQuery()->execute();
      $channels = $storage->loadMultiple($entity_ids);
    }
    catch (\Exception $e) {
      $this->logger()->error('Error loading channel: %code : %message', ['%code' => $e->getCode(), $e->getMessage()]);
    }

    return $channels;
  }

  /**
   * Generate template options.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return array
   *   An array of templates indexed by id.
   */
  protected function getTemplates(EntityInterface $entity) {
    $templates = [];

    try {
      $storage = \Drupal::entityTypeManager()->getStorage('applenews_template');
      $entity_ids = $storage->getQuery()
        ->condition('node_type', $entity->bundle())
        ->execute();
      $entities = $storage->loadMultiple($entity_ids);
      foreach ($entities as $entity) {
        $templates[$entity->id()] = $entity->label();
      }
    }
    catch (\Exception $e) {
      $this->logger()->error('Error loading templates: %code : %message', ['%code' => $e->getCode(), $e->getMessage()]);
    }

    return $templates;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Update channels and sections structure for storage and API call.
    foreach ($values as &$value) {
      $value += [
        'status' => FALSE,
        'template' => '',
        'channels' => '',
        'is_preview' => TRUE,
      ];
      $result = [];
      $channels = array_keys(array_filter($value['channels']));
      $sections = array_keys(array_filter($value['sections']));
      foreach ($channels as $channel_id) {
        foreach ($sections as $section_id) {
          if (strpos($section_id, $channel_id) === 0) {
            $section_id_result = substr($section_id, strlen($channel_id . '-section-'));
            $result[$channel_id][$section_id_result] = 1;
          }
        }
      }
      $value['channels'] = serialize($result);
      unset($value['sections']);
    }
    return $values;
  }

  /**
   * Logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   Logger object.
   */
  protected function logger() {
    return \Drupal::logger('applenews');
  }

}
