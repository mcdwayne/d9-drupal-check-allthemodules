<?php

/**
 * @file
 * Contains audit_log.page.inc.
 *
 * Page callback for Audit log entities.
 */

/**
 * Prepares variables for Audit log templates.
 *
 * Default template: audit-log.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_audit_log(array &$variables) {
  // Fetch AuditLog Entity Object.
  $audit_log = $variables['element']['#audit_log'];
}
