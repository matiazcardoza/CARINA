import { Component, input, model, output, signal } from '@angular/core';
import { DrawerModule } from 'primeng/drawer';
import { ButtonModule } from 'primeng/button';
import { AvatarModule } from 'primeng/avatar';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-sidebar',
  imports: [DrawerModule, ButtonModule, AvatarModule, RouterModule],
  templateUrl: './sidebar.html',
  styleUrl: './sidebar.css'
})
export class Sidebar {
  isOpen = input<boolean>(false);
  visible = model(true);
  sentOpenValue = output<boolean>();
  handleOpenSidebar = () => {
    console.log("value send")
    this.sentOpenValue.emit(false)
  }
  // Position (4 cajones)


  // Headless - estados de secciones (sustituyen pStyleClass)
  favOpen     = signal(true);
  reportsOpen = signal(false);
  revenueOpen = signal(false);
  appOpen     = signal(true);
  transportVouchersOpen     = signal(true);

  toggle(s: 'fav'|'reports'|'revenue'|'app'|'transportVouchers') {
    if (s === 'fav') this.favOpen.update(v => !v);
    if (s === 'reports') this.reportsOpen.update(v => !v);
    if (s === 'revenue') this.revenueOpen.update(v => !v);
    if (s === 'app') this.appOpen.update(v => !v);
    if (s === 'transportVouchers') this.transportVouchersOpen.update(v => !v);
  }

  close(drawerRef: any, e: Event) { drawerRef.close(e); }
}
