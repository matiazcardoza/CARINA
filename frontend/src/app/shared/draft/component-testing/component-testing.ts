import { Component } from '@angular/core';
import { UiButton } from './components/ui-button/ui-button';
@Component({
  selector: 'app-component-testing',
  imports: [UiButton],
  templateUrl: './component-testing.html',
  styleUrl: './component-testing.css'
})
export class ComponentTesting {

  myfuncion(param: any){
    console.log("this is the param",param);
  }
}
