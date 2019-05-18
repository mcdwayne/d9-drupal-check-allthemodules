# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Unreleased

## [0.0.4] - 2018-10-17
### Added
- Add drush 9.x support
- Add correct breakpoint group for breakgen_responsive_image module
### Fixed
- Fix wrong imageStyleMachine name issue
- Fix breakgen_crop Warning: array_flip(): Can only flip STRING and INTEGER values! in Drupal\Core\Entity\EntityStorageBase->loadMultiple()

## [0.0.3] - 2018-03-11
### Fixed
- Fix another minor bug for machine name (strips out dots from yml file)

## [0.0.2] - 2018-02-22
### Changed
- Changed code style from PSR-2 to Drupal code style.

### Added
- Added hooks for 3rd party extensions.
- Added example module "breakgen_crop" for 3rd party integration.
- Added missing PHP doc annotations.
- Added command to clear all breakgen related entities.
- Added post save hook for 3rd party extensions.
- Added example module "breakgen_responsive_image" for responsive image generation.
- Added breakgen_responsive_image and breakgen_responsive_image_fallback to breakgen file.

### Fixed
- Fixed warning when percentages are not set on breakpoint.
- Fixed crop_type definition having "x" instead of ":" for aspect ratio

### Changed
- Changed example module naming to be more generic

## 0.0.1 - 2018-02-22
### Added
- Ability to generate image styles from a breakpoint file.
- This CHANGELOG file to hopefully serve as an evolving example of a
  standardized open source project CHANGELOG.
- Standard breakpoint file.
- README for basic instructions.

[Unreleased]: https://github.com/tamtamnl/Drupal-Breakgen/compare/v0.0.1...HEAD
[0.0.2]: https://github.com/tamtamnl/Drupal-Breakgen/compare/v0.0.1...v0.0.2