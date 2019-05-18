<?php

namespace Drupal\partnersite_profile\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;


/**
 * Defines an interface for Access Link generator plugins.
 */
interface LinkGeneratorInterface extends PluginInspectionInterface {


  public function id();
  public function label();
  public function type();
  public function accessLinkBuild($account, $expire, $redirect);
	public function prepareTimeStamps($expire);
	public function prepareRedirect( $redirect = NULL );
	public function prepareHashKey($account, $timestamp, $maptable, $mapsecret);

}
