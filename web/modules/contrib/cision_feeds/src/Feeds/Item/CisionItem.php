<?php

namespace Drupal\cision_feeds\Feeds\Item;
use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * Defines an item class for use with an Cision parser.
 */
class CisionItem extends BaseItem {

  protected $title;
  protected $guid;
  protected $langCommonGuid;
  protected $PublishDateUtc;
  protected $LastChangeDateUtc;
  protected $LanguageCode;
  protected $Intro;
  protected $Body;
  protected $SyndicatedUrl;
  protected $Images;
  protected $Categories;
  protected $Keywords;
  protected $Files;

}
