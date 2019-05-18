<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Aggregation Level tag.
 *
 * @MetatagTag(
 *   id = "agls_aggregationlevel",
 *   label = @Translation("Aggregation Level"),
 *   description = @Translation("The level of aggregation of the described resource. There are only two valid values for this property—'item' or 'collection'."),
 *   name = "AGLSTERMS.aggregationlevel",
 *   group = "agls",
 *   weight = 1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AggregationLevel extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = array()) {
    $form = parent::form($element);
    $form['#type'] = 'select';
    $values = ['item', 'collection'];
    $form['#options'] = array_combine($values, $values);
    $form['#empty_option'] = $this->t('None');

    return $form;
  }

}
