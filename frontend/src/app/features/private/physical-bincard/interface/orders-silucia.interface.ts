// export interface Order {
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

import { Order } from "./order.interface";

// export interface Orders {
//   id: number;
//   silucia_id: number;
//   order_type: 'compra' | 'venta'; // ajusta según los posibles valores
//   issue_date: string; // formato ISO
//   goal_project: string;
//   api_date: string | null;
//   state: string | null;
//   created_at: string | null;
//   updated_at: string | null;
// }

export interface OrdersSilucia {
  message: string;
  data: Order[];
}

// {
//     "message": "Daily work log retrieved successfully",
//     "data": [
//         {
//             "id": 1,
//             "silucia_id": 123,
//             "order_type": "compra",
//             "issue_date": "2025-08-14T00:00:00.000000Z",
//             "goal_project": "Mejorar escuela",
//             "api_date": null,
//             "state": null,
//             "created_at": null,
//             "updated_at": null
//         },
//         {
//             "id": 2,
//             "silucia_id": 432,
//             "order_type": "compra",
//             "issue_date": "2025-08-14T00:00:00.000000Z",
//             "goal_project": "mejorar hospital region puno",
//             "api_date": null,
//             "state": null,
//             "created_at": null,
//             "updated_at": null
//         }
//     ]
// }