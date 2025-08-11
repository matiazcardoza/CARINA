import { Component, input, output } from '@angular/core';

@Component({
  selector: 'app-child-component',
  imports: [],
  templateUrl: './child-component.html',
  styleUrl: './child-component.css'
})
export class ChildComponent {
  
  valueTosendToParent = output<number>()
  valueOfFatherx = input<number>()

  handleSendParent(){
    console.log("value send to parent")
    this.valueTosendToParent.emit(5)
  }
}
