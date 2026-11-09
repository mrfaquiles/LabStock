<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Laboratório usado nos cadastros e nas entradas de lote dos testes. */
    protected function labId(): int
    {
        return \App\Models\laboratorio::firstOrCreate(['nome' => 'Almoxarifado de teste'])->idlaboratorio;
    }
}
