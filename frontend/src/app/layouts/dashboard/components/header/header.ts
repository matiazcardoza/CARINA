import { Component, input, output, signal } from '@angular/core';
import { RouterModule } from '@angular/router';
import { Sidebar } from '../sidebar/sidebar';
import { Profile } from '../profile/profile';
import {MatIconModule} from '@angular/material/icon'
@Component({
  selector: 'app-header',
  imports: [RouterModule, Sidebar, Profile, MatIconModule],
  templateUrl: './header.html',
  styleUrl: './header.css'
})
export class Header {
  isOpen = input<boolean>(false)
  sentOpenValue = output<boolean>()

  handleOpenSidebar(value: boolean){
    // console.log(value)
    this.sentOpenValue.emit(value); 
  }
}
