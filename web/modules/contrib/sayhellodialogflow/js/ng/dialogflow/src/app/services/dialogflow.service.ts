import { Injectable } from '@angular/core';
import { Http, Headers } from '@angular/http';
import { Observable } from 'rxjs/Rx';
import 'rxjs/add/operator/map';
import { environment } from '@env/environment';

declare var Drupal: any;

@Injectable()
export class DialogflowService {

  public domain: string;
  public baseURL: string;
  public token: string;
  public isDebug: boolean;

  constructor(private http: Http){}

  public getResponse(query: string){
    let data = {
      query : query,
      lang: 'en',
      sessionId: '12345'
    }


    if(Drupal.behaviors.sayHelloDialogflow.getDomain() !== this.domain) {
      this.domain  = Drupal.behaviors.sayHelloDialogflow.getDomain();
    }
    if(Drupal.behaviors.sayHelloDialogflow.getDomain() !== this.baseURL) {
      this.baseURL = Drupal.behaviors.sayHelloDialogflow.getBaseurl();
    }
    if(Drupal.behaviors.sayHelloDialogflow.getDomain() !== this.token) {
      this.token   = Drupal.behaviors.sayHelloDialogflow.getToken();
    }
    if(Drupal.behaviors.sayHelloDialogflow.getDebug() !== this.isDebug) {
      this.isDebug  = false;
      if(Drupal.behaviors.sayHelloDialogflow.getDebug() == 1 ) {
        this.isDebug  = true;
      }
    }

    return this.http
      .post(`${this.baseURL}`, data, {headers: this.getHeaders()})
      .map(res => {
        console.log("res");
        console.log(res);
        return res.json()
      })
  }

  public getHeaders(){
    let headers = new Headers();
    headers.append('Authorization', `Bearer ${this.token}`);
    return headers;
  }
}
