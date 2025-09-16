import { Component, signal } from '@angular/core';
import { TableModule } from "primeng/table";
import { IconField } from "primeng/iconfield";
import { InputIcon } from "primeng/inputicon";

@Component({
  selector: 'app-whm-user-management',
  imports: [TableModule, IconField, InputIcon],
  templateUrl: './whm-user-management.html',
  styleUrl: './whm-user-management.css'
})
export class WhmUserManagement {
  users = signal([]);
}
