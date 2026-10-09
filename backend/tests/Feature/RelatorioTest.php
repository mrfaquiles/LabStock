<?php

namespace Tests\Feature;

use App\Models\laboratorio;
use App\Models\User;
use App\Models\vidraria;
use Database\Seeders\UnidadeMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    private array $reagente;
    private int $lote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnidadeMedidaSeeder::class);
        Sanctum::actingAs(User::factory()->create(['tipo' => 'tecnico']));

        // Lote inicial de 10 kg registrado em 01/03/2026
        $this->reagente = $this->postJson('/api/reagentes', [
            'nome' => 'Percarbonato de Sódio', 'catmat' => '123', 'idunidademedida' => 1,
            'quantidade' => 10, 'lote' => 'LOT-A', 'data_validade' => '2030-01-01',
        ])->json();
        $this->lote = $this->reagente['lotes'][0]['identradareagente'];
        \App\Models\entrada_reagente::find($this->lote)->update(['data' => '2026-03-01']);

        // Usos: 1 kg em março, 0.5 kg em abril, 2 kg em maio
        foreach ([['2026-03-15', 1], ['2026-04-10', 0.5], ['2026-05-20', 2]] as [$data, $qtd]) {
            $this->postJson('/api/saidareagentes', [
                'idreagente' => $this->reagente['idreagente'], 'identrada' => $this->lote,
                'quantidade' => $qtd, 'data' => $data, 'observacao' => "Uso de $data",
            ])->assertCreated();
        }
    }

    public function test_relatorio_de_estoque_calcula_saldos_do_periodo(): void
    {
        // Abril: começa com 9 kg (10 - 1 de março), sai 0.5, termina com 8.5
        $linha = $this->getJson('/api/relatorios/estoque?data_inicio=2026-04-01&data_fim=2026-04-30&categoria=reagentes')
            ->assertOk()
            ->json('itens.0');

        $this->assertEquals(9, $linha['saldo_inicial']);
        $this->assertEquals(0, $linha['entradas']);
        $this->assertEquals(0.5, $linha['saidas']);
        $this->assertEquals(8.5, $linha['saldo_final']);
        $this->assertEquals('kg', $linha['unidade']);
    }

    public function test_relatorio_de_estoque_inclui_entrada_do_periodo(): void
    {
        $linha = $this->getJson('/api/relatorios/estoque?data_inicio=2026-03-01&data_fim=2026-03-31&categoria=reagentes')
            ->json('itens.0');

        $this->assertEquals(0, $linha['saldo_inicial']);
        $this->assertEquals(10, $linha['entradas']);
        $this->assertEquals(1, $linha['saidas']);
        $this->assertEquals(9, $linha['saldo_final']);
    }

    public function test_relatorio_de_gastos_filtra_periodo_e_calcula_media(): void
    {
        // Abril e maio (61 dias = ~2 meses): 2.5 kg consumidos
        $resposta = $this->getJson('/api/relatorios/gastos?data_inicio=2026-04-01&data_fim=2026-05-31&categoria=reagentes')
            ->assertOk();

        $this->assertCount(2, $resposta->json('detalhes'));
        $this->assertEquals('LOT-A', $resposta->json('detalhes.0.lote'));

        $resumo = $resposta->json('resumo.0');
        $this->assertEquals(2.5, $resumo['total']);
        $this->assertEquals(2, $resumo['registros']);
        $this->assertEqualsWithDelta(1.25, $resumo['media_mensal'], 0.01);
        $this->assertEquals(6.5, $resumo['estoque_atual']);
        $this->assertEqualsWithDelta(5.2, $resumo['cobertura_meses'], 0.1);
    }

    public function test_relatorio_de_gastos_inclui_so_baixas_de_vidraria_aprovadas(): void
    {
        $lab = laboratorio::create(['nome' => 'Lab 1']);
        $vidraria = vidraria::create(['nome' => 'Béquer', 'catmat' => 'V1', 'quantidade' => 5]);
        $solicitar = fn (string $justificativa) => $this->postJson('/api/saidavidrarias', [
            'idvidraria' => $vidraria->idvidraria, 'idlaboratorio' => $lab->idlaboratorio,
            'quantidade' => 2, 'data' => '2026-04-05', 'motivo' => 'Quebra', 'observacao' => $justificativa,
        ])->assertCreated()->json('idsaidavidraria');

        $aprovada = $solicitar('Quebrou na aula prática');
        $solicitar('Ainda aguardando o admin');

        Sanctum::actingAs(User::factory()->create(['tipo' => 'admin']));
        $this->postJson("/api/saidavidrarias/{$aprovada}/aprovar")->assertOk();

        $resposta = $this->getJson('/api/relatorios/gastos?data_inicio=2026-04-01&data_fim=2026-04-30')
            ->assertOk();

        $vidrarias = collect($resposta->json('detalhes'))->where('categoria', 'vidrarias')->values();
        $this->assertCount(1, $vidrarias);
        $this->assertEquals('Lab 1', $vidrarias[0]['laboratorio']);
        $this->assertEquals('Quebra: Quebrou na aula prática', $vidrarias[0]['observacao']);
    }

    public function test_periodo_invalido_e_recusado(): void
    {
        $this->getJson('/api/relatorios/gastos?data_inicio=2026-05-01&data_fim=2026-04-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['data_fim']);
    }

    public function test_perfil_consulta_pode_emitir_relatorios(): void
    {
        Sanctum::actingAs(User::factory()->create(['tipo' => 'consulta']));

        $this->getJson('/api/relatorios/estoque?data_inicio=2026-01-01&data_fim=2026-12-31')->assertOk();
    }
}
