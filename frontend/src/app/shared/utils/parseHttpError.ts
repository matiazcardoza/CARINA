// src/app/shared/utils/http-error.util.ts

// SE NECESITA QUE SE ASINCRONO EN CASO DE QUE EL BACKEND DEVUELVA ARCHIVOS BLOB
import { HttpErrorResponse } from '@angular/common/http';

export type ToastSeverity = 'success' | 'info' | 'warn' | 'error';

export interface ParsedHttpError {
  status: number;
  severity: ToastSeverity;
  title: string;   // e.g., "Error 422"
  detail: string;  // mensaje legible
}

/**
 * Convierte cualquier error de HttpClient en un objeto listo para toasts.
 * Soporta:
 * - Laravel 422: { message, errors: { campo: [msg, ...] } }
 * - Mensaje plano: error.error como string
 * - Error como objeto: error.error.message o valores del objeto
 * - Falla de red (status 0)
 * - Respuestas Blob (descarga) que en realidad contienen JSON de error
 */
export async function parseHttpError(err: unknown): Promise<ParsedHttpError> {
  let status = 0;
  let detail: string | null = null;

  // Intento especial: si viene como Blob (descarga fallida) y parece JSON
  const tryParseBlob = async (e: any) => {
    try {
      const blob = e?.error;
      if (blob instanceof Blob) {
        const text = await blob.text();
        try {
          const json = JSON.parse(text);
          return json;
        } catch {
          // no era JSON; devolvemos el texto crudo
          return { message: text };
        }
      }
    } catch {}
    return null;
  };

  // 1) HttpErrorResponse
  if (err instanceof HttpErrorResponse) {
    status = err.status ?? 0;

    // Si la carga útil viene en Blob (descargas)
    const blobJson = await tryParseBlob(err);
    const payload = blobJson ?? err.error;

    // a) Cadena plana
    if (typeof payload === 'string') {
      detail = payload.trim();
    }
    // b) Objeto con message/errors (Laravel, Nest, etc.)
    if (!detail && payload && typeof payload === 'object') {
      // mensaje principal
      const msg =
        (payload as any).message ??
        (payload as any).error ??
        null;

      // errores de validación { field: [msg1, msg2], ... }
      const val = (payload as any).errors;
      const valMsgs = val
        ? Object.values(val)
            .flat()
            .filter(Boolean)
            .map(String)
            .join(', ')
        : null;

      // fallback: concatenar valores si es un objeto simple
      const concat =
        !msg && !valMsgs
          ? Object.values(payload as any)
              .flat()
              .filter(v => typeof v === 'string')
              .join(', ')
          : null;

      detail = (valMsgs ?? msg ?? concat ?? '').toString().trim() || null;
    }

    // c) Fallback común del HttpErrorResponse
    if (!detail) {
      detail = (err.message || '').toString().trim() || null;
    }
  }

  // 2) Cualquier otro error (throw new Error, etc.)
  if (!status && !(err instanceof HttpErrorResponse)) {
    const maybeMsg = (err as any)?.message ?? String(err ?? '');
    detail = (maybeMsg || 'Ocurrió un error inesperado.').toString();
  }

  // Normalizar
  if (!detail) {
    detail =
      status === 0
        ? 'Sin conexión o la petición fue bloqueada.'
        : 'Ocurrió un error inesperado.';
  }

  // Severidad según status
  const severity: ToastSeverity =
    status >= 500 ? 'error'
    : status === 0   ? 'warn'
    : status === 422 ? 'warn'
    : status >= 400 ? 'warn'
    : 'error';

  const title = `Error${status ? ' ' + status : ''}`;

  return { status, severity, title, detail };
}
