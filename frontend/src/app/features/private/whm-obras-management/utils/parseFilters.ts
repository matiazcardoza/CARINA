// export default parseFilters(filters: TableLazyLoadEvent['filters']) {
//   const out: Record<string, any> = {};
//   if (!filters) return out;

//   for (const [field, meta] of Object.entries(filters)) {
//     // meta puede ser FilterMetadata o FilterMetadata[]
//     const m = meta as FilterMetadata | FilterMetadata[];
//     if (Array.isArray(m)) {
//       // Modo "advanced" con múltiples constraints
//       const firstConstraint = m[0];
//       if (firstConstraint?.value !== undefined && firstConstraint?.value !== null && firstConstraint?.value !== '') {
//         out[field] = firstConstraint.value;
//         out[`${field}MatchMode`] = firstConstraint.matchMode; // 'contains', 'equals', etc.
//       }
//     } else {
//       if (m?.value !== undefined && m?.value !== null && m?.value !== '') {
//         out[field] = m.value;
//         out[`${field}MatchMode`] = m.matchMode; // 'contains', 'equals', etc.
//       }
//     }
//   }
//   return out;
// }