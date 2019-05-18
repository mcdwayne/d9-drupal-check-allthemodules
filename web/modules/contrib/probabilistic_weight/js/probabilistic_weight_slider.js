(function($){

Drupal.behaviors.probabilisticWeightSlider = {
  attach: function(context, settings) {
    $('.prob_weight_slider:not(.processed)', context).each(
      function(){
        var $this = $(this);
        $this.addClass('processed');
        var $field = $this;
        var $id = $this.prop('id');
        var $description = $this.parent().find('.description');
        var $description_text = $description.text();
        $this.attr('data-description', $description.text());
        var $slider = $('<div class="prob-weight-slider"></div>').insertAfter($this).slider({
          min: 0,
          max: 1,
          range: 'min',
          value: $this.val(),
          step: 0.01,
          slide: function(event, ui) {
            $this.val(ui.value);
            $description.text($this.attr('data-description') + ': ' + ui.value);
          }
        });
        $this.bind(
          'change blur',
          function(){
            var $this = $(this);
            if(isFinite($this.val())){
              if($this.val() < 0){
                $this.val(0);
              }else if($this.val() > 1){
                $this.val(1);
              }
              $slider.slider('value', $this.val());
              $description.text($this.attr('data-description') + ': ' + $slider.slider('value'));
            }else{
              $this.val($slider.slider('value'));
            }
          }
        );
        $description.text($this.attr('data-description') + ': ' + $slider.slider('value'));
        if (!$this.hasClass('prob_weight_required')) {
          var $id_checkbox = $id + '--disable';
          var $checkbox_wrapper = $('<div class="form-item form-type-checkbox"></div>');
          var $checkbox;
          if ($.trim($field.val()) === '') {
            $checkbox = $('<input type="checkbox" class="form-checkbox" id="' + $id_checkbox + '" checked="checked"/>');
          }
          else {
            $checkbox = $('<input type="checkbox" class="form-checkbox" id="' + $id_checkbox + '" />');
          }
          var $label = $('<label for="' + $id_checkbox + '" class="option">' + Drupal.settings.probabilistic_weight.empty_text + '</label>');
          $checkbox_wrapper.append($checkbox);
          $checkbox_wrapper.append($label);
          $this.parent().append($checkbox_wrapper);
          function updateSliderFromCheckbox($item) {
            if ($item.prop('checked')) {
              $slider.slider('disable');
              $description.text($description_text + ': ' + Drupal.settings.probabilistic_weight.disabled_text);
              $field.val('');
            }
            else {
              $slider.slider('enable');
              $description.text($description_text + ': ' + $slider.slider('value'));
              $field.val($slider.slider('value'));
            }
          }
          $('#' + $id_checkbox).bind(
            'change click',
            function(){
              var $this = $(this);
              updateSliderFromCheckbox($this);
            }
          );
          updateSliderFromCheckbox($('#' + $id_checkbox));
        }
        $this.hide();
      }
    );
  }
};

})(jQuery);
