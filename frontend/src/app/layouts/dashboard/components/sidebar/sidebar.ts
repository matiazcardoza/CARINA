import { Component, input, output, signal } from '@angular/core';
import {MatIconModule} from '@angular/material/icon'
// import { NgClass } from '@angular/common';
import { RouterModule } from '@angular/router';
@Component({
  selector: 'app-sidebar',
  imports: [MatIconModule, RouterModule],
  templateUrl: './sidebar.html',
  styleUrl: './sidebar.css'
})
export class Sidebar {

  /**
   * emitimos una seña inversa al valor actual, que abre el menu
   */
  isOpen = input<boolean>(false)
  sentOpenValue = output<boolean>()
  handleOpenSidebar(){
    this.sentOpenValue.emit(!this.isOpen());
  }

  
  value = signal(0)
  incomingValue = input<boolean>(false)

  closeSidebar(){
    // console.log("cerrar el sidebar")
    this.sentOpenValue.emit(true)
  }
  isActive = true;
}
