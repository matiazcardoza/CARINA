import { Component } from '@angular/core';
// import { MovementRegisterService } from './services/register/movement-register.service';
// import { MovementRegisterService } from './services/register/movement-register.service';
import { MovementRegisterService } from './services/register/movement-register.service';
import { Observable } from 'rxjs';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ExmapleService } from './exmaple.service';
@Component({
  selector: 'app-valued-kardex',
  imports: [ReactiveFormsModule],
  templateUrl: './valued-kardex.html',
  styleUrl: './valued-kardex.css'
})
export class ValuedKardex {
    userForm!: FormGroup;
    roles: string[] = ['Usuario', 'Administrador', 'Invitado'];  // Opciones para el select
    submissionSuccess: boolean = false;
    serverMessage: string = '';
    
  constructor(private fb: FormBuilder, private exampleService: ExmapleService){

  }

  ngOnInit(): void {
    // Definir el formulario reactivo con sus controles y validaciones
    this.userForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      role: ['', Validators.required],
      subscribe: [false]
    });
  }

  onSubmit(): void {
    // console.log(this.exampleService.seeUrl())
    if (this.userForm.valid) {
      const formData = this.userForm.value;  // Obtener los valores del formulario
      // Llamar al servicio para enviar datos
      
      this.exampleService.sendData(formData).subscribe({
        next: (response:any) => {
          // Éxito: manejar respuesta del servidor
          this.submissionSuccess = true;
          this.serverMessage = response.message || 'Datos enviados correctamente.';
          // Limpiar formulario si se desea
          this.userForm.reset();
        },
        error: (err) => {
          console.error('Error en el envío', err);
          this.serverMessage = 'Hubo un error al enviar los datos.';
        }
      });
    }
  }

}
