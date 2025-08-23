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