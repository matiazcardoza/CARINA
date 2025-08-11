import { Component, output } from '@angular/core';

@Component({
  selector: 'app-my-child-component',
  imports: [],
  templateUrl: './my-child-component.html',
  styleUrl: './my-child-component.css'
})
export class MyChildComponent {

  // send value to parent element
  sendFather = output<string>()

  send(){
    this.sendFather.emit("0001");
  }
}
