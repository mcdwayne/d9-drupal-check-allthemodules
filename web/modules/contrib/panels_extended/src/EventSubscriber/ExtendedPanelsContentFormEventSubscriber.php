<?php

namespace Drupal\panels_extended\EventSubscriber;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ctools\Form\AjaxFormTrait;
use Drupal\page_manager\PageInterface;
use Drupal\panels_extended\BlockConfig\AdminInfoInterface;
use Drupal\panels_extended\BlockConfig\VisibilityInterface;
use Drupal\panels_extended\Event\ExtendedPanelsContentFormEvent;
use Drupal\panels_extended\Form\PanelsScheduleBlockForm;
use Drupal\panels_extended\Plugin\DisplayBuilder\ExtendedDisplayBuilder;
use Drupal\panels_extended\Plugin\PanelsPattern\ExtendedPanelsPatternInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Improves the panels content form for variant 'panels_extended'.
 *
 * The improvements are done in 2 events so events can be added which
 * stop propagation to prevent these adjustments.
 *
 * Improvements:
 * - Add browsing tokens for page title.
 * - Fix default region when adding blocks.
 * - Visual improvements:
 *   - Block title more prominent and indication if visible.
 *   - Block disabled or not visible.
 *   - Show block configurations on form by implementing AdminInfoInterface.
 * - Add links to schedule, disable/enable a block.
 */
class ExtendedPanelsContentFormEventSubscriber implements EventSubscriberInterface {

  use AjaxFormTrait;
  use StringTranslationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExtendedPanelsContentFormEvent::FORM_ALTER][] = ['alterBaseForm', -1];
    $events[ExtendedPanelsContentFormEvent::FORM_ALTER][] = ['alterBlockTableForm', -10];
    return $events;
  }

  /**
   * Some basic changes to the panels form.
   *
   * Current changes:
   * - Allow browsing of tokens for page title.
   * - Fix add block url to use the default region.
   *
   * @param \Drupal\panels_extended\Event\ExtendedPanelsContentFormEvent $event
   *   The event.
   */
  public function alterBaseForm(ExtendedPanelsContentFormEvent $event) {
    $form = &$event->getForm();
    $formState = $event->getFormState();

    $cachedValues = $formState->getTemporaryValue('wizard');

    // Allow browsing the available tokens for page title.
    if ($cachedValues['page'] instanceof PageInterface) {
      $contexts = $cachedValues['page']->getContexts();
      if (!empty($contexts)) {
        $form['page_title']['#description'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => $this->getContextAsTokenData($contexts),
        ];
      }
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variantPlugin */
    $variantPlugin = $cachedValues['plugin'];

    // Fix the add block url so it uses the default region.
    if (!empty($form['add']['#url'])) {
      /** @var \Drupal\Core\Url $url */
      $url = $form['add']['#url'];
      $url->setRouteParameter('region', $variantPlugin->getLayout()->getPluginDefinition()->getDefaultRegion());
    }
  }

  /**
   * Returns available context as token data.
   *
   * BASED ON: PageBlockDisplayVariant::getContextAsTokenData.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   List of contexts.
   *
   * @return array
   *   An array with token data values keyed by token type.
   */
  protected function getContextAsTokenData(array $contexts) {
    $data = [];
    foreach ($contexts as $context) {
      // @todo Simplify this when token and typed data types are unified in
      //   https://drupal.org/node/2163027.
      if (strpos($context->getContextDefinition()->getDataType(), 'entity:') === 0) {
        $token_type = substr($context->getContextDefinition()->getDataType(), 7);
        if ($token_type == 'taxonomy_term') {
          $token_type = 'term';
        }

        try {
          $data[$token_type] = $context->getContextValue();
        }
        catch (ContextException $ce) {
          $contextValue = NULL;
        }
      }
    }
    return $data;
  }

  /**
   * Alter the table with the blocks on the panels content form.
   *
   * @param \Drupal\panels_extended\Event\ExtendedPanelsContentFormEvent $event
   *   The event.
   */
  public function alterBlockTableForm(ExtendedPanelsContentFormEvent $event) {
    $form = &$event->getForm();
    $formState = $event->getFormState();

    $form['#attached']['library'][] = 'panels_extended/panels_form';

    $cachedValues = $formState->getTemporaryValue('wizard');

    $tempstoreId = $formState->getFormObject()->getTempstoreId();

    if (($key = array_search($this->t('Plugin ID'), $form['blocks']['#header'])) !== FALSE) {
      unset($form['blocks']['#header'][$key]);
    }

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variantPlugin */
    $variantPlugin = $cachedValues['plugin'];

    if ($regionAssignments = $variantPlugin->getRegionAssignments()) {
      $pattern = $variantPlugin->getPattern();
      $builder = $variantPlugin->getBuilder();

      foreach ($regionAssignments as $blocks) {
        /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
        foreach ($blocks as $block_id => $block) {
          $row = &$form['blocks'][$block_id];
          unset($row['id']);

          $blockConfiguration = $block->getConfiguration();

          $isDisabled = !empty($blockConfiguration[ExtendedDisplayBuilder::BLOCK_CONFIG_DISABLED]);
          $isUnscheduled = $builder instanceof ExtendedDisplayBuilder && !$builder->blockIsScheduled($blockConfiguration);
          $isVisible = !$block instanceof VisibilityInterface || $block->isVisible();
          $notVisibleReason = $isVisible ? NULL : $block->getNotVisibleReason();
          if ($isDisabled) {
            $row['#attributes']['class'][] = 'block-disabled';
          }
          elseif ($isUnscheduled) {
            $row['#attributes']['class'][] = 'block-not-visible';
          }
          elseif (!$isVisible) {
            $row['#attributes']['class'][] = 'block-not-visible';
          }

          // Increase block delta to prevent issues with reordering a lot of blocks.
          $row['weight']['#delta'] = 99;

          $row['label'] = [
            '#theme' => 'panels_extended_content_form_block_info',
            '#title' => $row['label']['#markup'],
            '#title_visible' => $blockConfiguration['label_display'] === BlockPluginInterface::BLOCK_LABEL_VISIBLE,
            '#plugin_id' => $block->getPluginId(),
            '#schedule_start' => !empty($blockConfiguration[PanelsScheduleBlockForm::CFG_START]) ? $blockConfiguration[PanelsScheduleBlockForm::CFG_START] : NULL,
            '#schedule_end' => !empty($blockConfiguration[PanelsScheduleBlockForm::CFG_END]) ? $blockConfiguration[PanelsScheduleBlockForm::CFG_END] : NULL,
            '#disabled' => $isDisabled,
            '#unscheduled' => $isUnscheduled,
            '#visible' => $isVisible,
            '#notVisibleReason' => $notVisibleReason,
            '#primary_info' => ($block instanceof AdminInfoInterface) ? $block->getAdminPrimaryInfo() : NULL,
            '#secondary_info' => ($block instanceof AdminInfoInterface) ? $block->getAdminSecondaryInfo() : [],
          ];

          if ($pattern instanceof ExtendedPanelsPatternInterface) {
            $machine_name = $pattern->getMachineName($cachedValues);
            $destination = $this->requestStack->getCurrentRequest()->getRequestUri();
            $row['operations']['#links'][] = [
              'title' => $this->t('Schedule'),
              'url' => $pattern->getBlockScheduleUrl($tempstoreId, $machine_name, $block_id, $destination),
              'attributes' => $this->getAjaxAttributes(),
            ];
            if ($isDisabled) {
              $row['operations']['#links'][] = [
                'title' => $this->t('Enable'),
                'url' => $pattern->getBlockEnableUrl($tempstoreId, $machine_name, $block_id, $destination),
              ];
            }
            else {
              $row['operations']['#links'][] = [
                'title' => $this->t('Disable'),
                'url' => $pattern->getBlockDisableUrl($tempstoreId, $machine_name, $block_id, $destination),
              ];
            }
          }
        }
      }
    }
  }

}
