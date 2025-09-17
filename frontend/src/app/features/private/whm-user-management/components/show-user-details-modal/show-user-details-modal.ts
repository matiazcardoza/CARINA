import { Component, input, output, signal } from '@angular/core';
import { Dialog } from "primeng/dialog";

@Component({
  selector: 'app-show-user-details-modal',
  imports: [Dialog],
  templateUrl: './show-user-details-modal.html',
  styleUrl: './show-user-details-modal.css'
})
export class ShowUserDetailsModal {
  isOpen = input<boolean>(true);
  onCloseModal = output<boolean>();

  onClose(value:boolean){
    this.onCloseModal.emit(value)
  }
}
