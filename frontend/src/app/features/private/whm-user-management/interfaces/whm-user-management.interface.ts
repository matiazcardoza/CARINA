export interface RoleApi { id: number; name: string; }
export interface UserApi {
  id: number;
  num_doc: string | null;
  name: string;
  persona_name: string | null;
  last_name: string | null;
  email: string;
  roles: RoleApi[] | null;
  role_names: string;
  state: number;
  created_at: string | null;
  updated_at: string | null;
}
export interface ApiResponse<T> {
  message: string;
  data: T;
}

/* Modelo para la tabla (ya deduplicado y formateado) */
export interface UserRow {
  id: number;
  num_doc: string;
  usuario: string;          // users.name
  persona: string;          // persona_name + last_name
  email: string;
  roles: RoleApi[];         // deduplicado
  role_names: string;       // deduplicado y join(", ")
  state: number;
  created_at: string | null;
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