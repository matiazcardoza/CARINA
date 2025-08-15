export interface MovementKardex {
  id: number;
  product_id: number;
  movement_type?: string;         // Ej: 'entrada', 'salida', 'ajuste'
  movement_date?: string;         // Formato ISO: 'YYYY-MM-DD'
  amount?: number;                // Cantidad del movimiento
  final_balance?: number;         // Saldo final después del movimiento
  // created_at: string;             // Timestamp de creación
  // updated_at: string;             // Timestamp de última modificación
}
