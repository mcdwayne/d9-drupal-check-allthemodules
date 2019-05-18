<?php

namespace Drupal\pagedesigner\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the Pagedesigner content formatter.
 *
 * @FieldFormatter(
 *   id = "pagedesigner_formatter",
 *   module = "pagedesigner",
 *   label = @Translation("Pagedesigner content"),
 *   field_types = {
 *     "pagedesigner_item"
 *   }
 * )
 */
class PagedesignerFormatter extends FormatterBase
{

    /**
     * {@inheritdoc}
     */
    public function settingsSummary()
    {
        $summary = [];
        $summary[] = $this->t('Displays the pagedesigner content.');
        return $summary;
    }

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $markup = '';
        // Get node from route
        $node = \Drupal::routeMatch()->getParameter('node');
        if ($node != null) {
            $renderer = \Drupal::service('pagedesigner.service.renderer');
            if (\Drupal::currentUser()->hasPermission('edit pagedesigner element entities') && isset($_GET['pd']) && $_GET['pd'] == 1) {
                $renderer->renderForEdit($node);
            } elseif (\Drupal::currentUser()->hasPermission('view unpublished pagedesigner element entities')) {
                $renderer->render($node);
            } else {
                $renderer->renderForPublic($node);
            }
            $markup = $renderer->getMarkup($node);
        }
        return $element[] = ['#markup' => $markup];
    }

}
