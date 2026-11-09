// Gera os fluxogramas (fluxo-uso-reagente.svg e fluxo-baixa-vidraria.svg). Uso: node gerar-fluxogramas.mjs
import fs from 'fs';

const FONTE = "'Times New Roman', Times, serif";
const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');

function desenho() {
  let svg = '';
  const texto = (cx, cy, linhas, tam = 18, extra = '') => {
    const h = tam * 1.25;
    linhas.forEach((l, i) => {
      const y = cy - ((linhas.length - 1) * h) / 2 + i * h + tam * 0.35;
      svg += `<text x="${cx}" y="${y}" font-size="${tam}" text-anchor="middle"${extra}>${esc(l)}</text>`;
    });
  };
  const api = {
    inicio(cx, cy, rotulo) {
      svg += `<rect x="${cx - 70}" y="${cy - 24}" width="140" height="48" rx="24" fill="#dcdcdc" stroke="#111" stroke-width="2"/>`;
      texto(cx, cy, [rotulo], 19, ' font-weight="bold"');
      return { t: [cx, cy - 24], b: [cx, cy + 24], l: [cx - 70, cy], r: [cx + 70, cy] };
    },
    caixa(cx, cy, w, h, linhas, cinza = false) {
      svg += `<rect x="${cx - w / 2}" y="${cy - h / 2}" width="${w}" height="${h}" fill="${cinza ? '#f0f0f0' : '#fff'}" stroke="#111" stroke-width="2"/>`;
      texto(cx, cy, linhas);
      return { t: [cx, cy - h / 2], b: [cx, cy + h / 2], l: [cx - w / 2, cy], r: [cx + w / 2, cy] };
    },
    decisao(cx, cy, w, h, linhas) {
      svg += `<polygon points="${cx},${cy - h / 2} ${cx + w / 2},${cy} ${cx},${cy + h / 2} ${cx - w / 2},${cy}" fill="#fff" stroke="#111" stroke-width="2"/>`;
      texto(cx, cy, linhas, 17);
      return { t: [cx, cy - h / 2], b: [cx, cy + h / 2], l: [cx - w / 2, cy], r: [cx + w / 2, cy] };
    },
    seta(pontos, rotulo, posRotulo) {
      svg += `<polyline points="${pontos.map((p) => p.join(',')).join(' ')}" fill="none" stroke="#111" stroke-width="2" marker-end="url(#seta)"/>`;
      if (rotulo) {
        const [x, y] = posRotulo || [pontos[0][0] + 10, pontos[0][1] + 22];
        svg += `<text x="${x}" y="${y}" font-size="16" font-style="italic">${rotulo}</text>`;
      }
    },
    fechar(w, h) {
      return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}" font-family="${FONTE}" fill="#111">`
        + `<defs><marker id="seta" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0,0 L10,5 L0,10 z" fill="#111"/></marker></defs>`
        + `<rect width="${w}" height="${h}" fill="#fff"/>${svg}</svg>`;
    },
  };
  return api;
}

// ---------- Figura: registro de uso de reagente ----------
{
  const d = desenho();
  const X = 330, DW = 330, DH = 116;
  const ini = d.inicio(X, 40, 'Início');
  const b1 = d.caixa(X, 135, 380, 66, ['Usuário informa o reagente, o lote,', 'a quantidade e a justificativa']);
  const d1 = d.decisao(X, 275, DW, DH, ['Perfil é administrador', 'ou técnico?']);
  const d2 = d.decisao(X, 425, DW, DH, ['Dados válidos? (mín. 1 mg,', 'data não futura)']);
  const b2 = d.caixa(X, 560, 380, 66, ['Inicia a transação e bloqueia', 'o reagente e o lote']);
  const d3 = d.decisao(X, 695, DW, DH, ['Justificativa válida?', '(obrigatória se controlado)']);
  const d4 = d.decisao(X, 845, DW, DH, ['Lote na validade ou', 'uso confirmado?']);
  const d5 = d.decisao(X, 995, DW, DH, ['Saldo do lote', 'é suficiente?']);
  const b3 = d.caixa(X, 1130, 380, 66, ['Grava o uso, desconta o estoque', 'e confirma a transação']);
  const fim = d.inicio(X, 1240, 'Fim');
  const err = d.caixa(775, 635, 250, 300, ['Recusa a operação', 'e informa o motivo', 'ao usuário.', '', 'Nada é gravado:', 'a transação', 'é desfeita.'], true);

  d.seta([ini.b, b1.t]);
  d.seta([b1.b, d1.t]);
  for (const [a, b] of [[d1, d2], [d2, b2], [d3, d4], [d4, d5]]) d.seta([a.b, b.t], 'sim');
  d.seta([b2.b, d3.t]);
  d.seta([d5.b, b3.t], 'sim');
  d.seta([b3.b, fim.t]);
  for (const dec of [d1, d2, d3, d4, d5]) {
    const y = dec.r[1];
    const destinoY = Math.min(Math.max(y, err.t[1] + 20), err.b[1] - 20);
    const xMeio = 590 + (y > err.b[1] ? 30 : 0);
    const pts = y === destinoY ? [dec.r, [err.l[0], y]] : [dec.r, [xMeio, y], [xMeio, destinoY], [err.l[0], destinoY]];
    d.seta(pts, 'não', [dec.r[0] + 12, y - 8]);
  }
  fs.writeFileSync(new URL('./fluxo-uso-reagente.svg', import.meta.url), d.fechar(920, 1280));
}

// ---------- Figura: baixa de vidraria com aprovação ----------
{
  const d = desenho();
  const X = 470, DW = 340, DH = 120;
  const ini = d.inicio(X, 40, 'Início');
  const b1 = d.caixa(X, 135, 400, 66, ['Usuário solicita a baixa: quantidade,', 'motivo e justificativa']);
  const d1 = d.decisao(X, 275, DW, DH, ['Dados válidos? (justificativa', 'com 10 caracteres ou mais)']);
  const d2 = d.decisao(X, 425, DW, DH, ['Quantidade menor ou', 'igual ao estoque?']);
  const d3 = d.decisao(X, 580, DW + 40, DH + 10, ['Solicitante é administrador', 'ou a aprovação está desligada?']);
  const b2 = d.caixa(X, 725, 380, 66, ['Solicitação fica pendente;', 'o estoque não muda']);
  const b3 = d.caixa(X, 835, 380, 50, ['Administrador analisa a solicitação']);
  const d4 = d.decisao(X, 960, DW, DH, ['Administrador', 'aprova?']);
  const d5 = d.decisao(X, 1110, DW, DH, ['Quantidade ainda cabe', 'no estoque atual?']);
  const b4 = d.caixa(X, 1245, 380, 66, ['Baixa aprovada:', 'o estoque diminui']);
  const fim = d.inicio(X, 1350, 'Fim');
  const rec = d.caixa(780, 1092, 230, 92, ['Recusada: o estoque', 'não muda e o registro', 'fica no histórico']);
  const fim2 = d.inicio(780, 1190, 'Fim');
  const err = d.caixa(120, 700, 200, 300, ['Recusa a', 'operação e', 'informa o motivo', 'ao usuário.', '', 'Nada é gravado.'], true);

  d.seta([ini.b, b1.t]);
  d.seta([b1.b, d1.t]);
  d.seta([d1.b, d2.t], 'sim');
  d.seta([d2.b, d3.t], 'sim');
  d.seta([d3.b, b2.t], 'não');
  d.seta([b2.b, b3.t]);
  d.seta([b3.b, d4.t]);
  d.seta([d4.b, d5.t], 'sim');
  d.seta([d5.b, b4.t], 'sim');
  d.seta([b4.b, fim.t]);
  // Aprovação direta: sai pela direita e entra na caixa da baixa aprovada
  d.seta([d3.r, [930, d3.r[1]], [930, b4.r[1]], b4.r], 'sim', [d3.r[0] + 12, d3.r[1] - 8]);
  // Recusa do administrador
  d.seta([d4.r, [rec.t[0], d4.r[1]], rec.t], 'não', [d4.r[0] + 12, d4.r[1] - 8]);
  d.seta([rec.b, fim2.t]);
  // Erros vão para a caixa da esquerda
  for (const dec of [d1, d2, d5]) {
    const y = dec.l[1];
    const destinoY = Math.min(Math.max(y, err.t[1] + 20), err.b[1] - 20);
    const pts = y === destinoY ? [dec.l, [err.r[0], y]] : [dec.l, [250, y], [250, destinoY], [err.r[0], destinoY]];
    d.seta(pts, 'não', [dec.l[0] - 40, y - 8]);
  }
  fs.writeFileSync(new URL('./fluxo-baixa-vidraria.svg', import.meta.url), d.fechar(960, 1390));
}
console.log('fluxogramas gerados');
