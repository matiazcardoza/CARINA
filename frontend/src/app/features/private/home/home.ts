import { Component, signal } from '@angular/core';

@Component({
  selector: 'app-home',
  imports: [],
  templateUrl: './home.html',
  styleUrl: './home.css'
})
export class Home {
  count = 0;
  secondCount = signal(0);

  increment(){
    this.count ++ ;
  }
  decrement(){
    this.count -- ;
  }
  reset() {
    this.count = 0; // vuelve a cero
  }



  ngOnInit() {
    console.log("mi valor cargado en el primer render")
    setTimeout(() => {
      this.count = 8 // Aquí Angular no se entera, no repinta en zoneless
      this.secondCount.update(c => c + 1 );
    }, 1000);
  }

  ngAfterViewInit(){ /* ya hay DOM/hijos */ }
  ngOnDestroy(){ /* limpiar timers/subscripciones */ }

}
