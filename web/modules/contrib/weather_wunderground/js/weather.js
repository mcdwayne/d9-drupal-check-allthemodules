jQuery( document ).ready(function( $ ){
  $('.current-weather').click(function(){
    var $this = $(this);
    if(!$this.hasClass('active'))
    {
      $this.next().fadeIn('fast').toggleClass('active')
    }
    else
    {
      $this.next().fadeOut('fast').toggleClass('active')
    }
    $this.toggleClass('active');
  });
});