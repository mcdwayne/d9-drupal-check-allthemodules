<klarna>
  <ul class="klarna-payment-categories">
    <li each={ item in opts.settings.payment_method_categories } data-id={ item.identifier } onclick={ selectMethod } class={ item.selected ? 'is-selected' : ''}>
      <span class=indicator></span>
      <span class=name>{ item.name }</span>
    <div if={ item.selected } class={ item.done ? 'riot-processed' : '' } data-selector="klarna-container" id=klarna-payment-container-{ item.identifier }>
    </div>
    </li>
  </ul>
  <script>
    var self = this
    var authorizationAttempted = false

    selectMethod = function(event) {
      var item = event.item.item
      // Deselect previous items.
      opts.settings.payment_method_categories.map(function (item) {
        item.selected = false
        return item
      })
      // Mark current as selected.
      item.selected = true
      self.update()

      opts.load(item.identifier, {}, function (response) {
        item.done = true
        opts.selectedPaymentMethod = item.identifier
        self.update()
      })
    }.bind(this)

    isValid = function() {
      var input = document.querySelector('[data-klarna-selector="authorization-token"]')

      return input.getAttribute('value').length > 0;
    }

    submitHandler = function(event) {
      if (!isValid()) {
        event.preventDefault()
      }
      if (!opts.selectedPaymentMethod) {
        return;
      }
      if (!authorizationAttempted) {
        opts.authorize(opts.selectedPaymentMethod, function (response) {
          if (response.approved && response.show_form) {
            // @todo Figure out what to do if validation fails.
            observer.trigger('success', response, event.target)
          }
          // Indicate that we have attempted to authorize the order
          // and all further calls should use reauthorize callback
          // rather than this.
          authorizationAttempted = true
        })
      }
      else {
        // @todo Figure out how to deal with re-authorization if Klarna::onReturn()
        // fails.
        opts.reauthorize(opts.selectedPaymentMethod, function (response) {
          if (response.approved && response.show_form) {
            observer.trigger('success', response, event.target)
          }
        })
      }
    }.bind(this)

    var ValidationObserver = function() {
      riot.observable(this)

      this.on('success', function (response, element) {
        // Store authorization token to hidden form field.
        var input = document.querySelector('[data-klarna-selector="authorization-token"]')
        input.setAttribute('value', response.authorization_token)

        // Submit form to redirect to complete page.
        document.querySelector('.payment-redirect-form').submit();
      })
    }

    var button = document.querySelector('[data-klarna-selector="submit"]')
    button.addEventListener('click', submitHandler)

    var observer = new ValidationObserver()
  </script>
</klarna>