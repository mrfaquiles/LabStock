<?php

// Mensagens de validação em português (APP_LOCALE=pt).
// Regras não listadas aqui caem no inglês padrão do Laravel.
return [
    'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'exists' => 'O :attribute selecionado é inválido.',
    'gt' => [
        'numeric' => 'O campo :attribute deve ser maior que :value.',
    ],
    'in' => 'O :attribute selecionado é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'numeric' => 'O campo :attribute deve ser um número.',
    'password' => [
        'letters' => 'A senha deve conter pelo menos uma letra.',
        'numbers' => 'A senha deve conter pelo menos um número.',
        'mixed' => 'A senha deve conter letras maiúsculas e minúsculas.',
        'symbols' => 'A senha deve conter pelo menos um símbolo.',
        'uncompromised' => 'Esta senha apareceu em vazamentos de dados. Escolha outra.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',

    'attributes' => [
        'catmat' => 'CATMAT',
        'data' => 'data',
        'data_validade' => 'data de validade',
        'descricao' => 'descrição',
        'email' => 'e-mail',
        'idequipamento' => 'equipamento',
        'identrada' => 'lote',
        'idlaboratorio' => 'laboratório',
        'idreagente' => 'reagente',
        'idunidademedida' => 'unidade de medida',
        'idvidraria' => 'vidraria',
        'localizacao' => 'localização',
        'meses_alerta' => 'alerta de vencimento',
        'observacao' => 'observação',
        'password' => 'senha',
        'patrimonio' => 'patrimônio',
        'tipo' => 'perfil',
    ],
];
