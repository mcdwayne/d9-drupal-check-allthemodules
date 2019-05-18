# Breezy
![Build Status](https://travis-ci.org/ChromaticHQ/breezy.svg?branch=8.x-1.x)

[Build Status]: https://travis-ci.org/ChromaticHQ/breezy

[Breezy HR] is end-to-end recruiting software designed to optimize your
recruiting process and delight your entire team. Bring everyone on board in less
time (and with less hassle) with our user-friendly, feature-rich platform.

This module provides integration between Drupal and the [Breezy API] for a
positions listing and position detail pages as well as a positions listing
block. _Please note: This module retrieves and displays raw HTML for job
listings that is provided by the Breezy API. This is HTML that you specify in
the Breezy UI, but should still be handled with care._

* For a full description of the module, visit the
[project page](https://drupal.org/project/breezy).
* To submit bug reports and feature suggestions, or to track changes, refer to
the [issue queue](https://drupal.org/project/issues/breezy)

### Requirements
A Breezy HR account.

### Installation
Installation via Composer is reccommended but not required.

`composer install drupal/breezy`

### Configuration
* Configure your Breezy company id authentication details at
`admin/config/services/breezy`.
* Assign the appropriate Drupal permissions for Breezy.
* Published listings should display at `/careers`.

[Breezy HR]: https://breezy.hr/

### Maintainers
Current maintainers:
* Mark Dorison (markdorison) - https://www.drupal.org/u/markdorison

This project has been sponsored by [Chromatic](https://chromatichq.com).

[Breezy API]: https://developer.breezy.hr/
