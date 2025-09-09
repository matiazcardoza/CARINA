// src/app/core/models/fuel-order.model.ts

export type FuelType = 'gasolina' | 'diesel' | 'glp';
export type ApprovalStatus = 'approved' | 'rejected' | null;

export interface FuelOrderListItem {
  id: number;

  // Campos que muestras en la tabla
  numero: string | null;
  fecha: string; // ISO (p. ej. "2025-09-05")
  vehiculo_placa: string | null; // snapshot guardado en la orden
  fuel_type: FuelType;
  quantity_gal: string;   // llega como string por cast decimal en Laravel
  amount_soles: string;   // idem
  supervisor_status: ApprovalStatus;
  manager_status: ApprovalStatus;

  // Relación opcional usada en la tabla (si no hay snapshot)
  vehicle?: { id: number; plate: string; brand: string } | null;

  // Opcionales útiles si el backend los expone
  driver?: { id: number; name: string } | null;
  status_global?: 'pending' | 'approved' | 'rejected';
}

// Si consumes paginado de Laravel
export interface Page<T> {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
}
