/*
 *
 *  Jquery.ui sortable and draggable blocks
 *
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.draggable_blocks = {
    attach: function (context, settings) {

      var startRegion = null;
      var blockSettings = {};
      // ToDo this is loading twice
      function fixHelper( e, ui ) {
        startRegion = $(this);
        ui.helper
          .addClass('mx-state-moving ui-corner-all')
          .outerWidth("250px")
      }

      function setBlocks (ui) {
        var blocksInput = $('#draggable-blocks-input');
        var endRegion = $(ui.item);
        saveRegion(startRegion);
        saveRegion(endRegion);
        $(blocksInput).val(JSON.stringify(blockSettings));
      }

      function saveRegion(elem) {
        var region = $(elem).parent('.region-wrap-container');
        var regionName = region.attr('aria-region');
        if (typeof regionName === 'undefined') {
          return;
        }
        if(blockSettings.hasOwnProperty(regionName)){
          delete blockSettings[regionName];
        }
        var blockArray = [];
        var blocks = $(region).find('.portlet-container');
        $(blocks).each(function(index, block) {
          blockArray.push($(block).attr('aria-block'));
        });
        blockSettings[regionName] = blockArray;
      }

      var region_list = settings.draggable_blocks.region_list;
      var block_list = settings.draggable_blocks.block_list;

      $.each(region_list, function( region, el ) {
        $(el).addClass('region-container region-wrap-container').attr('aria-region', region);
        $(el).once().append('<span class="region-name">' + region + '</span>');
      });
      $.each($( ".region-wrap-container" ).find('.block'), function( i, el ) {
        $(el).addClass('portlet-container');
        var block_raw = $(el).attr('id');
        // Expects id as #block-DEFINITION-ID
        block_raw = block_raw.substring(6);
        var block_id = block_raw.replace(new RegExp("-", "g"), "_");
        var blockData = block_list[block_id];
        $(el).attr('aria-block', block_id)
        $(el).once().wrap( "<div class='portlet portlet-wrap'></div>" );
        // Not using before because once() blocks it
        $(el).parent().once().prepend('<div class="portlet-header">' + blockData.label + '<span class="ui-icon ui-icon-minusthick portlet-toggle"></span></div>');
	  });
      // Do it later to avoid double header injection
      $('.draggable-blocks-container').addClass('region-container');
      $( "#block-draggableblocks" ).draggable({ cursor: "move", cursorAt: { top: 56, left: 56 }, handle: "draggable-blocks-header" });

      $( ".region-container" ).sortable({
        connectWith: ".region-container",
        cursor: "move",
        cursorAt: { left: 5 },
        handle: ".portlet-header",
        cancel: ".portlet-toggle",
        placeholder: "portlet-placeholder ui-corner-all",
        start: fixHelper,
        stop: function( event, ui ) {
          setBlocks(ui);
        }
      }).disableSelection();

      $( ".portlet" )
        .addClass( "ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" )
        .find( ".portlet-header" )
          .addClass( "ui-widget-header ui-corner-all" );
 
      $( ".portlet-toggle" ).once().click(function() {
        var icon = $( this );
        icon.toggleClass( "ui-icon-minusthick ui-icon-plusthick" );
        icon.closest( ".portlet" ).find( ".portlet-container" ).toggle();
      });

      $( ".draggable-blocks-container .portlet-container" ).hide();

    }
  };

})(jQuery, Drupal);
