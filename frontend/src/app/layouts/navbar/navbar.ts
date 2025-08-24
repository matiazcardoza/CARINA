import { Component, output } from '@angular/core';
import { Toolbar } from 'primeng/toolbar';
import { AvatarModule } from 'primeng/avatar';
import { SharedModule } from 'primeng/api';
import { ButtonModule } from 'primeng/button';

@Component({
  selector: 'app-navbar',
  imports: [Toolbar, AvatarModule, ButtonModule],
  templateUrl: './navbar.html',
  styleUrl: './navbar.css'
})
export class Navbar {
  sentOpenValue = output<boolean>();
  handleOpenSidebar(value: boolean){
    this.sentOpenValue.emit(true)
  }
}
