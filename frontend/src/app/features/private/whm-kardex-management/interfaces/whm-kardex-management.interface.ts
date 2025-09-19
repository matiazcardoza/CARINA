export type OC   = 
{ id: number; ext_order_id: string; fecha: string; proveedor: string; monto_total: number };

// ---------------

export interface PecosaLite {
  id: number;
  obra_id: number;
  anio: string;
  numero: string;
  fecha: string;
  prod_proy?: string;
  cod_meta?: string;
  desmeta?: string;
  desuoper?: string;
  destipodestino?: string;
  item?: string;
  desmedida?: string;
  cantidad?: number;
  precio?: number;
  total?: number;
  saldo?: number;
  numero_origen?: string;
  idsalidadet_silucia: number | string;
  idcompradet_silucia?: number | string;

  /** nuevo: lista de reportes ya calculada por el backend */
  reports?: Array<{
    report_id: number;
    type: string|null;
    status: string;
    flow_id: number|null;
    current_step: number|null;
    current_role: string|null;
    user_step: {
      id: number;
      role: string;
      status: string;
      order: number;
      page?: number|null;
      pos_x?: number|null;
      pos_y?: number|null;
      width?: number|null;
      height?: number|null;
    } | null;
    can_sign: boolean;
    download_url: string|null;
    sign_callback_url: string|null;
  }>;
}
