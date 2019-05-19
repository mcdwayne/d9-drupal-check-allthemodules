

(function() {

  WissKI = WissKI || {};
  WissKI.apus = WissKI.apus || {};
  WissKI.apus.gui = WissKI.apus.gui || {
    
    openCUDDialogs : [],
    openRDialogs : [],

    showCUDDialog: function( args ) {
      
      var anno = args.anno || null;
      var anchorElement = args.anchor || document;
      var position = args.position || {
        target: 'event',
        at : 'top left',
      };
      if (anno === null || !anno.id)  {
        alert('Bad annotation');
      }
      
      jQuery(anchorElement).qtip({
        position : position,
        content : '<div class="wisski-apus-body"></div>',
      });



      jQuery().html 
      
      
      
    }

    
    
    
    
    
  };

})();

