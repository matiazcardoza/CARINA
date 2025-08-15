export interface Order {
  id: number;
  silucia_id: number;
  order_type: string;
  issue_date: string;
  goal_project: string;
  api_date: string | null;
  state: number; // El estado viene como número
  created_at: string | null;
  updated_at: string | null;
}