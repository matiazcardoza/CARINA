import { Component, signal } from '@angular/core';
import { ChildComponent } from './components/child-component/child-component';
@Component({
  selector: 'app-my-first-component',
  imports: [ChildComponent],
  templateUrl: './my-first-component.html',
  styleUrl: './my-first-component.css'
})
export class MyFirstComponent {
  valueOfFather = signal<number>(0);
  addValue(){
    this.valueOfFather.update( value => value + 1 ); 
  }

  addSpecificValue = (x: any) => {
    console.log(x)
    this.valueOfFather.update(value => x);
  }
}
