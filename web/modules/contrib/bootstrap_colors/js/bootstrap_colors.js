(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.barrio_colors = {
    attach: function (context, settings) {
      var primary_drupal = settings.bootstrap_colors === 'undefined' ? "#e91e63" : settings.bootstrap_colors.primary_shade;
      var accent_drupal = settings.bootstrap_colors === 'undefined' ? "#5062c2" : settings.bootstrap_colors.accent_shade;

    $("#primary").bind("keyup change", function() {
      colorChange($(this).val());
    });
    $("#accent").bind("keyup change", function() {
      accentChange($(this).val());
    });

    $('#primary-wrap').colorpicker({format: 'hex', color: primary_drupal}).on('changeColor.colorpicker', function(event){
      colorChange(event.color.toHex());
    });
    colorChange(primary_drupal);

    $('#accent-wrap').colorpicker({format: 'hex', color: accent_drupal}).on('changeColor.colorpicker', function(event){
      accentChange(event.color.toHex());
    });
    accentChange(accent_drupal);

    $(".primary").click(function() {
      $( ".primary" ).removeClass( "active" );
	  $(this).addClass( "active" );
      $("#primary-shade").val($(this).text()).css("background", $(this).text());
      var colors = getColorsArray(".primary");
      var colorindex = jQuery.inArray( $(this).text(), colors );
      var primaryLight = (colorindex < 4) ? "#ffffff" : colors[colorindex - 4];
      var primaryDark = (colorindex > 7) ? "#000000" : colors[colorindex + 2];
      $("#primary-light").val(primaryLight).css("background", primaryLight);
      $("#primary-dark").val(primaryDark).css("background", primaryDark);
      applyPrimaryColors ($(this).text(), primaryLight, primaryDark);
    });

    $(".accent").click(function() {
      $( ".accent" ).removeClass( "active" );
	  $(this).addClass( "active" );
      $("#accent-shade").val($(this).text()).css("background", $(this).text());
      var colors = getColorsArray(".accent");
      var colorindex = jQuery.inArray( $(this).text(), colors );
      var accentLight = (colorindex < 1) ? colors[0] : colors[colorindex - 1];
      var accentDark = (colorindex > 2) ? "#000000" : colors[colorindex + 1];
      $("#accent-light").val(accentLight).css("background", accentLight);
      $("#accent-dark").val(accentDark).css("background", accentDark);
      applyAccentColors ($(this).text(), accentLight, accentDark);
    });
    
    $( ".mdcolor" ).each(function( index ) {
      $( this ).css("background", $( this ).text());
    });

    $(".mdcolor").click(function() {
      var selection = 0;	
      $( ".mdcolor" ).each(function( index ) {
        if($( this ).hasClass( "mdprimary" )) selection++;
        if($( this ).hasClass( "mdaccent" )) selection++;
      });
      switch(selection) {
        case 2:
          $( ".mdcolor" ).removeClass( "mdprimary" ).removeClass( "mdaccent" )
        case 0:
          $(this).addClass( "mdprimary" );
          $('#primary-wrap').colorpicker('setValue', $(this).text());
          colorChange($(this).text());
          break;
        case 1:
          $(this).addClass( "mdaccent" );
          $('#accent-wrap').colorpicker('setValue', $(this).text());
          accentChange($(this).text());
          break;
      }
    });
    $('#getpalletes').click(function() {
      getColourLovers('http://www.colourlovers.com/api/palettes?format=json&jsonCallback=?');
	});
    $('#getpalletestop').click(function() {
      getColourLovers('http://www.colourlovers.com/api/palettes/top?format=json&jsonCallback=?');
	});
    $('#getpalletesnew').click(function() {
      getColourLovers('http://www.colourlovers.com/api/palettes/new?format=json&jsonCallback=?');
	});
    $('#getpalletesrandom').click(function() {
      getColourLovers('http://www.colourlovers.com/api/palettes/random?format=json&jsonCallback=?');
	});
    }
  };

  function colorChange(hex) {
    var tiny = tinycolor(hex);
    $( ".primary" ).removeClass( "active" );
//    $('#primary-wrap').colorpicker({'setValue': tiny.toHexString()})
//    $("#primary-output").css("background", tiny.toHexString());
    var colors = computeColors(hex);
    $("#c50").text(colors[0]).css("background", colors[0]);
    $("#c100").text(colors[1]).css("background", colors[1]);
    $("#c200").text(colors[2]).css("background", colors[2]);
    $("#c300").text(colors[3]).css("background", colors[3]);
    $("#c400").text(colors[4]).css("background", colors[4]);
    $("#c500").text(colors[5]).css("background", colors[5]);
    $("#c600").text(colors[6]).css("background", colors[6]);
    $("#c700").text(colors[7]).css("background", colors[7]);
    $("#c800").text(colors[8]).css("background", colors[8]);
    $("#c900").text(colors[9]).css("background", colors[9]);
  }

  function accentChange(hex) {
    var tiny = tinycolor(hex);
    $( ".accent" ).removeClass( "active" )
    $("#accent-output").css("background", tiny.toHexString());
    var colors = computeColors(hex);
    $("#a100").text(colors[10]).css("background", colors[10]);
    $("#a200").text(colors[11]).css("background", colors[11]);
    $("#a400").text(colors[12]).css("background", colors[12]);
    $("#a700").text(colors[13]).css("background", colors[13]);
  }

  function computeColors(hex)
  {
    hex = getLightestBase(hex);
  // Return array of color objects.
    return [
      tinycolor(hex).lighten( 52 ).toHexString(),
      tinycolor(hex).lighten( 37 ).toHexString(),
      tinycolor(hex).lighten( 26 ).toHexString(),
      tinycolor(hex).lighten( 12 ).toHexString(),
      tinycolor(hex).lighten( 6 ).toHexString(),
      tinycolor(hex).toHexString(),
      tinycolor(hex).darken( 6 ).toHexString(),
      tinycolor(hex).darken( 12 ).toHexString(),
      tinycolor(hex).darken( 18 ).toHexString(),
      tinycolor(hex).darken( 24 ).toHexString(),
      tinycolor(hex).lighten( 52 ).toHexString(),
      tinycolor(hex).lighten( 37 ).toHexString(),
      tinycolor(hex).lighten( 6 ).toHexString(),
      tinycolor(hex).darken( 12 ).toHexString()
    ];
  };

  // Function to prevent lightest
  // colors from turning into white.
  // Done by darkening base until the
  // brightest color is no longer #fff.
  function getLightestBase(base)
  {
    if( tinycolor( base ).lighten( 52 ).toHexString().toLowerCase() == "#ffffff" )
    {
      return getLightestBase( tinycolor( base ).darken( 6 ).toHexString() );
    }
    else
    {
      return base;
    }
  };

  function getColorsArray (type)
  {
    var cells = $(type);
	var colors = new Array();
	$.each( cells, function( key, cell ) {
      colors.push(cell.innerHTML);
    });
    return colors;
  }

  function getColourLovers(url) {
    $.ajax({
      url: url,
      dataType: 'jsonp'
    }).then(success);
  }

  function success (data) {
    var results = $('#colourlovers-table tbody');
    $(results).empty();
    data.forEach(function(item) {
      var title = document.createElement('tr');
      var el = document.createElement('tr');
      $(title).append('<td colspan="5">' + item.title + '</td>');
      item.colors.forEach(function(color) {
        var td = document.createElement('td');
        var wrap = document.createElement('div');
        $(wrap).addClass("clcolor").text('#' + color.toLowerCase());
        $(wrap).each(function( index ) {
          $( this ).css("background", $( this ).text());
        });
        $(wrap).click(function() {
          var selection = 0;	
          $( ".clcolor" ).each(function( index ) {
            if($( this ).hasClass( "clprimary" )) selection++;
            if($( this ).hasClass( "claccent" )) selection++;
          });
          switch(selection) {
            case 2:
              $( ".clcolor" ).removeClass( "clprimary" ).removeClass( "claccent" )
            case 0:
              $(this).addClass( "clprimary" );
              $('#primary-wrap').colorpicker('setValue', $(this).text());
              colorChange($(this).text());
              break;
            case 1:
              $(this).addClass( "claccent" );
              $('#accent-wrap').colorpicker('setValue', $(this).text());
              accentChange($(this).text());
              break;
          }
        });
        $(td).append(wrap);
        $(el).append(td);
     });
     results.append(title);
     results.append(el);
   });
  }

  function applyPrimaryColors (shade, light, dark) {
    $('.navbar-top').css('background-color', dark);
    $('.navbar-inverse').css('background-color', shade);
    $('.bg-inverse').css('background-color', shade);
    $('.navbar a').css('color', light);
    $( ".navbar a" ).hover(
      function() {
        $( this ).css('color', '#fff');
      }, function() {
        $( this ).css('color', light);
      }
	);
    $('.btn-default').css('background-color', light).css('border-color', shade).css('color', dark);
    $( ".btn-default" ).hover(
      function() {
        $( this ).css('background-color', shade).css('border-color', dark).css('color', light);
      }, function() {
        $( this ).css('background-color', light).css('border-color', shade).css('color', dark);
      }
	);
  }

  function applyAccentColors (shade, light, dark) {
    $('.jumbotron').css('background-color', light);
    $('footer a').css('color', shade);
    $( "footer a" ).hover(
      function() {
        $( this ).css('color', dark);
      }, function() {
        $( this ).css('color', shade);
      }
    );
    $('.btn-primary').css('background', shade).css('border-color', dark);
    $( ".btn-primary" ).hover(
      function() {
        $( this ).css('background-color', dark);
      }, function() {
        $( this ).css('background-color', shade);
      }
    );
  }

})(jQuery, Drupal);
