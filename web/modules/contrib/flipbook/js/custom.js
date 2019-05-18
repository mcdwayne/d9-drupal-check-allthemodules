
  jQuery(document).ready(function() {
var path = drupalSettings.host + "/" + drupalSettings.modulepath;
if(drupalSettings.pdfchoice == 1){
     
        var template = {
        html: path + '/templates/default-book-view.html',
        styles: [
          path + '/css/font-awesome.min.css',
          path + '/css/short-white-book-view.css'
        ],
        script: path + '/js/default-book-view.js'
      };   
  jQuery('.books').find('img').click(function(e) {
      var currentpath = jQuery(this).attr('data');
      var booksOptions = {
      investindiaflipbook: {
      pdf: currentpath,
      downloadURL: currentpath,
      template: template
     },
    };
      
      var instance = {
      scene: undefined,
      options: undefined,
      node: jQuery('#flip-book-window').find('.mount-node')
      };
  jQuery('#flip-book-window').on('hidden.bs.modal',  function() {
    instance.scene.dispose();
  });
  jQuery('#flip-book-window').on('shown.bs.modal', function() {
    instance.scene = instance.node.FlipBook(instance.options);
  });
      var targetid = "investindiaflipbook";
      instance.options = booksOptions[targetid];
      jQuery('#flip-book-window').modal('show');
    
  });
}
else{
      
var template = {
        html: path + '/templates/default-book-view.html',
        links: [{
          rel: 'stylesheet',
          href: path + '/css/font-awesome.min.css'
        }],
        styles: [
          path + '/css/short-black-book-view.css'
        ],
        script: path + '/js/default-book-view.js'
      };
    jQuery('#container1').FlipBook({
       pdf: drupalSettings.pdfpath,
       template: template
     });
}

  });

      // }