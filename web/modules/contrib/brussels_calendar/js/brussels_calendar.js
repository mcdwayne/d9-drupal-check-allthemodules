  var locale = window.navigator.userLanguage || window.navigator.language;
  moment.locale(locale);

  var today = moment();

  function Calendar(selector, events, settings, dayClickFunction, eventClickFunction) {
  	this.settings = settings;
  	this.dayClickFunction=dayClickFunction;
  	this.eventClickFunction=eventClickFunction;
 	
  	events.sort(function(a, b){
  		return a.eventName.localeCompare(b.eventName);
  	});
  	
    events.forEach(function(ev) {
     ev.date = moment.unix(ev.date);
     
     if(ev.date_end){
    	ev.date_end = moment.unix(ev.date_end);
     }else{
    	ev.date_end = moment(ev.date);
     }
    });
    
    this.events = events;
    
    // Add classes for event category colors.
    var style=document.createElement('style');
	style.type='text/css';
	
	
	
	
	var categoryColors = this.events.reduce(function(categoryClassesPass, ev) {
       if(categoryClassesPass.indexOf(ev.color)===-1){
       	categoryClassesPass.push(ev.color);
       }
    	
        return categoryClassesPass;
      }.bind(this), []);
	
	// console.log('categoryColors', categoryColors);
	
	
	var categoryColorsClassesCounter=0;
	
	//Table with default mapping.
	var categoryColorsClassesTable={
		'default': 'default'
	};
	
	var cssLines=categoryColors.map(function(categoryColor){
		var categoryColorClass='category_color_'+categoryColorsClassesCounter;
		
		categoryColorsClassesTable[categoryColor]=categoryColorClass;
		
		categoryColorsClassesCounter++;
		
		// For day, listing, legend.
		return '.brussels_calendar .day-events .'+categoryColorsClassesTable[categoryColor]+' { background: '+categoryColor+'; }'+"\n"+
			'.brussels_calendar .event-category.'+categoryColorsClassesTable[categoryColor]+' { background: '+categoryColor+'; }'+"\n"+
			'.brussels_calendar .entry.'+categoryColorsClassesTable[categoryColor]+':after { background: '+categoryColor+'; }';
	}).join("\n");
	
	this.categoryColorsClassesTable=categoryColorsClassesTable;

	 console.log('cssLines', cssLines);
	
	if(style.styleSheet){
		style.styleSheet.cssText=cssLines;
	}else{
	    style.appendChild(document.createTextNode(cssLines));
	}
	document.getElementsByTagName('head')[0].appendChild(style);
    
    // console.log('calendar events', events);
  	
    this.el = document.querySelector(selector);
    this.el.className+='brussels_calendar';
    
    this.current = moment().date(1);
    this.draw();
    
    var current = document.querySelector('.today');
    if(current) {
      var self = this;
      window.setTimeout(function() {
        self.openDay(current, true);
      }, 500);
    }
  }

  Calendar.prototype.draw = function() {
    //Create Header
    this.drawHeader();

    //Draw Month
    this.drawMonth();

	if(this.settings.legend_enabled){
		// Draw Legend
	    this.drawLegend();
	}
  }

  Calendar.prototype.drawHeader = function() {
    var self = this;
    if(!this.header) {
      //Create the header elements
      this.header = createElement('div', 'header');
      this.header.className = 'header';

      this.title = createElement('h1');

      var right = createElement('div', 'right');
      right.addEventListener('click', function() { self.nextMonth(); });

      var left = createElement('div', 'left');
      left.addEventListener('click', function() { self.prevMonth(); });

      //Append the Elements
      this.header.appendChild(this.title); 
      this.header.appendChild(right);
      this.header.appendChild(left);
      this.el.appendChild(this.header);
    }

    this.title.innerHTML = this.current.format('MMMM YYYY');
  }

  Calendar.prototype.drawMonth = function() {
    var self = this;
    
    
    
    if(this.month) {
      this.oldMonth = this.month;
      this.oldMonth.className = 'month out ' + (self.next ? 'next' : 'prev');
      this.oldMonth.addEventListener('webkitAnimationEnd', function() {
        self.oldMonth.parentNode.removeChild(self.oldMonth);
        self.month = createElement('div', 'month');
        self.backFill();
        self.currentMonth();
        self.fowardFill();
        self.el.appendChild(self.month);
        window.setTimeout(function() {
          self.month.className = 'month in ' + (self.next ? 'next' : 'prev');
        }, 16);
      });
    } else {
        this.month = createElement('div', 'month');
        this.el.appendChild(this.month);
        this.backFill();
        this.currentMonth();
        this.fowardFill();
        this.month.className = 'month new';
    }
  }

  Calendar.prototype.backFill = function() {
    var clone = this.current.clone();
    var dayOfWeek = clone.weekday();

    if(!dayOfWeek) { return; }

    clone.subtract(dayOfWeek+1, 'days');

    for(var i = dayOfWeek; i > 0 ; i--) {
      this.drawDay(clone.add(1, 'days'));
    }
  }

  Calendar.prototype.fowardFill = function() {
    var clone = this.current.clone().add(1, 'months').subtract(1, 'days');
    var dayOfWeek = clone.weekday();

    if(dayOfWeek === 6) { return; }

    for(var i = dayOfWeek; i < 6 ; i++) {
      this.drawDay(clone.add(1, 'days'));
    }
  }

  Calendar.prototype.currentMonth = function() {
    var clone = this.current.clone();

    while(clone.month() === this.current.month()) {
      this.drawDay(clone);
      clone.add(1, 'days');
    }
  }

  Calendar.prototype.getWeek = function(day) {
    if(!this.week || day.weekday() === 0) {
      this.week = createElement('div', 'week');
      this.month.appendChild(this.week);
    }
  }

  Calendar.prototype.drawDay = function(day) {
    var self = this;
    this.getWeek(day);

    //Outer Day
    var outer = createElement('div', this.getDayClass(day));
    outer.addEventListener('click', function() {
      self.openDay(this, false);
    });

    //Day Name
    var name = createElement('div', 'day-name', day.format('ddd'));

    //Day Number
    var number = createElement('div', 'day-number', day.format('DD'));

    //Events
    var events = createElement('div', 'day-events');
    this.drawEvents(day, events);

    outer.appendChild(name);
    outer.appendChild(number);
    outer.appendChild(events);
    this.week.appendChild(outer);
  }

  Calendar.prototype.isTodaysEvent = function(day, ev) {
  	
		 /*if(ev.date.isSame(day, 'day')) {
          memo.push(ev);
        }*/
        // console.log('ev', ev);
        var dateBefore=moment(ev.date).subtract(1, 'days');
    	
    	var dateAfter=moment(ev.date_end).add(1, 'days');
    	
    	if(day.isBetween(dateBefore, dateAfter, 'day')){
    		// console.log(day.format()+' is between '+dateBefore.format()+' and '+dateAfter.format());
    		return true;
    	}
    	return false;
  	
  }
  Calendar.prototype.drawEvents = function(day, element) {
    if(day.month() === this.current.month()) {
      var todaysEvents = this.events.reduce(function(memo, ev) {
       
    	if(this.isTodaysEvent(day, ev)){
    		memo.push(ev);
    	}
    	
        return memo;
      }.bind(this), []);
      
      var calendar = this;

      todaysEvents.forEach(function(ev) {
        var evSpan = createElement('span', calendar.categoryColorsClassesTable[ev.color]);
        element.appendChild(evSpan);
      });
    }
  }

  Calendar.prototype.getDayClass = function(day) {
    classes = ['day'];
    if(day.month() !== this.current.month()) {
      classes.push('other');
    } else if (today.isSame(day, 'day')) {
      classes.push('today');
    }
    return classes.join(' ');
  }

  Calendar.prototype.openDay = function(el, dontRunDayClickFunction) {
  	
    var details, arrow;
    var dayNumber = +el.querySelectorAll('.day-number')[0].innerText || +el.querySelectorAll('.day-number')[0].textContent;
    var day = this.current.clone().date(dayNumber);

  	if(!dontRunDayClickFunction && this.dayClickFunction){
  		this.dayClickFunction(day);
  	}
  	
    var currentOpened = document.querySelector('.brussels_calendar .details');

    //Check to see if there is an open detais box on the current row
    if(currentOpened && currentOpened.parentNode === el.parentNode) {
      details = currentOpened;
      arrow = document.querySelector('.arrow');
    } else {
      //Close the open events on differnt week row
      //currentOpened && currentOpened.parentNode.removeChild(currentOpened);
      if(currentOpened) {
        currentOpened.addEventListener('webkitAnimationEnd', function() {
          currentOpened.parentNode.removeChild(currentOpened);
        });
        currentOpened.addEventListener('oanimationend', function() {
          currentOpened.parentNode.removeChild(currentOpened);
        });
        currentOpened.addEventListener('msAnimationEnd', function() {
          currentOpened.parentNode.removeChild(currentOpened);
        });
        currentOpened.addEventListener('animationend', function() {
          currentOpened.parentNode.removeChild(currentOpened);
        });
        currentOpened.className = 'details out';
      }

      //Create the Details Container
      details = createElement('div', 'details in');

      //Create the arrow
      var arrow = createElement('div', 'arrow');

      //Create the event wrapper

      details.appendChild(arrow);
      el.parentNode.appendChild(details);
    }

    var todaysEvents = this.events.reduce(function(memo, ev) {
    	
    	if(this.isTodaysEvent(day, ev)){
    		memo.push(ev);
    	}
      /*if(ev.date.isSame(day, 'dayisTodaysEvent')) {
      	
        memo.push(ev);
      }*/
      return memo;
    }.bind(this), []);

    this.renderEvents(todaysEvents, details);

    arrow.style.left = el.offsetLeft - el.parentNode.offsetLeft + 27 + 'px';
  }

  Calendar.prototype.renderEvents = function(events, ele) {
  	var self = this;
    //Remove any events in the current details element
    var currentWrapper = ele.querySelector('.events');
    var wrapper = createElement('div', 'events in' + (currentWrapper ? ' new' : ''));

    events.forEach(function(ev) {
      var div = createElement('div', 'event');
      var square = createElement('div', 'event-category ' + self.categoryColorsClassesTable[ev.color]);
      var a = createElement('a', '', ev.eventName);
      
        	
      if(self.eventClickFunction){
      	a.onclick=self.eventClickFunction;
      }else{
		a.setAttribute('href', ev.url);
      }
      div.appendChild(square);
      div.appendChild(a);
      wrapper.appendChild(div);
    });

    if(!events.length) {
      var div = createElement('div', 'event empty');
      var span = createElement('span', '', this.settings.no_events);

      div.appendChild(span);
      wrapper.appendChild(div);
    }

    if(currentWrapper) {
      currentWrapper.className = 'events out';
      currentWrapper.addEventListener('webkitAnimationEnd', function() {
        currentWrapper.parentNode.removeChild(currentWrapper);
        ele.appendChild(wrapper);
      });
      currentWrapper.addEventListener('oanimationend', function() {
        currentWrapper.parentNode.removeChild(currentWrapper);
        ele.appendChild(wrapper);
      });
      currentWrapper.addEventListener('msAnimationEnd', function() {
        currentWrapper.parentNode.removeChild(currentWrapper);
        ele.appendChild(wrapper);
      });
      currentWrapper.addEventListener('animationend', function() {
        currentWrapper.parentNode.removeChild(currentWrapper);
        ele.appendChild(wrapper);
      });
    } else {
      ele.appendChild(wrapper);
    }
  }

  Calendar.prototype.drawLegend = function() {
  	var self = this;
    var legend = createElement('div', 'legend');
    var calendars = this.events.map(function(e) {
      return e.calendar + '|' + e.color;
    }).reduce(function(memo, e) {
      if(memo.indexOf(e) === -1) {
        memo.push(e);
      }
      return memo;
    }, []).forEach(function(e) {
      var parts = e.split('|');
      var content = parts[0];
      var color = parts[1];
      
      // TODO debug
      // var entry = createElement('span', 'entry ' +  self.categoryColorsClassesTable[color], content);
      var entry = createElement('span', 'entry ' +  self.categoryColorsClassesTable[color], content);
      
      legend.appendChild(entry);
    });
    this.el.appendChild(legend);
  }

  Calendar.prototype.nextMonth = function() {
    this.current.add(1, 'months');
    this.next = true;
    this.draw();
  }

  Calendar.prototype.prevMonth = function() {
    this.current.subtract(1, 'months');
    this.next = false;
    this.draw();
  }

  window.Calendar = Calendar;

  function createElement(tagName, className, innerText) {
    var ele = document.createElement(tagName);
    if(className) {
      ele.className = className;
    }
    if(innerText) {
      ele.innderText = ele.textContent = innerText;
    }
    return ele;
  }

