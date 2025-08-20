import { Component } from '@angular/core';
import { UiButton } from './components/ui-button/ui-button';
// import ButtonModule
import { TablePrimeng } from '../../components/table-primeng/table-primeng';
import { ButtonModule } from 'primeng/button';
@Component({
  selector: 'app-component-testing',
  imports: [UiButton, ButtonModule, TablePrimeng],
  templateUrl: './component-testing.html',
  styleUrl: './component-testing.css'
})
export class ComponentTesting {

  myfuncion(param: any){
    console.log("this is the param",param);
  }
}
