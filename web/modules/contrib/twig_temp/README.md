Twig Temporary Environment
==========================

Running Drupal in a containerized environment where you don't have any shared
storage for public files? This module moves the Twig template cache to the
temporary directory, removing one more dependency on the public stream wrapper.

There's no configuration for this module - once installed, compiled templates
will be saved to temporary storage. To go back to regular public files storage,
simply uninstall.
