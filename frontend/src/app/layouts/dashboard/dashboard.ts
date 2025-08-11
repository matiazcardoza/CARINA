import { Component } from '@angular/core';
import { Footer } from './components/footer/footer';
import { Header } from './components/header/header';
import { RouterOutlet } from '@angular/router';
@Component({
  selector: 'app-dashboard',
  imports: [RouterOutlet,Header,Footer],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css'
})
export class Dashboard {

}
