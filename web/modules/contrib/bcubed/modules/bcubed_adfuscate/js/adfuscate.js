new BCubedActionPlugin({
    action: function(args) {
      jQuery(function ($) {
        mask = $("<div/>").attr("style", args.settings.mask_style);
        content = $("<div/>").attr("style", args.settings.message_style);
        content.html(args.settings.message);
        mask.append(content);
        $('body').append(mask);
        mask.css('opacity'); // to prevent following line being executed before DOM update
        mask.attr("style", args.settings.mask_style + args.settings.mask_style_active);
      });
    }
  })
