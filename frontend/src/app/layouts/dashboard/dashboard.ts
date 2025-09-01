import { Component, signal } from '@angular/core';
// import { Footer } from './components/footer/footer';
// import { Header } from './components/header/header';
import { RouterOutlet } from '@angular/router';
// import { Sidebar } from './components/sidebar/sidebar';
import { Sidebar } from '../sidebar/sidebar';
import { Navbar } from '../navbar/navbar';
@Component({
  selector: 'app-dashboard',
  imports: [
    RouterOutlet,
    // Header, 
    Sidebar, 
    Navbar],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard {
  isOpen = signal<boolean>(false)
  handleOpenSidebar(value: boolean){
    console.log("datos llegado: ", value);
    this.isOpen.set(value) 
  }
}
