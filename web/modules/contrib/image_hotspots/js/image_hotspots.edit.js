/**
 * @file
 * Provides UI for making hotspots on image.
 */

'use strict';
(function ($, Drupal) {
  Drupal.behaviors.imageHotspotsEdit = {
    attach: function (context, settings) {
      $('.image-hotspots-wrapper:not(.init-edit)', context).once('imageHotspotsEdit').each(function () {
        var $wrapper = $(this);
        // Main elements.
        var divs = {
          $wrapper: $wrapper,
          $imageWrapper: $wrapper.find('.image-wrapper'),
          $image: $wrapper.find('img'),
          $labelsWrapper: $wrapper.find('.labels'),
          $editForm: $wrapper.find('.edit-form-wrapper'),
          $imageJcrop: null,
          $jcropWrapper: null
        };
        // Main data.
        var data = {
          hotspotsTarget: {
            fieldName: this.dataset.fieldName,
            imageStyle: this.dataset.imageStyle,
            fid: this.dataset.fid
          },
          hotspots: settings.image_hotspots[this.dataset.fieldName][this.dataset.fid][this.dataset.imageStyle].hotspots || {}
        };
        // Editor state.
        var state = {
          currentLabel: null,
          currentButton: null,
          currentHid: null,
          jcropApi: null
        };

        // Initialization.
        $wrapper.find('.add-button').click(function () {
          var $button = $(this);
          unselectButton();
          state.currentButton = $button;
          editAction($button);
        });
        divs.$labelsWrapper.children().each(function () {
          addEditorsToLabel($(this));
        });
        initEditForm();
        $wrapper.addClass('.init-edit');

        // Add edit and remove buttons to label.
        function addEditorsToLabel($label) {
          var $edit = $('<div />', {
            class: 'action edit',
            html: Drupal.t('Edit'),
            title: Drupal.t('Edit this hotspot'),
            on: {
              click: function () {
                var $button = $(this);
                var $label = $button.parent().parent();
                unselectButton();
                state.currentButton = $button;
                $button.addClass('selected');
                editAction($label);
              }
            }
          });
          var $remove = $('<div />', {
            class: 'action remove',
            html: Drupal.t('Remove'),
            title: Drupal.t('Delete this hotspot'),
            on: {
              click: function () {
                unselectButton();
                state.currentButton = null;
                var $label = $(this).parent().parent();
                removeAction($label);
              }
            }
          });
          var $box = $('<div />', {
            class: 'label-editor',
            'data-hid': $label.data('hid')
          });
          $box.append($edit, $remove);
          $label.append($box);
        }

        // Action when user click on 'remove' button.
        function removeAction($label) {
          var hid = $label.data('hid');
          var $button = $label.find('.remove');
          var $throbber = $('<div />', {
            class: 'ajax-progress-throbber',
            html: '<div class="throbber"></div>'
          });
          $button.after($throbber);
          $.post(drupalSettings.path.baseUrl + 'image-hotspots/' + hid + '/delete', {}, function (responseData) {
            removeHotspotFromImage(hid);
            hideJcrop();
            hideEditForm();
            deleteFromSettings(hid);
            $label.fadeOut(100);
            setTimeout(function () {
              $label.remove();
            }, 100)
          })
          .fail(function (responseData) {
            alert(responseData.responseText)
          })
          .always(function () {
            $throbber.remove();
          });
        }

        // Action when user click on 'edit' button.
        function editAction($label) {
          var hid = $label.data('hid') || -1;
          var hotspot_data = data.hotspots[hid] || {
            'title': '',
            'description': '',
            'link': ''
          };
          var position = $label.position();
          var dimensions = {
            'width': $label.width(),
            height: $label.height()
          };

          state.currentHid = hid;
          state.currentLabel = $label;

          showJcrop();

          if (hotspot_data.x !== undefined) {
            state.jcropApi.setSelect([
              hotspot_data.x,
              hotspot_data.y,
              hotspot_data.x2,
              hotspot_data.y2
            ]);
          }
          else {
            state.jcropApi.release();
          }

          showEditForm(hotspot_data, position, dimensions);
        }

        // Calculates position of form and fills inputs.
        function showEditForm(hotspot_data, position, dimensions) {
          if (divs.$editForm.width() + position.left + dimensions.width > $(window).width()) {
            position.left -= divs.$editForm.width();
          }
          divs.$editForm.css({
            top: position.top + 10 + 'px',
            left: position.left + 10 + 'px'
          });
          divs.$editForm.fadeIn(200);
          divs.$editForm.find('input[name="hotspots-title"]').val(hotspot_data.title).focus();
          divs.$editForm.find('input[name="hotspots-description"]').val(hotspot_data.description);
          divs.$editForm.find('input[name="hotspots-link"]').val(hotspot_data.link);
        }

        // Hides edit form and clears messages.
        function hideEditForm() {
          divs.$editForm.fadeOut(200);
          divs.$editForm.find('.form-messages').html('');
        }

        // Action when form is closed or successfully submited.
        function closeFormAction() {
          hideJcrop();
          hideEditForm();
          unselectButton();
        }

        // Action when user click on 'Save' button in edit form.
        function saveFormAction() {
          var hid, hotspotNewData;
          var selection = state.jcropApi.tellSelect();
          if (selection.h <= 0) {
            divs.$editForm.find('.form-messages').html(Drupal.t('Please select an area on the image.'));
            return false;
          }

          hid = state.currentHid;
          hotspotNewData = {
            title: divs.$editForm.find('input[name="hotspots-title"]').val(),
            description: divs.$editForm.find('input[name="hotspots-description"]').val(),
            link: divs.$editForm.find('input[name="hotspots-link"]').val(),
            x: Math.round(selection.x),
            y: Math.round(selection.y),
            x2: Math.round(selection.x2),
            y2: Math.round(selection.y2)
          };

          var $throbber = $('<div />', {
            class: 'ajax-progress-throbber',
            html: '<div class="throbber"></div>'
          });

          if (hid !== -1) {
            if (equalData(data.hotspots[hid], hotspotNewData)) {
              closeFormAction();
              return false;
            }

            divs.$editForm.find('button').after($throbber);
            $.post(drupalSettings.path.baseUrl + 'image-hotspots/' + hid + '/update', hotspotNewData, function (responseData) {
              hotspotNewData = responseData;
              var $labelTitle = state.currentLabel.find('.label-title').children();
              if (data.hotspots[hid].title !== hotspotNewData.title) {
                $labelTitle.html(hotspotNewData.title);
              }
              if (data.hotspots[hid].link !== hotspotNewData.link) {
                if ($labelTitle.is('a')) {
                  if (hotspotNewData.link === '') {
                    $labelTitle.replaceWith($('<span>' + $labelTitle.html() + '</span>'));
                  }
                  else {
                    $labelTitle.attr('href', hotspotNewData.link);
                  }
                }
                else {
                  $labelTitle.replaceWith($('<a href="' + hotspotNewData.link + '" target="_blank">' + $labelTitle.html() + '</a>'));
                }
              }
              if (data.hotspots[hid].description !== hotspotNewData.description) {
                $labelTitle.prevObject.prevObject[0].setAttribute('title', hotspotNewData.description);
              }

              data.hotspots[hid] = hotspotNewData;
              hotspotNewData.hid = hid;
              closeFormAction();
              removeHotspotFromImage(hid);
              Drupal.behaviors.imageHotspotView.createHotspotBox(divs.$imageWrapper, hotspotNewData);
            })
            .fail(function (responseData) {
              divs.$editForm.find('.form-messages').html(responseData.responseText);
            })
            .always(function () {
              $throbber.remove();
            });
          }
          else {
            hotspotNewData.image_style = data.hotspotsTarget.imageStyle;
            hotspotNewData.field_name = data.hotspotsTarget.fieldName;
            hotspotNewData.fid = data.hotspotsTarget.fid;

            divs.$editForm.find('button').after($throbber);
            $.post(drupalSettings.path.baseUrl + 'image-hotspots/create', hotspotNewData, function (responseData) {
              hotspotNewData = responseData;
              data.hotspots[responseData.hid] = hotspotNewData;

              var $newLabel = Drupal.behaviors.imageHotspotView.createHotspotLabel(divs.$labelsWrapper, hotspotNewData);
              Drupal.behaviors.imageHotspotView.createHotspotBox(divs.$imageWrapper, hotspotNewData);
              addEditorsToLabel($newLabel);
              closeFormAction();
            })
            .fail(function (responseData) {
              divs.$editForm.find('.form-messages').html(responseData.responseText);
            })
            .always(function () {
              $throbber.remove();
            });
          }
        }

        // Sets up new options for Jcrop and shows it.
        function showJcrop() {
          var jcropSettings = {
            trueSize: [divs.$image.attr('width'), divs.$image.attr('height')],
            boxWidth: divs.$image.width(),
            boxHeight: divs.$image.height()
          };

          if (state.jcropApi === null) {
            initJcrop();
          }
          state.jcropApi.setOptions(jcropSettings);

          divs.$imageWrapper.hide();
          divs.$jcropWrapper.show();
        }

        // Releases Jcrop selection and hide it.
        function hideJcrop() {
          state.jcropApi && state.jcropApi.release();
          divs.$jcropWrapper && divs.$jcropWrapper.hide();
          divs.$imageWrapper.show();
        }

        // Remove existing box and overlays form image after successful deletion.
        function removeHotspotFromImage(hid) {
          divs.$imageWrapper.find('.overlay[data-hid="' + hid + '"]').remove();
          divs.$imageWrapper.find('.hotspot-box[data-hid="' + hid + '"]').remove();
        }

        // Edit form initialization.
        function initEditForm() {
          divs.$editForm.find('.close-button').click(function () {
            closeFormAction();
          });
          divs.$editForm.find('button').click(function () {
            saveFormAction();
            return false;
          });
          divs.$editForm.keyup(function (evt) {
            evt = evt || window.event;
            if ((evt.key && evt.key == 'Escape') || (evt.keyCode == 27)) {
              closeFormAction();
            }
          });
        }

        // Creates image for jcrop and hide original.
        function initJcrop() {
          var jcropSettings = {
            keySupport: false,
            bgOpacity: 0.7
          };
          divs.$imageJcrop = divs.$image.clone();
          divs.$imageWrapper.after(divs.$imageJcrop);
          divs.$imageJcrop.Jcrop(jcropSettings, function () {
            state.jcropApi = this;
            divs.$jcropWrapper = divs.$wrapper.find('.jcrop-holder');
          });
        }

        function equalData(oldD, newD) {
          return (oldD.title == newD.title &&
            oldD.description == newD.description &&
            oldD.link == newD.link &&
            oldD.x == newD.x &&
            oldD.y == newD.y &&
            oldD.x2 == newD.x2 &&
            oldD.y2 == newD.y2);
        }

        // Remove 'selected' class from last button.
        function unselectButton() {
          state.currentButton && state.currentButton.removeClass('selected');
        }

        // Remove deleted hotspots info from drupalSettings.
        function deleteFromSettings(hid) {
          var target = data.hotspotsTarget;
          if (settings.image_hotspots[target.fieldName][target.fid][target.imageStyle].hotspots.hasOwnProperty(hid)) {
            delete settings.image_hotspots[target.fieldName][target.fid][target.imageStyle].hotspots[hid];
          }
        }
      });
    }
  };
})(jQuery, Drupal);
