/**
 * @file
 * Provides a behaviour to enhance the library edit form.
 */

(function (Drupal) {
  'use strict';

  /**
   * Add the behavior.
   */
  Drupal.behaviors.librariesProviderUiLibraryForm = {
    attach: function (context) {
      // When the source changes load the new versions in the corresponding select.
      var source = context.querySelector('#edit-source')
      if (source) {
        source.addEventListener('change', function(event) {
          var versions = event.srcElement.options[event.srcElement.options.selectedIndex].dataset.librarySourceVersions;
          versions = JSON.parse(versions);
          var versionSelect = context.getElementById('edit-version');
          versionSelect.options.length = 0;
          for (var version in versions){
            var option = document.createElement('option');
            option.value = version;
            option.innerHTML = version;
            versionSelect.appendChild(option);
          }
        });
      }

      // When the variant changes update the description with the proper URL.
      var variant = context.querySelector('#edit-variant')
      if (variant) {
        variant.addEventListener('change', function(event) {
          var url = event.srcElement.options[event.srcElement.options.selectedIndex].dataset.libraryVariantUrl;
          context.getElementById('edit-variant--description').innerHTML = Drupal.t(
            'Learn more about this variant at <a href=@url>@url</a>', {'@url': url}
          );
        });
      }
    }
  };
})(Drupal);
