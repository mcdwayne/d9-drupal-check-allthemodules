/**
* WissKI Automatic Text Analysis (formerly Wisski Send)
* send text content to server and get annotations
* This was formerly part of the wisskicore plugin
* @author Eugen Meissner (2011), Martin Scholz (2011-)
*/



(function() {
 
  tinymce.create('tinymce.plugins.wisskiTextAnly',{

    getInfo : function() {
        return {
        longname : 'Wisski Text Analysis',
        author : 'Eugen Meissner, Martin Scholz'
        };
    },

    isautosend : false, // if true, the text will be send periodically automatically
    lastTicket : 0, // revision increment to identify the last post
    textChanged : true, // state, if text has changed since last send
    interval : 10000, // interval for automatic sending
    url : '', // the url to send the text to
    callbackURL : '',
    logCallbackURL : '',
    profile : 'default', 

    init : function(ed, url) {
      var t = this;
    
      t.editor = ed;
// set later      t.core = ed.plugins.wisskicore;
      t.interval = ed.getParam('wisski_textanly_autosend_interval');
      t.url = url;
      t.callbackURL = ed.getParam('wisski_textanly_url');
      t.logCallbackURL = ed.getParam('wisski_textanly_log_url');
      t.profile = ed.getParam('wisski_textanly_profile')['default'];
      
      // button to toggle automatic sending of the text
      ed.addButton('wisskiTextAnlyAuto', {
        title : 'Auto',
        label : 'Toggle Autosend', 
        onclick : function() { 
          t.toggleAutosend(ed);
        }
      });
      
      // button to send the text manually
      // the button will be disabled if automatic sending is on
      ed.addButton('wisskiTextAnlyManual', {
        title : 'Analyse now',
        image : url + '/analyse_now.png',
       // label: 'Analyse',
        onclick : function() {
          t.sendText(ed);
        }
      });

      // button to send the text manually
      // the button will be disabled if automatic sending is on
      ed.addButton('wisskiTextAnlyLog', {
        title : 'Log of last analysis',
        image : url + '/analyse_log.png',
       // label: 'Analyse',
        onclick : function() {
          t.showLastLog();
        }
      });


      
      ed.onInit.add(function() {
        // set default values and button states
        t.isautosend = ed.getParam('wisski_textanly_autosend') == 'true';
        ed.controlManager.setDisabled('wisskiTextAnlyManual', t.isautosend);
        ed.controlManager.setActive('wisskiTextAnlyAuto', t.isautosend);
        // set up periodic check
        var periodic = window.setInterval(function() {
          // only if activated and text has changed
          // then send text automatically
          if (t.isautosend && t.textChanged) {
            t.textChanged = false;
            if (typeof t.core != 'undefined') t.core.db.warn('periodic!');
            t.sendText(ed);
          }
        }, t.interval);
      });

      ed.onChange.add(function(ed, l) {
        t.textChanged = true;
      });

    },

    
    // toggle automatic sending; update button states
    toggleAutosend : function(ed) {
      var t = this;
      ed.plugins.wisskiprogress.setProgressState(0);
      t.isautosend = !t.isautosend;
      ed.controlManager.setDisabled('wisskiTextAnlyManual', t.isautosend);
      ed.controlManager.setActive('wisskiTextAnlyAuto', t.isautosend);
    },
    

    // sends the text to the server using AJAX call
    sendText : function(ed) {
      var t = this;

      if (typeof t.core === 'undefined') {
        if (!ed.plugins.wisskicore) {
          alert('Module WissKICore is not loaded!');
          return;
        } 
        t.core = ed.plugins.wisskicore;
      }

      var data = {};  // the json data to be sent
      t.lastTicket = t.core.createUUID4();   
      data.ticket = t.lastTicket;  // store the ticket in the call metadata
      data.text = ed.getContent({format : 'raw'});  // we want the text with html tags
      data.profile = t.profile;
      
      t.core.setProgressState(1); // set busy sending state

      t.core.db.log("Send text data", data);
      
      // ajax call
      tinymce.util.XHR.send({
         url : t.callbackURL,
         content_type : "application/json",
         type : "POST",
         data : 'text_struct=' + tinymce.util.JSON.serialize(data),
         success_scope : t, // set this plugin object as scope for the callback
         success : t.processResponse,
         error : function( type, req, o ) {
          if (req.status != 200) {
            t.core.setProgressState(0);
            t.core.db.warn("Ajax call not successful.");
            t.core.db.log("Type: ",type);
            t.core.db.log("Status: " + req.status + ' ' + req.statusText);
          }
         }
       });
    },
    

    // process a successful  ajax response
    processResponse : function(data, req, o) {
      var t = this, ed = tinymce.activeEditor;
      
      t.core.db.log("Recieved text annotations", data);

      t.core.setProgressState(0);
      data = (t.core._isObject(data)) ? data : tinymce.util.JSON.parse(data);
      if (data == undefined) {
        t.db.warn('No response.');
        return;
      } 
      
      if (data.rev != t.revision) return;  // this is not the current request
      
      // set all retrieved annotations
      tinymce.each(data.annos, function(anno) {
        t.core.setAnnotation(ed, anno, false);
      });
    
    },

    
    showLastLog : function(ticket) {
      var t = this;
      
      if (typeof ticket == 'undefined') ticket = this.lastTicket;
      if (!ticket) return;

      t.editor.windowManager.open({
        file : Drupal.settings.basePath + "/wisski/textanly/showlog/" + ticket,
        title : 'Last analysis log',
        width : 800,
        height : 600,
        inline : 1,
        scrollbars : true,
        resizable : true,
        maximizable : true,
        close_previous : true,
      }, {
        logs : function() {
          
        },
        ticket : ticket,
        url : t.logCallbackURL
      });


    }


  });

  tinymce.PluginManager.add('wisskiTextAnly', tinymce.plugins.wisskiTextAnly);
 })();
