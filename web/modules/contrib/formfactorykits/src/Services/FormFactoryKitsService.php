<?php

namespace Drupal\formfactorykits\Services;

use Drupal\formfactorykits\Kits;
use Drupal\kits\Services\KitsService;

/**
 * Interface FormFactoryKitsInterface
 *
 * @package Drupal\formfactorykits\Kits
 */
class FormFactoryKitsService extends KitsService implements FormFactoryKitsInterface {
  /**
   * @inheritdoc
   */
  public function button($id = NULL, $parameters = [], $context = []) {
    return Kits\Button\ButtonKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function checkbox($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Checkboxes\CheckboxKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function checkboxes($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Checkboxes\CheckboxesKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function color($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Color\ColorKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function container($id = NULL, $parameters = [], $context = []) {
    return Kits\Container\ContainerKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function date($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Date\DateKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function dateList($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Date\DateListKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function dateTime($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Date\DateTimeKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function details($id = NULL, $parameters = [], $context = []) {
    return Kits\Container\DetailsKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function email($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\EmailKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function file($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Media\ManagedFile\FileKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function fileUnmanaged($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Media\FileKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function heading($id = NULL, $parameters = [], $context = []) {
    return Kits\Markup\HeadingKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function hidden($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\HiddenKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function image($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Media\ManagedFile\ImageKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function imageButton($id = NULL, $parameters = [], $context = []) {
    return Kits\Button\ImageButtonKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function item($id = NULL, $parameters = [], $context = []) {
    return Kits\Markup\ItemKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function markup($id = NULL, $parameters = [], $context = []) {
    return Kits\Markup\MarkupKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function nodeAutoComplete($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Entity\NodeAutoCompleteKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function number($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\Number\NumberKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function password($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\PasswordKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function passwordConfirm($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\PasswordConfirmKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function path($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\PathKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function radios($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Radios\RadiosKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function range($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\Number\RangeKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function search($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\SearchKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function select($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Select\SelectKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function submit($id = NULL, $parameters = [], $context = []) {
    return Kits\Button\SubmitButtonKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function table($id = NULL, $parameters = [], $context = []) {
    return Kits\Container\Table\TableKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function tableRow($id = NULL, $parameters = [], $context = []) {
    return Kits\Container\Table\TableRowKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function tableSelect($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Table\TableSelectKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function telephone($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\Number\TelephoneKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function taxonomyTermSelect($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Entity\TaxonomyTermSelectKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function text($id = NULL, $parameters = [], $context = []) {
    return Kits\Markup\TextKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function textField($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\TextFieldKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function textArea($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\TextAreaKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function userAutoComplete($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Entity\UserAutoCompleteKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function url($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\Text\UrlKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function value($id = NULL, $parameters = [], $context = []) {
    return Kits\Field\ValueKit::create($this, $id, $parameters, $context);
  }

  /**
   * @inheritdoc
   */
  public function verticalTabs($id = NULL, $parameters = [], $context = []) {
    return Kits\Container\Tabs\VerticalTabsKit::create($this, $id, $parameters, $context);
  }
}
