import { Component, input, output, signal } from '@angular/core';
import { Button } from 'primeng/button';
import { DialogModule } from 'primeng/dialog';
import { InputNumberModule } from 'primeng/inputnumber';
import { TableModule } from 'primeng/table';
import { InputText } from "primeng/inputtext";
import { FormsModule } from '@angular/forms';
// import { KardexManagementService } from '../../services/kardex-management.service';
import { KardexManagementService } from '../../../kardex-management/services/kardex-management.service';

import { WhmKardexManagementService } from '../../services/whm-kardex-management.service';
import { Toast } from 'primeng/toast';
import { MessageService } from 'primeng/api';
@Component({
  selector: 'app-add-new-user-modal',
  imports: [DialogModule, Button, TableModule, InputNumberModule, InputText, FormsModule, Toast],
  templateUrl: './add-new-user-modal.html',
  styleUrl: './add-new-user-modal.css',
  providers: [MessageService]
})
export class AddNewUserModal {

    constructor(private service:WhmKardexManagementService, private messageService: MessageService){

    }

    isOpen = input<boolean>(false)
    obraId = input<number | null>(null)
    sentOpenValue = output<boolean>()
    isOpenChange = output<boolean>()
    onAddNewOperario = output()
    dniQuery: string = '';
    loading = signal<boolean>(false)
    onListPeopleByDni = output<boolean>();
    initialValue ={
            "dni": "",
            "first_lastname": "",
            "second_lastname": "",
            "names": "",
            "full_name": "",
            "civil_status": null,
            "address": null,
            "ubigeo": null,
            "ubg_department": null,
            "ubg_province": null,
            "ubg_district": null,
            "photo_base64": null,
            "reniec_consulted_at": null,
            "created_at": null,
            "updated_at": null
    }
    personObtainedByDni = signal<any>(this.initialValue)
    onBuscarDni() {
      const dni = (this.dniQuery || '').trim();
      if (!dni) return;
      this.loading.set(true);
      this.service.getPersonByDni(this.obraId(), dni).subscribe({
        next: person => {
          this.personObtainedByDni.set(person.data);
          this.showToastMessage({detail: "DNI obtenido correctamente", severity: 'success', summary: "Success"});

        },
        error: err => {
          console.error('Error al consultar DNI:', err)
          this.loading.set(false); 
          this.showToastMessage({detail: "El DNI solicitado no existe", severity: 'error', summary: 'Error'});
        },

        complete: () => {
          this.loading.set(false); 
        },
      });
    }

    closeMovementDetailsModal() {
      // this.sentOpenValue.emit(false);
      this.isOpenChange.emit(false);
      // this.isOpen(false)
      // cierra el diálogo como lo manejes hoy
      // this.showMovementDetailsModal = false; // o tu signal/acción equivalente
    }

    sendPesonSelected(){
      console.log("acepta el nombre")
      const dni = (this.dniQuery || '').trim();
        if (!dni) return;
        // this.loading.set(true);
        this.service.postSavePersonByDni(this.obraId(), dni).subscribe({
          next: person => {
            this.personObtainedByDni.set(this.initialValue);
            this.showToastMessage({detail: "DNI obtenido correctamente", severity: 'success', summary: "Success"});
            this.isOpenChange.emit(false);
            this.onAddNewOperario.emit();
            // hacemos una peticion en el padre para obtener la lista seleccionable de usuarios


          },
          error: err => {
            console.error('Error al consultar DNI:', err)
            // this.loading.set(false); 
            this.showToastMessage({detail: "El DNI solicitado no existe", severity: 'error', summary: 'Error'});
            this.isOpenChange.emit(false);
            this.personObtainedByDni.set(this.initialValue);
          },

          complete: () => {
            // this.loading.set(false); 
          },
      });



      // this.onListPeopleByDni.emit(this.personObtainedByDni());
      // this.personObtainedByDni.set({
      //       "dni": "",
      //       "first_lastname": "",
      //       "second_lastname": "",
      //       "names": "",
      //       "full_name": "",
      //       "civil_status": null,
      //       "address": null,
      //       "ubigeo": null,
      //       "ubg_department": null,
      //       "ubg_province": null,
      //       "ubg_district": null,
      //       "photo_base64": null,
      //       "reniec_consulted_at": null,
      //       "created_at": null,
      //       "updated_at": null
      // });

      this.dniQuery = '';
      this.isOpenChange.emit(false);
      // this.sentOpenValue.emit(false)

      
    }


    showToastMessage({
      severity, 
      summary, 
      detail
    }:{
      severity: 'success' | 'info' | 'warn' | 'error';
      summary: string;
      detail: string;
    }) {
        this.messageService.add({ severity: severity, summary: summary, detail: detail });
    }

}
