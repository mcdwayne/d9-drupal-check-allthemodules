(function ($, Drupal) {
  Drupal.behaviors.advertisingProductsAmazonImageSelection = {
    attach: function (context, settings) {
      $('.advertising-products__images').once('advertisingProductsAmazonImageSelection').each(function(index, el) {
        var $el = $(el)
        var $alternatives = $el.find('.advertising-products__alternatives')
        var $imageSelection = $el.find('.advertising-products__image-selection')
        var $init = $el.find('.advertising-products__init')
        var $selected = $el.find('.advertising-products__selected')
        var $bottom = $el.find('.advertising-products__images--bottom')
        var $input = $el.siblings('.advertising-products__images--input')
        var $open = $el.siblings('.advertising-products__open-alternatives')


        var $imageZoom = $('<div class="advertising-products__image-zoom"></div>').appendTo($imageSelection).hide()
        var $changed = $('<div class="advertising-products__changed">' + Drupal.t('Not saved, yet!') + '</div>').appendTo($bottom).hide()

        var thisObj = {
          $el: $el,
          $alternatives: $alternatives,
          $init: $init,
          $imageZoom: $imageZoom,
          $imageSelection: $imageSelection,
          $selected: $selected,
          $changed: $changed,
          $input: $input,
          $open: $open
        }

        $init.on('click', Drupal.behaviors.advertisingProductsAmazonImageSelection.init.bind(thisObj))

        $el.parent().on('autocompleteselect', Drupal.behaviors.advertisingProductsAmazonImageSelection.autocompleteselect.bind(thisObj))

        Drupal.behaviors.advertisingProductsAmazonImageSelection.returnToLastState.call(thisObj)

      });
    },
    init: function(e) {
      if (e) {
        e.preventDefault()
      }
      Drupal.behaviors.advertisingProductsAmazonImageSelection.openAlternatives.call(this)

      if (!this.$el.hasClass('initialised')) {
        this.$alternatives.on('mouseenter.imageselection', 'img', Drupal.behaviors.advertisingProductsAmazonImageSelection.mouseenter.bind(this))
        this.$alternatives.on('mouseleave.imageselection', 'img', Drupal.behaviors.advertisingProductsAmazonImageSelection.mouseleave.bind(this))
        this.$alternatives.on('click.imageselection', 'img', Drupal.behaviors.advertisingProductsAmazonImageSelection.click.bind(this))

        this.$el.addClass('initialised')
      }
    },
    openAlternatives: function() {
      this.$alternatives.show()
      this.$init.hide()

      this.$open.val(true)

      this.$alternatives.find('img').each(function (index, el) {
        el.src = el.dataset.src
      })
    },
    reset: function() {
      // Reset input field
      this.$input.val("")
      this.$changed.hide()
    },
    returnToLastState: function() {
      if (this.$open.val()) {
        Drupal.behaviors.advertisingProductsAmazonImageSelection.init.call(this)
      }
      var inputVal = this.$input.val()
      var selectedSrc = this.$selected.find('img').attr('src')
      if(inputVal && inputVal !== selectedSrc) {
        Drupal.behaviors.advertisingProductsAmazonImageSelection.changeSelectedImage.call(this, inputVal)
      }
    },
    mouseenter: function(e) {
      var src = $(e.target).attr('src')
      this.$imageZoom.append('<img src="' + src + '" />').show()
    },
    mouseleave: function(e) {
      this.$imageZoom.empty().hide()
    },
    click: function(e) {
      var src = $(e.target).attr('src')
      Drupal.behaviors.advertisingProductsAmazonImageSelection.changeSelectedImage.call(this, src)
    },
    changeSelectedImage: function(src) {
      this.$selected.find('img').attr('src', src)
      this.$input.val(src)
      this.$changed.show()
    },
    autocompleteselect: function(e, ui) {
      this.$el.show()

      var primary = ui.item.primary
      var alternatives = ui.item.alternatives

      this.$selected.find('img').attr('src', primary)

      Drupal.behaviors.advertisingProductsAmazonImageSelection.reset.call(this)

      if (alternatives.length > 0) {
        var renderedAlternatives = alternatives.map(function(alternative) {
          return $('<div><img data-src="' + alternative.url + '" data-image-id="' + alternative.iid + '"/></div>')
        })

        this.$alternatives.empty().append(renderedAlternatives)
        Drupal.behaviors.advertisingProductsAmazonImageSelection.init.call(this)
        this.$imageSelection.show()
      }
      else {
        this.$imageSelection.hide()
      }
    }
  }
})(jQuery, Drupal)
