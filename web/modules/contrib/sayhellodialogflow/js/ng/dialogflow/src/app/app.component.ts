import { Component } from '@angular/core';
import { Message } from '@app/models';

declare var Drupal: any;

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  public message : Message;
  public messages : Message[];


  constructor(){
    this.message = new Message('', 'assets/images/user.svg');
    this.messages = [new Message(Drupal.behaviors.sayHelloDialogflow.getDefaultIntentText(), 'assets/images/bot.png', new Date())];
  }
}
