# CKEditor5 Sections

Drupal module providing a sections editor based on CKEditor5.

## Dependencies

- Media library
- Linkit

Local development also requires the webpack module to be installed. It is not necessary to have it in production / client sites, although it is a good idea to have it when other webpack-based modules are present. In that case common dependencies can be extracted and cached in the browser.

## Developing

1. Enable the webpack module
1. Perform webpack's installation instructions
   1. create a project-wide _package.json_
   1. add the webpack dependencies `yarn add file:./web/modules/contrib/webpack`
1. Add the local editor build to the project-wide _package.json_. `yarn add file:./ckeditor5-sections` (@see TODO 1)
1. Perform code changes
1. Build the libraries statically
   - `drush webpack:build-single ckeditor5_sections/editor_build`
   - `drush webpack:build-single ckeditor5_sections/editor`
1. Commit both the source files and the dist files.

## TODO

1. Add a package.json that references the editor build and update installation instructions.

## Known issues

1. `drush webpack:serve` doesn't work at the moment. I'll check that asap.
