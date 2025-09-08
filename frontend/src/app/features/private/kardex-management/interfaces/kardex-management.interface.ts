export interface filter { 
    // numero?: string; 
    // anio?: number; 
    // estado?: string
    numero?: string; 
    anio?: number; 
    item?: string; 
    desmeta?: string; 
    siaf?: string;
    ruc?: string; 
    rsocial?: string; 
    email?: string; 
    page?: number; 
    per_page?: number 
  }

    export interface Pecosa{
      anio: string,
      numero: string,
      fecha: string,
      prod_proy: string,
      cod_meta: string,
      desmeta: string,
      desuoper: string,
      destipodestino: string,
      item: string,
      desmedida: string,
      idsalidadet:  number,
      cantidad: number, 
      precio: number,
    }

    export interface PaginationLink {
      url: string | null;
      label: string;         // puede ser "1", "..." o "Next »"
      active: boolean;
    }

    export interface LaravelPagination<T> {
      current_page: number;
      data: T[];
      first_page_url: string;
      from: number | null;
      last_page: number;
      last_page_url: string;
      links: PaginationLink[];
      next_page_url: string | null;
      path: string;
      per_page: number;
      prev_page_url: string | null;
      to: number | null;
      total: number;
      enums: unknown[];      // viene como [] en tu ejemplo
    }

