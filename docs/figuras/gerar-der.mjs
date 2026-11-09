// Gera o DER simplificado (der.svg). Uso: node gerar-der.mjs
import fs from 'fs';

const W = 280, COL = (i) => 30 + i * 335, HEAD = 34, LINHA = 23, PAD = 10;
const ROW = [80, 380, 650, 910];

const ent = {};
const E = (id, c, r, attrs) => {
  ent[id] = { id, x: COL(c), y: ROW[r], attrs, h: HEAD + attrs.length * LINHA + PAD };
};

E('unidade_medidas', 0, 0, ['idunidademedida PK', 'nome', 'sigla (kg, L, un)']);
E('reagentes', 1, 0, ['idreagente PK', 'idunidademedida FK', 'nome', 'catmat', 'cas', 'controlado', 'classificacao_controle', 'quantidade']);
E('entrada_reagentes', 2, 0, ['identradareagente PK', 'idreagente FK', 'idlaboratorio FK', 'idusuario FK', 'lote', 'data_validade', 'quantidade']);
E('saida_reagentes', 3, 0, ['idsaidareagente PK', 'idreagente FK', 'identrada FK', 'idusuario FK', 'quantidade', 'data', 'observacao']);
E('users', 4, 0, ['id PK', 'nome', 'email', 'tipo (perfil)', 'ativo']);

E('pedidos_compra', 0, 1, ['idpedido PK', 'idreagente FK', 'idvidraria FK', 'idusuario FK', 'quantidade', 'status']);
E('vidrarias', 1, 1, ['idvidraria PK', 'idlaboratorio FK', 'nome', 'catmat', 'quantidade']);
E('entrada_vidrarias', 2, 1, ['identravidraria PK', 'idvidraria FK', 'idlaboratorio FK', 'idusuario FK', 'quantidade']);
E('saida_vidrarias', 3, 1, ['idsaidavidraria PK', 'idvidraria FK', 'idusuario FK', 'idaprovador FK', 'quantidade', 'motivo', 'status']);
E('historico_alteracoes', 4, 1, ['id PK', 'idusuario FK', 'entidade', 'registro_id', 'acao', 'alteracoes']);

E('equipamento_locais', 0, 2, ['id PK', 'idequipamento FK', 'idlaboratorio FK', 'quantidade']);
E('equipamentos', 1, 2, ['idequipamento PK', 'nome', 'catmat', 'status', 'quantidade']);
E('entrada_equipamentos', 2, 2, ['identradaequipamento PK', 'idequipamento FK', 'idlaboratorio FK', 'idusuario FK', 'quantidade']);
E('saida_equipamentos', 3, 2, ['idsaidaequipamento PK', 'idequipamento FK', 'idlaboratorio FK', 'idusuario FK', 'quantidade']);
E('transferencias_equipamento', 4, 2, ['idtransferencia PK', 'idequipamento FK', 'idorigem FK', 'iddestino FK', 'idusuario FK', 'quantidade']);

E('laboratorios', 0, 3, ['idlaboratorio PK', 'idunidade FK', 'nome']);
E('unidades', 1, 3, ['idunidade PK', 'nome (campus)']);
E('equipamento_patrimonios', 2, 3, ['idpatrimonio PK', 'idequipamento FK', 'idlaboratorio FK', 'identrada FK', 'idsaida FK', 'numero', 'baixado_em']);
E('configuracoes', 4, 3, ['id PK', 'chave', 'valor']);

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
let svg = '';

// Ligações: pontos [x,y]; a = lado "1", b = lado "N"
const pt = {
  r: (e, i) => [ent[e].x + W, ent[e].y + HEAD + LINHA * i + 16],
  l: (e, i) => [ent[e].x, ent[e].y + HEAD + LINHA * i + 16],
  t: (e, dx = W / 2) => [ent[e].x + dx, ent[e].y],
  b: (e, dx = W / 2) => [ent[e].x + dx, ent[e].y + ent[e].h],
};
function rotulo([x1, y1], [x2, y2], txt) {
  const dx = Math.sign(x2 - x1), dy = Math.sign(y2 - y1);
  const x = x1 + dx * 14 + (dy !== 0 ? 12 : 0);
  const y = dx !== 0 ? y1 - 7 : (dy < 0 ? y1 - 6 : y1 + 20);
  svg += `<text x="${x}" y="${y}" font-size="17" font-weight="bold" text-anchor="middle">${txt}</text>`;
}
function liga(pontos) {
  svg += `<polyline points="${pontos.map((p) => p.join(',')).join(' ')}" fill="none" stroke="#111" stroke-width="1.8"/>`;
  rotulo(pontos[0], pontos[1], '1');
  rotulo(pontos[pontos.length - 1], pontos[pontos.length - 2], 'N');
}
const via = (a, b, y) => [a, [a[0], y], [b[0], y], b];

// Reagentes
liga([pt.r('unidade_medidas', 0), [ent.reagentes.x, pt.r('unidade_medidas', 0)[1]]].map((p, i) => i ? [p[0], pt.l('reagentes', 1)[1]] : [p[0], pt.l('reagentes', 1)[1]]));
liga([pt.r('reagentes', 0), pt.l('entrada_reagentes', 1)].map((p) => [p[0], pt.l('entrada_reagentes', 1)[1]]));
liga([pt.r('entrada_reagentes', 0), pt.l('saida_reagentes', 2)].map((p) => [p[0], pt.l('saida_reagentes', 2)[1]]));
liga(via(pt.t('reagentes', 220), pt.t('saida_reagentes', 140), 52));
liga([pt.t('reagentes', 60), [pt.t('reagentes', 60)[0], 340], [pt.t('pedidos_compra', 140)[0], 340], pt.t('pedidos_compra', 140)]);
// Vidrarias
liga([pt.l('vidrarias', 0), pt.r('pedidos_compra', 2)].map((p) => [p[0], pt.r('pedidos_compra', 2)[1]]).reverse());
liga([pt.r('vidrarias', 0), pt.l('entrada_vidrarias', 1)].map((p) => [p[0], pt.l('entrada_vidrarias', 1)[1]]));
liga(via(pt.t('vidrarias', 220), pt.t('saida_vidrarias', 140), 350));
// Usuários e histórico
liga([pt.b('users'), pt.t('historico_alteracoes')]);
// Equipamentos
liga([pt.l('equipamentos', 0), pt.r('equipamento_locais', 1)].map((p) => [p[0], pt.r('equipamento_locais', 1)[1]]));
liga([pt.r('equipamentos', 0), pt.l('entrada_equipamentos', 1)].map((p) => [p[0], pt.l('entrada_equipamentos', 1)[1]]));
liga(via(pt.t('equipamentos', 240), pt.t('saida_equipamentos', 140), 622));
liga(via(pt.t('equipamentos', 180), pt.t('transferencias_equipamento', 140), 600));
liga([pt.b('entrada_equipamentos', 140), pt.t('equipamento_patrimonios', 140)]);
liga([pt.b('equipamentos', 200), [pt.b('equipamentos', 200)[0], 860], [pt.t('equipamento_patrimonios', 60)[0], 860], pt.t('equipamento_patrimonios', 60)]);
liga([pt.b('saida_equipamentos', 140), [pt.b('saida_equipamentos', 140)[0], 880], [pt.t('equipamento_patrimonios', 220)[0], 880], pt.t('equipamento_patrimonios', 220)].map((p, i) => i === 1 || i === 2 ? [p[0], 860] : p));
// Locais
liga([pt.t('laboratorios'), pt.b('equipamento_locais')]);
liga([pt.l('unidades', 0), pt.r('laboratorios', 1)].map((p) => [p[0], pt.r('laboratorios', 1)[1]]).reverse());

// Entidades
for (const e of Object.values(ent)) {
  svg += `<rect x="${e.x}" y="${e.y}" width="${W}" height="${e.h}" fill="#fff" stroke="#111" stroke-width="2"/>`;
  svg += `<rect x="${e.x}" y="${e.y}" width="${W}" height="${HEAD}" fill="#dcdcdc" stroke="#111" stroke-width="2"/>`;
  svg += `<text x="${e.x + W / 2}" y="${e.y + 23}" font-size="18" font-weight="bold" text-anchor="middle">${esc(e.id)}</text>`;
  e.attrs.forEach((a, i) => {
    const m = a.match(/^(.*?) (PK|FK)$/);
    const y = e.y + HEAD + LINHA * i + 21;
    if (m) {
      svg += `<text x="${e.x + 12}" y="${y}" font-size="16"${m[2] === 'PK' ? ' font-weight="bold" text-decoration="underline"' : ''}>${esc(m[1])}</text>`;
      svg += `<text x="${e.x + W - 12}" y="${y}" font-size="13" font-style="italic" text-anchor="end">${m[2]}</text>`;
    } else {
      svg += `<text x="${e.x + 12}" y="${y}" font-size="16">${esc(a)}</text>`;
    }
  });
}

const H = 1190;
svg += `<text x="30" y="${H - 52}" font-size="16">PK: chave primária · FK: chave estrangeira · 1 e N: cardinalidade do relacionamento.</text>`;
svg += `<text x="30" y="${H - 26}" font-size="16">Para simplificar, as ligações de idusuario (users) e idlaboratorio (laboratorios) aparecem apenas como atributos FK.</text>`;

fs.writeFileSync(new URL('./der.svg', import.meta.url), `<svg xmlns="http://www.w3.org/2000/svg" width="1680" height="${H}" viewBox="0 0 1680 ${H}" font-family="'Times New Roman', Times, serif" fill="#111"><rect width="1680" height="${H}" fill="#fff"/>${svg}</svg>`);
console.log('der.svg gerado');
