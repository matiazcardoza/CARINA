import { Component, signal } from '@angular/core';
import { MyChildComponent } from './commponents/my-child-component/my-child-component';
@Component({
  selector: 'app-my-second-feature',
  imports: [MyChildComponent],
  templateUrl: './my-second-feature.html',
  styleUrl: './my-second-feature.css'
})
export class MySecondFeature {
  valueForTheFather = signal('initial Value');
  // this.secondCount.update(c => c + 1 );
  receivedValue(value: any){
    console.log("value received of the children:", value)
    this.valueForTheFather.update(()=>value);
  }
}
