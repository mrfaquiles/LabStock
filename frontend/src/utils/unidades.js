/*
 * Conversão de unidades de reagentes.
 *
 * Tudo é guardado na unidade base do reagente (kg, L ou un). Na tela, a pessoa
 * digita no que for mais natural (g, mg, mL, µL) e o valor é convertido.
 * Mesma regra do backend (app/Support/Quantidade.php).
 */

// fator = quanto 1 unidade vale na unidade base
export const ESCALAS = {
  kg: [{ sigla: 'kg', fator: 1 }, { sigla: 'g', fator: 0.001 }, { sigla: 'mg', fator: 0.000001 }],
  L: [{ sigla: 'L', fator: 1 }, { sigla: 'mL', fator: 0.001 }, { sigla: 'µL', fator: 0.000001 }],
};

// Unidades oferecidas para um reagente (sem subdivisão, só a própria unidade)
export const unidadesDisponiveis = (siglaBase) => ESCALAS[siglaBase] || [{ sigla: siglaBase || 'un', fator: 1 }];

// 500 g → 0.5 (kg). Seis casas decimais, como no banco (precisão de 1 mg / 1 µL)
export const paraBase = (valor, fator = 1) => Math.round(Number(valor || 0) * Number(fator) * 1e6) / 1e6;

const numero = (v, casas = 3) => Number(v).toLocaleString('pt-BR', { maximumFractionDigits: casas });

// Maior unidade em que o valor fica >= 1. Ex.: 0.0005 kg → { valor: 0.5, sigla: 'g' }
export const melhorUnidade = (valorBase, siglaBase) => {
  const escalas = unidadesDisponiveis(siglaBase);
  const v = Number(valorBase || 0);
  if (v === 0) return { valor: 0, sigla: escalas[0].sigla };
  const escolhida = escalas.find((e) => Math.abs(v) >= e.fator) || escalas[escalas.length - 1];
  return { valor: v / escolhida.fator, sigla: escolhida.sigla };
};

// "0,5 g", "2,5 kg", "250 mL"
export const formatarQuantidade = (valorBase, siglaBase) => {
  const { valor, sigla } = melhorUnidade(valorBase, siglaBase);
  return `${numero(valor, 6)} ${sigla}`.trim();
};

// Na unidade base, sem arredondar demais: "0,00025 kg"
export const formatarNaBase = (valorBase, siglaBase) => `${numero(valorBase || 0, 6)} ${siglaBase || ''}`.trim();
