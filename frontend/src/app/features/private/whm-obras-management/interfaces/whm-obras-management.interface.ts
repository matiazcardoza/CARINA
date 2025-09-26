export interface WhmObrasManagementInterface {
    
}

    export type Obra = {
    id: number;
    idmeta_silucia: string;
    anio: string;
    codmeta: string;
    nombre: string | null;
    desmeta: string | null;
    nombre_corto: string | null;
    };

    export interface Link {
    url: string | null;
    label: string;    // puede venir con entidades HTML (&laquo; ...)
    active: boolean;
    }

export interface MetasDetalladoResponse {
  current_page: number;
  data: Obra[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: Link[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
  enums: unknown[]; // viene como [] en el ejemplo
}

export interface params{
    page: number;
    per_page: number;
    sortField?: string;
    sortOrder?: 1 | -1;
    filters?: Record<string, string |number |null>
} 

export type Filters = { anio: string | null; codmeta: string | null };