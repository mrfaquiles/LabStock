<?php

namespace App\Models\Concerns;

use App\Models\historico_alteracao;
use Illuminate\Database\Eloquent\Model;

/**
 * Grava em historico_alteracoes cada criação, edição e exclusão do model,
 * com o usuário logado e o valor antes/depois de cada campo alterado.
 *
 * Uso: `use RegistraHistorico;` no model.
 * Campos ignorados podem ser definidos em `protected array $historicoIgnorar`.
 * Para registrar só algumas ações: `protected array $historicoAcoes = ['alterado', 'excluido'];`
 */
trait RegistraHistorico
{
    public static function bootRegistraHistorico(): void
    {
        static::created(function (Model $model) {
            if (!$model->registraAcao('criado')) {
                return;
            }
            $model->gravarHistorico('criado', collect($model->getAttributes())
                ->except($model->camposIgnoradosHistorico())
                ->map(fn ($valor) => ['antes' => null, 'depois' => $valor])
                ->all());
        });

        static::updated(function (Model $model) {
            if (!$model->registraAcao('alterado')) {
                return;
            }
            $alteracoes = [];
            foreach ($model->getChanges() as $campo => $novo) {
                if (in_array($campo, $model->camposIgnoradosHistorico(), true)) {
                    continue;
                }
                $antigo = $model->getOriginal($campo);
                // Ignora "mudanças" que são só de tipo (ex.: 1 e "1")
                if ((string) $antigo === (string) $novo) {
                    continue;
                }
                $alteracoes[$campo] = ['antes' => $antigo, 'depois' => $novo];
            }
            if ($alteracoes) {
                $model->gravarHistorico('alterado', $alteracoes);
            }
        });

        // Guarda o que foi apagado, para o registro não sumir sem deixar rastro
        static::deleted(function (Model $model) {
            if (!$model->registraAcao('excluido')) {
                return;
            }
            $model->gravarHistorico('excluido', collect($model->getAttributes())
                ->except($model->camposIgnoradosHistorico())
                ->map(fn ($valor) => ['antes' => $valor, 'depois' => null])
                ->all());
        });
    }

    protected function registraAcao(string $acao): bool
    {
        return in_array($acao, $this->historicoAcoes ?? ['criado', 'alterado', 'excluido'], true);
    }

    protected function camposIgnoradosHistorico(): array
    {
        return array_merge(['created_at', 'updated_at', $this->getKeyName()], $this->historicoIgnorar ?? []);
    }

    protected function gravarHistorico(string $acao, ?array $alteracoes): void
    {
        historico_alteracao::create([
            'entidade' => $this->getTable(),
            'registro_id' => $this->getKey(),
            'acao' => $acao,
            'alteracoes' => $alteracoes,
            'idusuario' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    public function historico()
    {
        return $this->hasMany(historico_alteracao::class, 'registro_id', $this->getKeyName())
            ->where('entidade', $this->getTable());
    }
}
