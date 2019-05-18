import { Component, OnInit, Input } from '@angular/core';
import { Message } from '@app/models';
import { DialogflowService } from '@app/services';

declare var responsiveVoice: any;
declare var webkitSpeechRecognition: any;
declare var Drupal: any;

@Component({
  selector: 'message-form',
  templateUrl: './message-form.component.html',
  styleUrls: ['./message-form.component.scss']
})
export class MessageFormComponent implements OnInit {

  @Input('message')
  public message : Message;

  @Input('messages')
  public messages : Message[];

  public recognition : any;
  public debug : string;
  public isDebug : boolean;
  public action : string;
  public action_label : string;

  constructor(private dialogFlowService: DialogflowService) {
    this.recognition = false;
    this.isDebug = this.dialogFlowService.isDebug;
    this.debug = JSON.stringify(Drupal.Redux.store.getState());
    this.action = '';
    this.action_label = '';
  }

  ngOnInit() {
  }

  public doAction(): void {
    let menu_items = Drupal.behaviors.sayHelloDialogflow.getMenu()['menu_items'];

    for (var i = 0; i < menu_items.length; ++i) {
      if(menu_items[i]['callback'] == this.action) {
        var object = {last_action:this.action};
        Drupal.Redux.store.dispatch(
            Drupal.Redux.actions.add('Opened ' + menu_items[i]['callback'])
        );

        window.location.href = '/' + menu_items[i]['url'];
        return;
      }
    }
  }

  public getActionLabel(action: string): string {
    let menu_items = Drupal.behaviors.sayHelloDialogflow.getMenu()['menu_items'];

    for (var i = 0; i < menu_items.length; ++i) {
      if(menu_items[i]['callback'] == action) {
        return 'Open ' + menu_items[i]['title'];
      }
    }

    return "No action available";
  }

  public sendTextMessage(): void {
    this.message.timestamp = new Date();
    this.messages.push(this.message);

    this.dialogFlowService.getResponse(this.message.content).subscribe(res => {

      this.debug = JSON.stringify(res);
      this.action = res.result.action;
      this.action_label = this.getActionLabel(this.action);
      responsiveVoice.speak(res.result.fulfillment.speech);

      this.messages.push(
        new Message(res.result.fulfillment.speech, 'assets/images/bot.svg', res.timestamp)
      );

    });

    this.message = new Message('', 'assets/images/user.svg');
  }

  public switchRecognition(): void {
    if (this.recognition) {
      this.stopRecognition();
    } else {
      this.startRecognition();
    }
  }

  public stopRecognition(): void {
    if (this.recognition) {
      this.recognition.stop();
      this.recognition = null;
    }
  }

  public startRecognition(): void {

    let messageCouldntHear = "I couldn't hear you, could you say that again?";
    this.recognition = new webkitSpeechRecognition();
    this.debug = '';

    this.recognition.continuous = false;
    this.recognition.interimResults = false;
    this.recognition.onstart = (event) => {
      responsiveVoice.speak("recording ...");
    };

    this.recognition.onresult = (event) => {
      this.recognition.onend = null;

      var text = "";
      for (var i = event.resultIndex; i < event.results.length; ++i) {
        text += event.results[i][0].transcript;
      }

      this.message = new Message(text, 'assets/images/user.svg', new Date());
      this.sendTextMessage();
      this.stopRecognition();
    };

    if(this.recognition) {
      this.recognition.onend = (event) => {
        this.stopRecognition();
        responsiveVoice.speak(messageCouldntHear);
      };
      this.recognition.lang = "en-US";
    }

    this.recognition.start();
  }
  
}