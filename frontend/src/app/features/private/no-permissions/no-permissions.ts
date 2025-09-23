import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { ButtonModule } from 'primeng/button';
import { CardModule } from 'primeng/card';

@Component({
  selector: 'app-no-permissions',
  standalone: true,
  imports: [ButtonModule, CardModule],
  templateUrl: './no-permissions.html',
  styleUrls: ['./no-permissions.css']
})
export class NoPermissions {
  constructor(private router: Router) {}

  goHome() {
    this.router.navigate(['/private/home']);
  }

  goBack() {
    window.history.back();
  }
}
