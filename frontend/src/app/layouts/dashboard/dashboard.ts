import { Component, signal } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { Sidebar } from '../sidebar/sidebar';
import { Navbar } from '../navbar/navbar';
@Component({
  selector: 'app-dashboard',
  imports: [
    RouterOutlet,
    Sidebar, 
    Navbar],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard {
  isOpen = signal<boolean>(false)
  handleOpenSidebar(value: boolean){
    this.isOpen.set(value) 
  }
}
