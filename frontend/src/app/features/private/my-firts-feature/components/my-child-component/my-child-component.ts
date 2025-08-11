import { Component, EventEmitter, input, Input, output, Output } from '@angular/core';

@Component({
  selector: 'app-my-child-component',
  imports: [],
  templateUrl: './my-child-component.html',
  styleUrl: './my-child-component.css'
})
export class MyChildComponent {
    @Input() mensaje = 'message';
    @Input() mensaje1 = 'aaa';
    @Input() mensaje2 = 'aaa';
    @Input() mensaje3 = 'sssss';

    @Output() mensajeDelHijo  = new EventEmitter<string>();

    sendToFather = output<string>()


    avisarPadre() {
      this.mensajeDelHijo.emit('Hola padre, soy tu hijo'); 
    }
    sendToFatherFunction(){
      this.sendToFather.emit("this message was send of the child")
    }
    save() {
      console.log('Hijo: guardado!');
      this.mensajeDelHijo.emit(); // le mando la señal al padre
    }
    enviarAlPadre = output<string>();   // 👈 moderno
    enviar() {
      this.enviarAlPadre.emit('¡Hola desde el hijo!');
    }

    // received message of father
    myValueReceivedOfTheFather = input<string>('Sin título');           // valor por defecto
}
