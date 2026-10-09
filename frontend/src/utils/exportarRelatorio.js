import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import { useConfigStore } from '../stores/configStore';

/*
 * Exportação de relatórios do LabStock.
 * Gera o arquivo direto no navegador (sem janela pop-up, que os navegadores bloqueiam).
 *
 * Formato esperado:
 *   {
 *     titulo: 'Relatório de Gastos',
 *     subtitulo: 'Período: 01/10/2026 a 09/10/2026',
 *     nomeArquivo: 'labstock-gastos-2026-10',      // sem extensão
 *     tabelas: [{ titulo: 'Resumo', colunas: ['Item', 'Total'], linhas: [['Béquer', '2']] }],
 *   }
 */

const MARINHO = [20, 40, 75];
const CINZA = [110, 110, 110];

// Instituição e laboratório definidos em Configurações do Sistema
const identificacaoInstituicao = () => {
  const { config, identificacao } = useConfigStore();
  return [identificacao, config.responsavel_tecnico && `Responsável: ${config.responsavel_tecnico}`]
    .filter(Boolean)
    .join('   |   ') || 'Sistema de Gestão para Laboratórios';
};

const dataHoraAtual = () =>
  `${new Date().toLocaleDateString('pt-BR')} às ${new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`;

export function baixarPdf({ titulo, subtitulo = '', tabelas, nomeArquivo }) {
  // Paisagem: os relatórios têm muitas colunas
  const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
  const largura = doc.internal.pageSize.getWidth();
  const margem = 12;

  // Faixa de cabeçalho
  doc.setFillColor(...MARINHO);
  doc.rect(0, 0, largura, 22, 'F');
  doc.setTextColor(255, 255, 255);
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(15);
  doc.text(`LabStock - ${titulo}`, margem, 11);
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(9);
  doc.text(identificacaoInstituicao(), margem, 17);

  let y = 30;
  if (subtitulo) {
    doc.setTextColor(...CINZA);
    doc.setFontSize(10);
    doc.text(subtitulo, margem, y);
    y += 6;
  }

  tabelas.forEach((tabela) => {
    if (tabela.titulo) {
      doc.setTextColor(...MARINHO);
      doc.setFont('helvetica', 'bold');
      doc.setFontSize(12);
      doc.text(tabela.titulo, margem, y + 4);
      y += 7;
    }

    autoTable(doc, {
      startY: y,
      head: [tabela.colunas],
      body: tabela.linhas.length
        ? tabela.linhas.map((linha) => linha.map((v) => String(v ?? '')))
        : [[{ content: 'Nenhum registro no período', colSpan: tabela.colunas.length, styles: { halign: 'center', textColor: CINZA } }]],
      margin: { left: margem, right: margem, top: 14, bottom: 14 },
      styles: { font: 'helvetica', fontSize: 8.5, cellPadding: 2, overflow: 'linebreak', valign: 'middle' },
      headStyles: { fillColor: MARINHO, textColor: 255, fontStyle: 'bold' },
      alternateRowStyles: { fillColor: [245, 247, 251] },
    });

    y = doc.lastAutoTable.finalY + 10;
  });

  // Rodapé em todas as páginas: data de emissão e numeração
  const totalPaginas = doc.getNumberOfPages();
  const altura = doc.internal.pageSize.getHeight();
  for (let pagina = 1; pagina <= totalPaginas; pagina++) {
    doc.setPage(pagina);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(...CINZA);
    doc.text(`Gerado em ${dataHoraAtual()}`, margem, altura - 6);
    doc.text(`Página ${pagina} de ${totalPaginas}`, largura - margem, altura - 6, { align: 'right' });
  }

  doc.save(`${nomeArquivo}.pdf`);
}

// CSV com ";" e BOM UTF-8: abre direto no Excel em português, com acentos corretos
export function baixarCsv({ titulo, subtitulo = '', tabelas, nomeArquivo }) {
  const celula = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
  const linhas = [[`LabStock - ${titulo}`], [identificacaoInstituicao()]];
  if (subtitulo) linhas.push([subtitulo]);

  tabelas.forEach((tabela) => {
    linhas.push([]);
    if (tabela.titulo) linhas.push([tabela.titulo]);
    linhas.push(tabela.colunas, ...tabela.linhas);
  });
  linhas.push([], [`Gerado em ${dataHoraAtual()}`]);

  const csv = '﻿' + linhas.map((linha) => linha.map(celula).join(';')).join('\r\n');
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
  const link = document.createElement('a');
  link.href = url;
  link.download = `${nomeArquivo}.csv`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
