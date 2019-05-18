(function ($, Drupal) {
  Drupal.behaviors.blockSidekick = {
    attach: function(context, settings) {
      $('body', context).once('displaySidekick').each(function () {

        function replaceHyphens(str) {
          return str.replace(/-/g, '_');
        }

        if (!settings.path.currentPathIsAdmin && settings.user.uid != 0) {
          var $dialog = $('<div id="dlg_block_sidekick"></div>');
          var $categoryHeader = '';
          var $blockGroup = '';
          var $block = '';
          var blocks = settings.block_sidekick.blocks;
          var regionIds = settings.block_sidekick.regionIds;

          var dialogPosition = {
            my: 'right top',
            at: 'right-25 top+25',
            of: 'body'
          };

          for (var blockCategory in blocks) {
            var category = blockCategory;
            var categoryBlocks = blocks[category];

            $categoryHeader = $('<div class="dlg_block_sidekick_category" data-id="' + category + '">' + category + '</div>');
            $blockGroup = $('<div class="dlg_block_sidekick_category_blocks"></div>');

            $dialog.append($categoryHeader);
            for (var blockIdx in categoryBlocks) {
              var block = categoryBlocks[blockIdx];
              $block = $('<div class="dlg_block_sidekick_category_blocks_block" data-id="' + block.id + '">' + block.admin_label + '</div>');
              $block.draggable({
                appendTo: 'body',
                helper: 'clone',
                revert: 'invalid',
                cursor: 'move',
                opacity: 0.75
              });
              $blockGroup.append($block);
            }
            $dialog.append($blockGroup);
          }

          $dialog.dialog({
            title: 'Block Sidekick',
            appendTo: 'body',
            position: dialogPosition,
            classes: {
              'ui-dialog': 'dlg_block_sidekick'
            },
            closeOnEscape: false
          })
          .css({
            height: '350px',
            overflow: 'auto',
          });

          for (var idx in regionIds) {
            $('body', context).find('.region-' + regionIds[idx])
              .sortable({
                connectWith: '.ui-droppable'
              })
              .droppable({
                accept: '.dlg_block_sidekick_category_blocks_block,.ui-droppable .block',
                drop: function(evt, ui) {
                  var $block = $(ui.draggable);
                  var $regionId = replaceHyphens(/region([\-A-Za-z0-9]+)+/.exec(this.className)[0].replace('region-', ''));
                  var $themeId = settings.block_sidekick.theme_info.name;
                  var $region = $(this);

                  if (ui.draggable.hasClass("ui-sortable-helper")) {
                    var $blockId = replaceHyphens($block.attr('id'));
                  } else {
                    var $blockId = $block.data('id');
                  }

                  // Get the region the block is currently assigned to.
                  var regionPromise = [];
                  var post = $.post(
                    '/ajax/block_sidekick/get_current_region',
                    {
                      block_id: $blockId,
                      theme_id: $themeId,
                    }
                  );
                  regionPromise.push(post);

                  $.when.apply($, regionPromise).then(function (data) {
                    console.log(data);
                  });

                  return false;

                  // Custom sortable logic.
                  if (ui.draggable.hasClass("ui-sortable-helper")) {
                    console.log('This droppable originated from region: ' + startRegion);

                    return false;
                  }

                  $.post(
                    '/ajax/block_sidekick/place_block',
                    {
                      block_id: $blockId,
                      region_id: $regionId,
                      theme_id: $themeId
                    },
                    function (response) {
                      if (response.status == 'OK') {
                        var $confirmation = $('<div title="Success!"><p>The block was successfully placed. Your page will now reload.</p><p><button class="dialog-cancel">OK</button></p></div>');
                        confirmationDialog = Drupal.dialog($confirmation, {
                          dialogClass: '',
                          resizable: false,
                          closeOnEscape: true,
                          create: function () {
                            $(this).parent().find('.ui-dialog-titlebar-close').remove();
                          },
                          beforeClose: false,
                          close: function (event) {
                            $(event.target).remove();
                            window.location.reload(true);
                          }
                        }).showModal();
                      }
                    }
                  );
                },
                classes: {
                  'ui-droppable-hover': 'dlg_block_sidekick_region_hover'
                }
              })
            ;
          }
        }
      });
    }
  };

}(jQuery, Drupal));
