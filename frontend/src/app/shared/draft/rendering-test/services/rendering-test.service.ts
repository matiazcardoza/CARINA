// demo.service.ts
import { Injectable } from '@angular/core';
import { of } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class RenderingTestService {
    getOrdenesCompra(_obraId: number, _q: string) {
    // ❗ Emite y COMPLETA inmediatamente (sincrónico)
    return of([{ id: 2, obra_id: 2 }]);
  }
}
