<?php

namespace Drupal\cision_feed\Feeds\Item;

use Drupal\feeds\Feeds\Item\BaseItem;

/**
 * Defines an item class for use with an Cision parser.
 */
class CisionFeedItem extends BaseItem {
  protected $guid;
  protected $title;
  protected $htmltitle;
  protected $header;
  protected $htmlheader;
  protected $created;
  protected $changed;
  protected $intro;
  protected $htmlintro;
  protected $body;
  protected $htmlbody;
  protected $countrycode;
  protected $languagecode;
  protected $isregulatory;
  protected $socialmediapitch;
  protected $type;
  protected $contact;
  protected $htmlcontact;
  protected $images;
  protected $files;
  protected $cisionwireurl;
  protected $syndicatedurl;
  protected $keywords;
  protected $categories;
  protected $companyinformation;
  protected $htmlcompanyinformation;
  protected $quickfacts;
  protected $logourl;
  protected $quotes;
  protected $externallinks;
  protected $embeddeditems;
  protected $videos;
  protected $tid;

}
