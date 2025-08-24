import { Component, input, output } from '@angular/core';
import { Button } from 'primeng/button';
import { DialogModule } from 'primeng/dialog';
import { InputNumberModule } from 'primeng/inputnumber';
import { TableModule } from 'primeng/table';
import { InputText } from "primeng/inputtext";
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-add-new-user-modal',
  imports: [DialogModule, Button, TableModule, InputNumberModule, InputText, FormsModule],
  templateUrl: './add-new-user-modal.html',
  styleUrl: './add-new-user-modal.css'
})
export class AddNewUserModal {

    isOpen = input<boolean>(false)
    sentOpenValue = output<boolean>()
    dniQuery: string = '';

    onBuscarDni() {
      const dni = (this.dniQuery || '').trim();
      if (!dni) return;
      
      // TODO: llama a tu servicio de búsqueda por DNI
      // this.myService.buscarPorDni(dni).subscribe(...)
    }

    closeMovementDetailsModal() {
      this.sentOpenValue.emit(false);
      // cierra el diálogo como lo manejes hoy
      // this.showMovementDetailsModal = false; // o tu signal/acción equivalente
    }

}
