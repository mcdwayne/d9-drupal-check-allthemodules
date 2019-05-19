/**
 * @file
 * TextRazor functionalities for node edit form.
 *
 * Tigers the 'suggest' button created by the form_alter() hook. Gets the
 * content text and push it to TextRazor, parses the JSON response to set
 * all values to the proper vocabulary field.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.textrazor = {
    /**
     * Prevents selectize tag creation on the fly.
     *
     * While textrazor is allowed to create tags, editors must not.
     * The setting for create missing term references will also allow
     * adding new tags in selectize.
     */
    preventTagCreation: function () {
      $.each(drupalSettings.selectize, function (index, value) {
        if (typeof value !== 'object') {
          value = JSON.parse(value);
        }
        var config = value;
        config.create = false;
        drupalSettings.selectize[index] = config;
      });
    },
    /**
     * Adds clicking on a suggested tag to pushes it a to the general tag list.
     */
    pushSelectedTag: function () {
      let self = this;
      let suggested_tags_input = 'edit-field-suggested-tags-target-id';
      let selectize_config = drupalSettings.selectize[suggested_tags_input];
      if (typeof selectize_config !== 'object') {
        selectize_config = JSON.parse(selectize_config);
      }
      selectize_config.onInitialize = function () {
        let selectize = $(this)[0];
        $('[data-drupal-selector=' + suggested_tags_input + ']')
          .next('.selectize-control')
          .find('.items')
          .on('click', '.item', function () {
            // Return the related value to the clicked label
            let option_key = _.findKey(selectize.options, function (option) {
              return option.value === this.dataset.value;
            }, this);
            let item = selectize.options[option_key];
            self.pushTag(item[selectize.settings.labelField], 'tags');
          });
      };
      drupalSettings.selectize[suggested_tags_input] = selectize_config;
    },
    /**
     * Maps the passed textrazor entity topic to the related Drupal entity type.
     *
     * @param {string} topic
     *   The textrazor entity topic.
     *
     * @returns {string}
     */
    getType: function (topic) {
      // Merge entity types form TextRazor to a reduced set of types in our vocabularies.
      let mapping = {
        'organizations': ['Agent'],
        'places': ['Place', 'PopulatedPlace', 'Country', 'Settlement'],
        'people': ['Person'],
        'date': ['Date'],
        'other-entities': ['URL', 'Other'],
        'industries': ['Industry'],
      };
      let type = _.findKey(mapping, function (value) {
        return _.intersection(value, topic).length;
      });
      return type ? type : 'entities';
    },
    /**
     * Pushes term label to the vocabulary field.
     */
    pushTag: function (label, fieldName, entity) {
      var input = $('[data-drupal-selector="edit-field-' + fieldName + '-target-id"]');
      label = label.replace(/[(|)]/g, '-');

      // Ignore tags that do not belong to the set of already created tags.
      if (fieldName === 'tags' && !drupalSettings.textrazor.currentTags.includes(label)) {
        return;
      }

      $.each(input, function (index, element) {
        if (typeof element.selectize !== 'undefined') {
          let selectize = element.selectize;
          selectize.addOption({
            [selectize.settings.valueField]: label,
            [selectize.settings.labelField]: label,
            entity: entity,
          });
          selectize.addItem(label, true);
        }
      });
    },
    /**
     * Get significant text for article classification from the edit form merging text and references to entities.
     *
     * @TODO make content fields configurable.
     */
    getContent: function () {
      // Get teaser content.
      var teaserSelector = 'edit-field-teaser-text-0-value';
      var teaser= $('#' + teaserSelector).val();
      // The content can be both plain textarea and CKEditor
      if (teaser == '' && typeof CKEDITOR !== "undefined" && typeof CKEDITOR.instances[teaserSelector] !== "undefined") {
        teaser = CKEDITOR.instances[teaserSelector].getData();
      }
      var content = [
        {
          type: 'text',
          value: teaser
        }
      ];

      // Get regular body content.
      var bodySelector = 'edit-body-0-value';
      var body = $('#' + bodySelector).val();
      // The content can be both plain textarea and CKEditor
      if (body == '' && typeof CKEDITOR !== "undefined" && typeof CKEDITOR.instances[bodySelector] !== "undefined") {
        body = CKEDITOR.instances[bodySelector].getData();
      }
      content.push({
        type: 'text',
        value: body
      });

      // Get paragraphs content.
      var summaryElements = '.paragraphs-container .paragraph--type--text';
      var formElements = '.paragraphs-container .paragraph-form-item--has-subform textarea';
      $(summaryElements + ', ' + formElements).each(function () {
        // The attribute is set in hook_entity_view_alter.
        if ($(this).attr('data-entity-id') !== undefined) {
          content.push({
            type: 'ref',
            value: $(this).attr('data-entity-id')
          });
        }
        else if ($(this).val() !== '') {
          content.push({
            type: 'text',
            value: $(this).val()
          });
        }
        else if (typeof CKEDITOR !== "undefined" && typeof CKEDITOR.instances[$(this).attr('id')] !== "undefined") {
            content.push({
              type: 'text',
              value: CKEDITOR.instances[$(this).attr('id')].getData()
            });
          }
      });
      return content;
    },
    attach: function (context) {
      let self = this;
      // Calls for extending the selectize config.
      self.preventTagCreation();
      self.pushSelectedTag();

      $('.js-form-item-field-suggested-tags-target-id label').on('click', function () {
        $(this).closest('div').toggleClass('show-tags');
      });

      $('input[data-drupal-action="textrazor-suggest"]').click(function (e) {
        e.preventDefault();

        // @see src/Plugin/rest/resource/TextrazorResource class.
        $.get('/rest/session/token').done(function (data, textStatus, jqXHR) {
          $.post({
            url: "/textrazor?_format=json",
            beforeSend: function (req) {
              req.setRequestHeader("X-CSRF-Token", data);
            },
            contentType: 'application/json',
            data: JSON.stringify({
              text: self.getContent(),
            })
          }).done(function (res){
            var stringResponse = JSON.stringify(res);
            $(context).find('[data-drupal-selector="edit-field-textrazor-response-0-value"]').val(stringResponse);
            // TODO solve the () of drupal parse geting the value as term ID in a proper way

            /**
             * This section iterates each kind of terms to the
             * corresponding vocabulary field.
             *
             * @see https://www.textrazor.com/docs/rest#TextRazorResponse
             * @see the JSON string
             */
            let coarseTopics = _.sortBy(res.response.coarseTopics, 'relevanceScore');
            _.map(coarseTopics, function(topic) {
              let label = TextRazor.translateLabel(topic, 'de', topic.label);
              self.pushTag(label, 'categories');
            });
            _.map(res.response.topics, function(topic) {
              let label = TextRazor.translateLabel(topic, 'de', topic.label);
              self.pushTag(label, 'topics');
            });

            /**
             * Maps IPTC terms to the local vocabulary if present.
             *
             * @note no translation check because the reference is based on ID to a already existing terms.
             *
             * @see 'classifiers' at https://www.textrazor.com/docs/rest#analysis
             */
            _.map(res.response.categories, function(topic) {
              if (topic.label !== undefined) {
                self.pushTag(topic.label, 'newstopics');
              }
            });

            // Filter for type, number and duplicate entities and sort them by score.
            let sorted_entities = _.filter(res.response.entities, function (entity) {
              return entity.hasOwnProperty('type') && !(entity.hasOwnProperty('unit') && entity.unit === 'Number');
            });
            sorted_entities = _.sortBy(sorted_entities, 'relevanceScore').reverse();
            sorted_entities = _.uniq(sorted_entities, true, 'entityId');

            _.map(sorted_entities, function(topic) {
              let label = TextRazor.translateLabel(topic, 'de', topic.entityId);
              let type = self.getType(topic.type);
              self.pushTag(label, type);
              self.pushTag(label, 'tags', type);
              self.pushTag(label, 'suggested-tags');
            });
          });
        });
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
