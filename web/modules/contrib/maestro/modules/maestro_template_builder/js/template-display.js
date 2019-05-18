//has the required JS functions to display a raphael based display page
var dragger = function () {
      this.ox = this.type == "rect" ? this.attr("x") : this.attr("cx");
      this.oy = this.type == "rect" ? this.attr("y") : this.attr("cy");
  },
  move = function (dx, dy) {
    try {
      var fromtask = {}, lineCount = 0, fromtasks = [];;
      var newx = 0, newy = 0;

      //lets first see if this thing is going out of bounds before we do anything
      if(Number(this.ox+dx) < 0) dx = 0 - this.ox;
      if(Number(this.ox+dx+this.getBBox().width) > r.width) dx = Number(r.width - this.ox - this.getBBox().width);

      if(Number(this.oy+dy) < 0) dy = 0 - this.oy;
      if(Number(this.oy+dy+this.getBBox().height) > r.height) dy = Number(r.height - this.oy - this.getBBox().height);

      //snap to grid -- set dx to the next closest 10 pixel
      newx = Math.ceil((this.ox + dx) / 10) * 10;
      newy = Math.ceil((this.oy + dy) / 10) * 10;

      var att = this.type == "rect" ? {x: newx, y: newy} : {cx: newx, cy: newy};
      this.set.attr(att);  //only the main task body has 'set' on it and thus this will throw a typeError for the other non-draggable elements
      this.parent.raphaelTextName.transform('t10,6');
      this.parent.raphaelTaskText.transform('t2,53');

      if(this.parent.raphaelStatusTaskText != null && this.parent.raphaelStatusTaskText != '') { //move the status bubble
        this.parent.raphaelStatusTaskText.transform('t85,35');
      }
      if(this.parent.statusbubble != null) {
        this.parent.statusbubble.attr('cx', newx + 88);  //transform seemed to not move the circle. Just move it absolutely.
        this.parent.statusbubble.attr('cy', newy + 35);
      }

      this.parent.editrectraphael.transform('t-8,-8');
      this.parent.editimageraphael.transform('t-5,-5');


      for(cntr=0; cntr < this.parent.lines.length; cntr++) {
        try{
          this.parent.lines[cntr].remove();
        }
        catch(e) {}  //don't care right now as this is an "undefined" that crept in during a failed move.
      }
      this.parent.lines.splice(0);
      this.parent.lines = [];
      maestro_draw_one_task_line(this, r);
      //now redraw lines that this points from
      for(cntr=0; cntr < this.parent.pointedfrom.length; cntr++) {
        if(this.parent.pointedfrom[cntr] != undefined) {
          fromtask = maestro_get_task_reference(this.parent.pointedfrom[cntr]);
          for(cntr2=0; cntr2 < fromtask.raphael.parent.lines.length; cntr2++) {
            try{
              fromtask.raphael.parent.lines[cntr2].remove();
            }
            catch(e) {
              //we're trying to remove, most likely, a blank value due to the value being removed by a remove lines at some point.
            }
          }
          fromtask.raphael.parent.lines.splice(0);
          fromtask.raphael.parent.lines = [];
          fromtasks.push(fromtask);
        }
      }
      //now remove the pointedfrom
      this.parent.pointedfrom.splice(0);
      for(cntr=0; cntr < fromtasks.length; cntr++) {
        maestro_draw_one_task_line(fromtasks[cntr], r);
      }
      r.safari();
    }
    catch(e) {  //not a portion of the draggable interface.  ignore it.  this is stuff like the text etc.
      return;
    }
  },
  up = function () {
      var parent = [];
      var alreadyExists = false;
      //this section of code helps determine what it is we're actually dealing with.
      //The nesting of the parent/raphael element can be brought one level higher when new tasks are generated on the fly
      //the base is this.parent, but we also get full nesting when we have a task loaded by the initial UI
      //we also have various sub-elements (like the edit button, lines around the tasks etc) that require us to
      //rifle through the object to get down to the actual task body
      if(this.hasOwnProperty('parent')) { //at the very least this element should have a parent
        if(this.parent.hasOwnProperty('raphael')) {
          if(this.parent.raphael.hasOwnProperty('parent')) parent = this.parent.raphael.parent;
          else parent = this.parent;
        }
        else parent = this.parent;

        if(lineFrom != false && lineFrom != parent.id && parent != undefined) {
          var taskFrom = maestro_get_task_reference(lineFrom);
          var taskTo = maestro_get_task_reference(parent.id);
          alreadyExists = false;
          //so now check if the taskFrom already has a reference to taskTo!
          for(var cntr = 0; cntr<taskFrom.raphael.parent.to.length; cntr++) {
            if(taskFrom.raphael.parent.to[cntr] == parent.id) alreadyExists = true;
          }
          if(!alreadyExists) {  //so, you don't exist, therefore we draw it AND signal the ajax callback
            taskFrom.raphael.parent.lines.push(r.join(taskFrom.raphael, taskTo.raphael, '#000000'));
            taskTo.raphael.parent.pointedfrom.push(lineFrom);
            taskFrom.raphael.parent.to.push(parent.id);
            maestro_update_task_information(taskFrom);
            maestro_update_task_information(taskTo);

            jQuery('[name="task_line_from"]').val(lineFrom);
            jQuery('[name="task_line_to"]').val(parent.id);
            jQuery('#edit-draw-line-complete').trigger('mousedown');  //fires the ajax event wired to this button
          }
        }

        if(falseLineFrom != false && falseLineFrom != parent.id && parent != undefined) {

          var taskFrom = maestro_get_task_reference(falseLineFrom);
          var taskTo = maestro_get_task_reference(parent.id);
          alreadyExists = false;
          for(var cntr = 0; cntr<taskFrom.raphael.parent.falsebranch.length; cntr++) {
            if(taskFrom.raphael.parent.falsebranch[cntr] == parent.id) alreadyExists = true;
          }
          if(!alreadyExists) {
            taskFrom.raphael.parent.lines.push(r.join(taskFrom.raphael, taskTo.raphael, '#FF0000'));
            taskTo.raphael.parent.pointedfrom.push(falseLineFrom);
            taskFrom.raphael.parent.falsebranch.push(parent.id);
            maestro_update_task_information(taskFrom);
            maestro_update_task_information(taskTo);

            jQuery('[name="task_line_from"]').val(falseLineFrom);
            jQuery('[name="task_line_to"]').val(parent.id);
            jQuery('#edit-draw-false-line-complete').trigger('mousedown');  //fires the ajax event wired to this button
          }
        }

        if(lineFrom == false && parent != undefined) {  //only do this if we're not drawing lines
          var divtop, divleft, x, y;
          divtop = jQuery('#maestro_div_template').offset().top;
          divleft = jQuery('#maestro_div_template').offset().left;
          x=this.attrs.x;
          y=this.attrs.y;
          jQuery('[name="task_clicked"]').val(parent.id);

          jQuery('[name="task_top"]').val(y);
          jQuery('[name="task_left"]').val(x);
          jQuery('#edit-move-task-complete').trigger('mousedown'); //trigger the ajax event wired to this button
        }

        lineFrom = false;
        falseLineFrom = false;
        jQuery('.maestro-template-message-area').css('display', 'none');
        jQuery('.maestro-template-message-area').html('');

        this.animate({"fill-opacity": this.attrs['fill-opacity']}, 500);
      }
  };

/**
 * Create the Raphael join function that draws our custom lines
 */
Raphael.fn.join = function(from, to, colour) {
  var box1 = from.getBBox(),
      box2 = to.getBBox();
  var hypotenuse = [], hypotoffsets = [], pointsboxFrom = [], pointsboxTo = [], points = [];
  var deltax, deltay, calc;
  //so now we have 2 boxes, lets go midpoint by midpoint to find the shortest hypotenuse
  //go logically through the box model from top, right, bottom, left in terms of midpoints on the box model
  pointsboxFrom.push( [box1.x + box1.width/2, box1.y] );
  pointsboxTo.push( [box2.x + box2.width/2, box2.y] ); //top of each
  pointsboxFrom.push( [box1.x2, box1.y2 - box1.height/2] );
  pointsboxTo.push( [box2.x2, box2.y2 - box2.height/2] ); //right of each
  pointsboxFrom.push( [box1.x2 - box1.width/2, box1.y2] );
  pointsboxTo.push( [box2.x2 - box2.width/2, box2.y2] ); //bottom of each
  pointsboxFrom.push( [box1.x, box1.y + box1.height/2] );
  pointsboxTo.push( [box2.x, box2.y + box2.height/2] ); //left of each
  //now, loop thru the FROM points, comparing each point to the TO points, calculating the hypotenuse, recording which hypotenuse is shortest
  for(i=0; i<pointsboxFrom.length; i++) {
    for(x=0; x<pointsboxTo.length; x++) {
       deltax = Math.abs(pointsboxTo[x][0] - pointsboxFrom[i][0]);
       deltay = Math.abs(pointsboxFrom[i][1] - pointsboxTo[x][1]);
       calc = Math.pow( deltax,2) + Math.pow(deltay , 2);
       hypotenuse.push(calc);
       hypotoffsets.push( [i,x] );
    }
  }
  calc = 0;
  points = [];
  //ok, now, for each hypotenuse, see which is shortest, then THAT becomes the point set to use
  for(i=0; i<hypotenuse.length; i++) {
    if(hypotenuse[i] < calc || calc == 0) {
      calc = hypotenuse[i];
      points = hypotoffsets[i];
    }
  }
  var path = ["M",pointsboxFrom[points[0]][0],pointsboxFrom[points[0]][1], "L", pointsboxTo[points[1]][0], pointsboxTo[points[1]][1]].join(",");
  var line;
  if(typeof colour != 'string') {
    line = this.path(path).attr({stroke: '#000000', 'stroke-width': 3 ,'arrow-end': 'classic-wide-long'});
  }
  else {
    line = this.path(path).attr({stroke: colour, 'stroke-width': 3 ,'arrow-end': 'classic-wide-long'});
  }
  line.toBack();
  line.click(maestro_line_click);
  line.from = from.parent.id;
  line.to = to.parent.id;
  return line;
}

function maestro_draw_task(taskInformation) {
  var task = {};
  var eltext, sampleText, tasktext, statustext;

  task.raphael = r.rect(Number(taskInformation.left), Number(taskInformation.top), 100, 60, 2);
  task.raphael.parent = taskInformation;
  task.raphael.parent.pointedfrom = [];
  //create a rect around the top
  task.raphael.parent.toprectraphael = r.rect(Number(taskInformation.left), Number(taskInformation.top), 100, 15, 2).attr({fill: '#a0a0a0',  'fill-opacity': 90});
  sampleText = taskInformation.taskname.substring(0,14);
  if(sampleText != taskInformation.taskname) {
    sampleText += '...';
  }
  eltext = r.text(Number(taskInformation.left)+10, Number(taskInformation.top)+6, sampleText).attr({'font-weight': 'bold', fill: '#000000', 'text-anchor': 'start'});
  task.raphael.attr({text: eltext });

  //UI label.  Push it to the bottom left corner
  tasktext = r.text(Number(taskInformation.left)+2, Number(taskInformation.top)+53, taskInformation.uilabel).attr({'font-weight': 'bold', fill: '#000000', 'text-anchor': 'start'});
  task.raphael.attr({text: tasktext });
  task.raphael.parent.raphaelTextName = eltext;
  task.raphael.parent.raphaelTaskText = tasktext;

  //status data if any
  task.raphael.parent.statusbubble = null;
  statustext = r.text(Number(taskInformation.left)+85, Number(taskInformation.top)+35, '').attr({"cursor": "default", "title": '', 'font-weight': 'bold', fill: '#000000', 'text-anchor': 'start'});
  task.raphael.parent.raphaelStatusTaskText = statustext;
  if(taskInformation.participate_in_workflow_status_stage) {
    task.raphael.parent.statusbubble = r.circle(Number(taskInformation.left)+88,Number(taskInformation.top)+35,9).attr({"cursor": "default", "fill" : "#a0a0a0", "title": taskInformation.workflow_status_stage_message});
    //statustext = r.text(Number(taskInformation.left)+85, Number(taskInformation.top)+35, taskInformation.workflow_status_stage_number).attr({"cursor": "default", "title": taskInformation.workflow_status_stage_message, 'font-weight': 'bold', fill: '#000000', 'text-anchor': 'start'});
    task.raphael.parent.raphaelStatusTaskText.attr({'text': taskInformation.workflow_status_stage_number, "title": taskInformation.workflow_status_stage_message });
  }

  task.raphael.toFront();
  if(task.raphael.parent.statusbubble) { // move the status bubble to the foreground
    task.raphael.parent.statusbubble.toFront();
    task.raphael.parent.raphaelStatusTaskText.toFront();
  }
  //create edit rect around the top left
  task.raphael.parent.editrectraphael = r.rect(Number(taskInformation.left) - 8, Number(taskInformation.top)-8, 16, 16, 2).attr({fill: '#555555',  'fill-opacity': 100});
  //insert the svg hamburger in to the edit box
  task.raphael.parent.editimageraphael = r.image(drupalSettings.baseURL + "core/misc/icons/ffffff/hamburger.svg",Number(taskInformation.left) - 5, Number(taskInformation.top)-5, 10, 10);
  task.raphael.parent.editimageraphael.click(maestro_handle_edit_click); //make the image clickable
  task.raphael.parent.editrectraphael.click(maestro_handle_edit_click);  //make the rectangle clickable
  task.raphael.parent.editimageraphael.parent = task; //set our parent object as the task so when we click, we know who we are
  task.raphael.parent.editrectraphael.parent = task;  //same for the rectangle.  Set the parent as the task so we know who we are
  //here we create a Raphael set and store that in the task object for use later when dragging
  task.raphael.set = r.set(
                  task.raphael,
                  eltext,
                  tasktext,
                  statustext,
                  task.raphael.parent.toprectraphael,
                  task.raphael.parent.editrectraphael,
                  task.raphael.parent.editimageraphael,
                  task.raphael.parent.statusbubble)
                  .drag(move, dragger, up);

  task.raphael.attr({fill: '#f0f0f0', stroke: drupalSettings.maestroTaskColours[taskInformation.type] ,"fill-opacity": 0.1, "stroke-width": 2, cursor: "move", title: taskInformation.taskname});
  return task;
}

function maestro_draw_all_lines(maestroTasks, r) {
  for (var i = 0; i < maestroTasks.length; i++) {
    maestro_draw_one_task_line(maestroTasks[i], r);
  }
  return maestroTasks;
}

function maestro_draw_one_task_line(incomingTask, r) {
  var to, pointers = [], flag, i2, task = {};
  if(incomingTask.hasOwnProperty('raphael')) task = incomingTask;
  else task.raphael = incomingTask;

  if(typeof task.raphael.parent.to != undefined) {
    for(i2 = 0; i2 < task.raphael.parent.to.length; i2++) {
      to = maestro_get_task_reference(task.raphael.parent.to[i2]);
      if(to != undefined) {
        task.raphael.parent.lines.push(r.join(task.raphael,to.raphael));
      }
      //now, remove any duplicates we may have from the pointedfrom
      if(to != undefined) {  //tasks that have no TO lines or the end task
        if(to.raphael.parent.pointedfrom.length == 0) {
          to.raphael.parent.pointedfrom.push(task.raphael.parent.id);
        }
        else {
          for(var cntr=0; cntr < to.raphael.parent.pointedfrom.length; cntr++) {
            pointers.push(to.raphael.parent.pointedfrom[cntr]);
          }
          to.raphael.parent.pointedfrom.splice(0);
          flag = false;
          for(var cntr=0; cntr < pointers.length; cntr++) {
            if(pointers[cntr] == task.raphael.parent.id && flag == false) {
              flag = true;
              to.raphael.parent.pointedfrom.push(pointers[cntr]);
            }
            if(pointers[cntr] != task.raphael.parent.id) {
              to.raphael.parent.pointedfrom.push(pointers[cntr]);
            }
          }
          if(flag == false) to.raphael.parent.pointedfrom.push(task.raphael.parent.id);
        }
      }
    }

    //now for the false branches
    //TODO: need to remove the duplicates that are generated here
    pointers = [];
    for(i2 = 0; i2 < task.raphael.parent.falsebranch.length; i2++) {
      to = maestro_get_task_reference(task.raphael.parent.falsebranch[i2]);
      if(to != undefined) {
        task.raphael.parent.lines.push(r.join(task.raphael,to.raphael, '#ff0000'));
        to.raphael.parent.pointedfrom.push(task.raphael.parent.id);
      }
      //now, remove any duplicates we may have from the pointedfrom
      if(to != undefined) {  //tasks that have no TO lines or the end task
        if(to.raphael.parent.pointedfrom.length == 0) {
          to.raphael.parent.pointedfrom.push(task.raphael.parent.id);
        }
        else {
          for(var cntr=0; cntr < to.raphael.parent.pointedfrom.length; cntr++) {
            pointers.push(to.raphael.parent.pointedfrom[cntr]);
          }
          to.raphael.parent.pointedfrom.splice(0);
          flag = false;
          for(var cntr=0; cntr < pointers.length; cntr++) {
            if(pointers[cntr] == task.raphael.parent.id && flag == false) {
              flag = true;
              to.raphael.parent.pointedfrom.push(pointers[cntr]);
            }
            if(pointers[cntr] != task.raphael.parent.id) {
              to.raphael.parent.pointedfrom.push(pointers[cntr]);
            }
          }
          if(flag == false) to.raphael.parent.pointedfrom.push(task.raphael.parent.id);
        }
      }

    }
  }
  return task;
}

function maestro_handle_edit_click(obj, absx, absy) {
  var divtop, divleft, x, y, capabilities, i, g;
  divtop = jQuery('#maestro_div_template').offset().top;
  divleft = jQuery('#maestro_div_template').offset().left;
  x=absx - divleft;
  y=absy - divtop + 150;
  capabilities = this.parent.raphael.parent.capabilities;
  //hide all capabilities in the menu first:
  jQuery('#edit-menu > div').children().each(function() {
    g = this.getAttribute('maestro_capabilities_id');
    if(g != null && g.startsWith('maestro_template_')) {
      jQuery('#' + this.id).hide();
    }
  });
  //now show the ones for this task
  jQuery('#edit-menu > div').children().each(function() {
    g = this.getAttribute('maestro_capabilities_id');
    if(g != null && g.startsWith('maestro_template_')) {
      for( i=0; i < capabilities.length; i++) {
        if(g == capabilities[i]) jQuery('#' + this.id).show();
      }
    }
  });

  jQuery('#maestro-task-menu').css('top', y + 'px');
  jQuery('#maestro-task-menu').css('left', x + 'px');
  jQuery('#maestro-task-menu').css('display', 'block');
  jQuery('[name="task_clicked"]').val(this.parent.raphael.parent.id);
}

function maestro_line_click(obj) {
  var from = this.from;
  var to = this.to;

  //TODO: Optional: open a dialogue box to ask whether to give the option to delete this line
  //this has not been implemented by the server-side as of yet and must currently be done
  //by the remove lines button on the task edit

}

function maestro_submit_form(event) {
  var x = confirm('Remove this task?');
  if(x) {
    jQuery('#edit-remove-task-complete').trigger('mousedown');
  }
}

function maestro_get_task_reference(taskid) {
  for(var cntr=0; cntr < maestroTasks.length; cntr++) {
    if(maestroTasks[cntr].raphael.parent.id == taskid) {
      return maestroTasks[cntr];
    }
  }
}

function maestro_update_task_information(task) {
  if(task != undefined) {
    for(var cntr=0; cntr < maestroTasks.length; cntr++) {
      if(maestroTasks[cntr].raphael.parent.id == task.raphael.parent.id) {
        maestroTasks.splice(cntr, 1);
        maestroTasks.push(task);
      }
    }
  }
}

function maestro_remove_task_lines(taskToRemoveLinesFrom) {
  var cntr, cntr2, fromtask, totask;
  var task = maestro_get_task_reference(taskToRemoveLinesFrom);

  //each line tells us who is connected on both ends.
  //task.raphael.parent.lines[cntr].from and .to tell us who we need to pick on specifically for the pointedfrom aspect
  for(cntr=0; cntr < task.raphael.parent.lines.length; cntr++) {
    totask = maestro_get_task_reference(task.raphael.parent.lines[cntr].to);  //get WHO we point to and remove their pointedfrom references to us
    for(cntr2=0; cntr2<totask.raphael.parent.pointedfrom.length; cntr2++) {
      if(totask.raphael.parent.pointedfrom[cntr2] == task.raphael.parent.id) {
        totask.raphael.parent.pointedfrom.splice(cntr2,1);
        //totask.raphael.parent.pointedfrom.splice(cntr2,1);
        maestro_update_task_information(totask);
      }
    }
    task.raphael.parent.lines[cntr].remove(); //remove the line from raphael
  }
  task.raphael.parent.lines.splice(0,cntr);
  task.raphael.parent.to.splice(0,cntr);
  task.raphael.parent.lines = [];
  task.raphael.parent.to = [];
  //Now, for each task that points to us
  for(cntr=0; cntr < task.raphael.parent.pointedfrom.length; cntr++) { //task.raphael.parent.pointedfrom WHO points to us
    fromtask = maestro_get_task_reference(task.raphael.parent.pointedfrom[cntr]);

    //this for loop removes the fromtask line from raphael and removes the line from the fromtask
    for(cntr2=0; cntr2 < fromtask.raphael.parent.lines.length; cntr2++) {  //for each of the lines the fromtask has
      if(fromtask.raphael.parent.lines[cntr2].to == task.raphael.parent.id) { //if the current line in the fromtask points to us
        fromtask.raphael.parent.lines[cntr2].remove();  //remove the line via raphael.
        fromtask.raphael.parent.lines[cntr2] = '';  //remove the line in the list by just blanking it out
        maestro_update_task_information(fromtask);  //update the task
      }
    }

    //this loop removes the line from the fromtask's to pointers.
    for(cntr2=0; cntr2 < fromtask.raphael.parent.to.length; cntr2++) {
      if(fromtask.raphael.parent.to[cntr2] == task.raphael.parent.id) {
        fromtask.raphael.parent.to[cntr2] = [];
        maestro_update_task_information(fromtask);
      }
    }

    //and now to deal with lines that are set in the falsebranch.
    for(cntr2=0; cntr2 < fromtask.raphael.parent.falsebranch.length; cntr2++) {
      if(fromtask.raphael.parent.falsebranch[cntr2] == task.raphael.parent.id) {
        fromtask.raphael.parent.falsebranch[cntr2] = [];
        maestro_update_task_information(fromtask);
      }
    }

  }
  task.raphael.parent.pointedfrom.splice(0);
  maestro_update_task_information(task);

}


/**
 *
 * Our Drupal Ajax callbacks
 */
(function($, Drupal) {
  Drupal.AjaxCommands.prototype.addNewTask = function(ajax, response, status) {
    //response contains five properties
    //id, label, ui label, capabilities, and type.  We use these values to create the new task.
    var taskInformation = {};
    var task = {};
    taskInformation.id = response.id;
    taskInformation.type = response.type;
    taskInformation.taskname = response.label;
    taskInformation.uilabel = response.uilabel;
    taskInformation.top = 15;
    taskInformation.left = 15;
    taskInformation.lines = [];
    taskInformation.to = [];
    taskInformation.nextfalsestep = [];
    taskInformation.falsebranch = [];
    taskInformation.pointedfrom = [];
    taskInformation.capabilities = response.capabilities;
    taskInformation.participate_in_workflow_status_stage = '';
    taskInformation.workflow_status_stage_number = 0;
    taskInformation. workflow_status_stage_message = ''
    task = maestro_draw_task(taskInformation);
    maestroTasks.push(task);
  }

  Drupal.AjaxCommands.prototype.maestroUpdateMetaData = function(ajax, response, status) {
    var newLabel = response.label;
    var statustext, sampleText;
    sampleText = newLabel.substring(0,14);
    if(sampleText != newLabel) {
      sampleText += '...';
    }
    var task = maestro_get_task_reference(response.taskid);
    task.raphael.parent.raphaelTextName.attr({text: sampleText });
    //Now to draw/undraw the task's status bubble if the settings exist.


    if(response.participate_in_workflow_status_stage) {  //not right here with taskInformation.  Need to get task x,y
      if(task.raphael.parent.statusbubble) {
        task.raphael.parent.raphaelStatusTaskText.attr({'text': ''});
        task.raphael.parent.statusbubble.remove();
        task.raphael.parent.statusbubble = null;
      }
      task.raphael.parent.statusbubble = r.circle(Number(task.raphael.parent.left)+88,Number(task.raphael.parent.top)+35,9).attr({"cursor": "default", "fill" : "#a0a0a0", "title": response.workflow_status_stage_message});
      //statustext = r.text(Number(task.raphael.parent.left)+85, Number(task.raphael.parent.top)+35, response.workflow_status_stage_number).attr({"cursor": "default", "title": response.workflow_status_stage_message, 'font-weight': 'bold', fill: '#000000', 'text-anchor': 'start'});
      task.raphael.parent.raphaelStatusTaskText.attr({'text': response.workflow_status_stage_number});
      task.raphael.parent.statusbubble.toFront();
      task.raphael.parent.raphaelStatusTaskText.toFront();
    }
    else {
      //we should remove any instance of the status information and bubbles here
      try{
        task.raphael.parent.raphaelStatusTaskText.attr({'text': ''});
        task.raphael.parent.statusbubble.remove();
        task.raphael.parent.statusbubble = null;
      }
      catch(e) {}
    }



  }

  Drupal.AjaxCommands.prototype.maestroShowSavedMessage = function(ajax, response, status) {
    //turn on the task's saved notification in ID save-task-notificaiton

    jQuery('#drupal-modal').animate({ scrollTop: 0 }, "fast");
    jQuery('#save-task-notificaiton').css('display', 'block');
    jQuery('#save-task-notificaiton').fadeOut(3000);
  }

  Drupal.AjaxCommands.prototype.maestroEditTask = function(ajax, response, status) {
    jQuery('#edit-edit-task-complete').trigger('click');
  }

  Drupal.AjaxCommands.prototype.maestroNoOp = function(ajax, response, status) {
  }

  Drupal.AjaxCommands.prototype.maestroSignalError = function(ajax, response, status) {
    alert(response.message);
  }

  Drupal.AjaxCommands.prototype.maestroDrawLineTo = function(ajax, response, status) {
    lineFrom = response.taskid;
    var message = Drupal.t('Please choose the task to draw the line to.');
    jQuery('.maestro-template-message-area').html(message);
    jQuery('.maestro-template-message-area').css('display', 'block');
  }

  Drupal.AjaxCommands.prototype.maestroDrawFalseLineTo = function(ajax, response, status) {
    falseLineFrom = response.taskid;
    var message = Drupal.t('Please choose the task to draw the FALSE line to.');
    jQuery('.maestro-template-message-area').html(message);
    jQuery('.maestro-template-message-area').css('display', 'block');
  }

  Drupal.AjaxCommands.prototype.maestroCloseTaskMenu = function(ajax, response, status) {
    jQuery('#maestro-task-menu').css('display', 'none');
  }

  Drupal.AjaxCommands.prototype.maestroRemoveTaskLines = function(ajax, response, status) {
    maestro_remove_task_lines(response.task);
  }

  Drupal.AjaxCommands.prototype.maestroRemoveTask = function(ajax, response, status) {
    var taskToRemove = response.task;
    var taskIndex = -1;
    maestro_remove_task_lines(taskToRemove);
    for(var cntr=0; cntr < maestroTasks.length; cntr++) {
      if(maestroTasks[cntr].raphael.parent.id == taskToRemove) {
        taskIndex = cntr;
        //remove it from display
        maestroTasks[cntr].raphael.parent.raphaelTextName.remove();
        maestroTasks[cntr].raphael.parent.raphaelTaskText.remove();
        maestroTasks[cntr].raphael.parent.editimageraphael.remove();
        maestroTasks[cntr].raphael.parent.editrectraphael.remove();
        maestroTasks[cntr].raphael.parent.toprectraphael.remove();
        if(maestroTasks[cntr].raphael.parent.statusbubble != null) maestroTasks[cntr].raphael.parent.statusbubble.remove();
        if(maestroTasks[cntr].raphael.parent.raphaelStatusTaskText != null) maestroTasks[cntr].raphael.parent.raphaelStatusTaskText.remove();
        maestroTasks[cntr].raphael.remove();
      }
    }
    //we're removing this task from the list only if we've had a hit in the loop above
    if(taskIndex >= 0) maestroTasks.splice(taskIndex, 1);
    jQuery('#maestro-task-menu').css('display', 'none');
    jQuery('[name="task_clicked"]').val('');
  }

  Drupal.AjaxCommands.prototype.alterCanvas = function(ajax, response, status) {
    r.setSize(response.width, response.height);
    $('#maestro_div_template').width(response.width);
    $('#maestro_div_template').height(response.height);
  }

  Drupal.AjaxCommands.prototype.signalValidationRequired = function(ajax, response, status) {
    //we just turn on the needs validation flag here.
    $('#maestro-template-validation').css('display', 'block');
  }

  Drupal.AjaxCommands.prototype.turnOffValidationRequired = function(ajax, response, status) {
    //we just turn off the needs validation flag here.
    $('#maestro-template-validation').css('display', 'none');
  }
})(jQuery, Drupal);





//main functionality
var canvasHeight = drupalSettings.canvasHeight;
var canvasWidth = drupalSettings.canvasWidth;
jQuery('#maestro_div_template').width(canvasWidth);
jQuery('#maestro_div_template').height(canvasHeight);
var r = Raphael('maestro_div_template', canvasWidth, canvasHeight);
var maestroTasks = drupalSettings.maestro;
//the Raphael attachable functions
var lineFrom = false;
var falseLineFrom = false;
//attach the close option to our dialog
jQuery('#close-task-menu').click(function() {
  jQuery('#maestro-task-menu').css('display', 'none');
  jQuery('[name="task_clicked"]').val('');
});
//first, we draw the tasks
for(i=0; i<maestroTasks.length; i++) {
  maestroTasks[i] = maestro_draw_task(maestroTasks[i]);
}
maestroTasks = maestro_draw_all_lines(maestroTasks, r);

