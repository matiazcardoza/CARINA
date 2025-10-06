export function formatServerYmdHm(raw: string | null | undefined): string {
  if (!raw) return '—';
  const s = String(raw).trim().replace('T', ' ');
  // Captura solo fecha y minutos, sin interpretar zona horaria
  const m = s.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);
  if (m) return `${m[1]} ${m[2]}`;
  // Si solo hay fecha
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return `${s} 00:00`;
  // Fallback: tal cual
  return s;
}