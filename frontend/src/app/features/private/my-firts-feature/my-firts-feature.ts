import { Component, Input } from '@angular/core';
import { MyChildComponent } from './components/my-child-component/my-child-component';
@Component({
  selector: 'app-my-firts-feature',
  imports: [MyChildComponent],
  templateUrl: './my-firts-feature.html',
  styleUrl: './my-firts-feature.css'
})

export class MyFirtsFeature {
  dato1 = 'Hola hijo 1';
  dato2 = 'Hola hijo 2';
  dato3 = 'Hola hijo 3';
  mensajeRecibido = '';

  childTitle = 'Soy el título que viene del padre';
  recibirMensaje(valor: string) {
    this.mensajeRecibido = valor;
  }
  receivedValueOfChild(value: string){
    console.log("value entry of children: ", value)
  }

  // value send to children
  tituloDelPadre= "this value is send of the father"
}
