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
  type userStep = {
    id: number;
    report_id: number;
    order: number;
    user_id: number | null;
    role: string;
    page: number;
    pos_x: number;
    pos_y: number;
    width: number;
    height: number;
    status: string;
    comment: string | null;
    signed_at: string | null; // ISO date string
    signed_by: string | null;
    provider: string | null;
    callback_token: string;
    sha256: string | null;
    created_at: string; // ISO date string
    updated_at: string; // ISO date string
  }

export interface Report {
  id: number;
  reportable_type: string;
  reportable_id: number;
  pdf_path: string;
  pdf_page_number: number;
  status: 'in_progress' | 'signed' | 'rejected' | string;
  current_step: userStep;
  generation_params: any | null; // puede tiparse mejor si conoces la estructura
  signing_starts_at: string | null; // ISO date string
  signing_ends_at: string | null;   // ISO date string
  created_by: number;
  sign_callback_url: string;
  created_at: string; // ISO date string
  updated_at: string; // ISO date string
}

export interface SignatureParams {
  location_url_pdf: string;
  post_location_upload: string;
  asunto?: string;
  rol?: string;
  tipo?: string;
  status_position?: string;
  visible_position?: boolean | string;
  bacht_operation?: boolean | string; // (typo replicado si tu proveedor lo exige)
  npaginas?: number | string;
  posx?: number | string;
  posy?: number | string;
  dni?: string;
  tipoSalida?: string;
  siguienteEnFirmar?: string;
  token?: string; // ideal: JWT corto emitido por tu backend
}