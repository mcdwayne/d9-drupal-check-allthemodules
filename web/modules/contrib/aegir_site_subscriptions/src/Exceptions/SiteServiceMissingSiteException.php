<?php

namespace Drupal\aegir_site_subscriptions\Exceptions;

/**
 * Exception for callers using the site service without setting a site.
 */
class SiteServiceMissingSiteException extends ServiceMissingWrappedObjectException {
}
