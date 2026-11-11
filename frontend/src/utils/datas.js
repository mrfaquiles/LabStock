// Datas no formato do banco (AAAA-MM-DD) sempre pelo fuso local.
// Não usar toISOString(): ele converte para UTC e, no Brasil, depois das 21h já devolve o dia seguinte.

export const paraIso = (d) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

export const hojeIso = () => paraIso(new Date());

// "2026-03-05" -> Date às 00:00 do horário local
export const paraData = (iso) => (iso ? new Date(String(iso).substring(0, 10) + 'T00:00:00') : null);

// "2026-03-05" (ou "2026-03-05 14:30:00") -> "05/03/2026"
export const formatarData = (iso) => {
  if (!iso) return '';
  const [a, m, d] = String(iso).substring(0, 10).split('-');
  return d ? `${d}/${m}/${a}` : String(iso);
};
