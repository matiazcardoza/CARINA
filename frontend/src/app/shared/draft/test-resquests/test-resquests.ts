import { Component, inject, signal } from '@angular/core';
import { TestResquestsService } from './services/test-resquests.service';
import { finalize } from 'rxjs';

import { JsonPipe } from '@angular/common';

@Component({
  selector: 'app-test-resquests',
  imports: [JsonPipe],
  templateUrl: './test-resquests.html',
  styleUrl: './test-resquests.css'
})
export class TestResquests {
  private http = inject(TestResquestsService);

  datos = signal<unknown | null>(null);
  isLoading = signal(false);
  error = signal<unknown | null>(null);


  realizarPeticion(): void {
    // 3. Preparamos la UI para la petición
    this.isLoading.set(true);
    this.datos.set(null);
    this.error.set(null);

    // Usaremos una API pública de prueba para el ejemplo
    const apiUrl = 'https://jsonplaceholder.typicode.com/posts/1';

    // 4. Realizamos la petición HTTP GET
    this.http.getData(apiUrl).pipe(
      // finalize() se ejecuta siempre, ya sea éxito o error
      finalize(() => this.isLoading.set(false))
    ).subscribe({
      next: (respuesta) => {
        // 5. Manejamos la respuesta exitosa
        console.log('Respuesta de la API:', respuesta);
        this.datos.set(respuesta);
      },
      error: (err) => {
        // 6. Manejamos el error
        console.error('Error en la petición:', err);
        // Guardamos el objeto de error completo para mostrarlo
        this.error.set({
          status: err.status,
          statusText: err.statusText,
          message: err.message
        });
      }
    });
  }
}
