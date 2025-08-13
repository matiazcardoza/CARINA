import { Component, signal } from '@angular/core';
import { Footer } from './components/footer/footer';
import { Header } from './components/header/header';
import { RouterOutlet } from '@angular/router';
import { Sidebar } from './components/sidebar/sidebar';
@Component({
  selector: 'app-dashboard',
  imports: [RouterOutlet,Header,Footer, Sidebar],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard {
  isOpen = signal<boolean>(false)
  handleOpenSidebar(value: boolean){
    console.log("valor ejecutado en el dashbloard")
    this.isOpen.set(value) 
  }
}
