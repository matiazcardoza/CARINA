// export interface products {
//   id: number;
//   silucia_id: number;
//   order_type: string;
//   issue_date: string;
//   goal_project: string;
//   api_date: string | null;
//   state: number; // El estado viene como número
//   created_at: string | null;
//   updated_at: string | null;
// }
export interface products {
  id: number;
  order_id: number;
  name: string;
  heritage_code: string;
  unit_price: string; // ← viene como string, no como número
  state: number;
  // created_at: string | null;
  // updated_at: string | null;
}
