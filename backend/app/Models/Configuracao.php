<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configurações do sistema em formato chave/valor.
 *
 * Uso: Configuracao::valor('meses_alerta_padrao')  // 4 se ninguém alterou
 */
class Configuracao extends Model
{
    protected $table = 'configuracoes';
    protected $fillable = ['chave', 'valor'];

    private const CACHE = 'labstock.configuracoes';

    /** Chaves aceitas, com valor padrão e tipo. */
    public const PADROES = [
        // Instituição (aparece no cabeçalho dos relatórios em PDF)
        'instituicao_nome' => ['padrao' => 'Instituto Federal', 'tipo' => 'string'],
        'laboratorio_nome' => ['padrao' => 'Laboratório de Química', 'tipo' => 'string'],
        'responsavel_tecnico' => ['padrao' => '', 'tipo' => 'string'],

        // Estoque
        'meses_alerta_padrao' => ['padrao' => 4, 'tipo' => 'int'],
        'cobertura_minima_meses' => ['padrao' => 3, 'tipo' => 'int'],

        // Vidrarias: baixa precisa da aprovação do administrador
        'vidraria_exige_aprovacao' => ['padrao' => true, 'tipo' => 'bool'],

        // Segurança: horas até o login expirar
        'sessao_horas' => ['padrao' => 8, 'tipo' => 'int'],
    ];

    /** Todas as configurações, já com os padrões e os tipos certos. */
    public static function todas(): array
    {
        $salvas = Cache::rememberForever(self::CACHE, fn () => self::pluck('valor', 'chave')->all());

        $resultado = [];
        foreach (self::PADROES as $chave => $definicao) {
            $resultado[$chave] = array_key_exists($chave, $salvas)
                ? self::converter($salvas[$chave], $definicao['tipo'])
                : $definicao['padrao'];
        }
        return $resultado;
    }

    public static function valor(string $chave): mixed
    {
        return self::todas()[$chave] ?? null;
    }

    public static function salvar(array $valores): void
    {
        foreach ($valores as $chave => $valor) {
            if (!array_key_exists($chave, self::PADROES)) {
                continue;
            }
            if (is_bool($valor)) {
                $valor = $valor ? '1' : '0';
            }
            self::updateOrCreate(['chave' => $chave], ['valor' => $valor === null ? null : (string) $valor]);
        }
        Cache::forget(self::CACHE);
    }

    private static function converter(?string $valor, string $tipo): mixed
    {
        return match ($tipo) {
            'int' => (int) $valor,
            'bool' => in_array($valor, ['1', 'true'], true),
            default => (string) $valor,
        };
    }
}
