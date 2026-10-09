<?php

namespace Tests\Unit;

use App\Support\Quantidade;
use PHPUnit\Framework\TestCase;

class QuantidadeTest extends TestCase
{
    public function test_escolhe_a_unidade_mais_legivel(): void
    {
        $this->assertSame('2,5 kg', Quantidade::formatar(2.5, 'kg'));
        $this->assertSame('500 g', Quantidade::formatar(0.5, 'kg'));
        $this->assertSame('1,5 g', Quantidade::formatar(0.0015, 'kg'));
    }

    public function test_miligramas_e_microlitros(): void
    {
        $this->assertSame('250 mg', Quantidade::formatar(0.00025, 'kg'));
        $this->assertSame('250 mL', Quantidade::formatar(0.25, 'L'));
        $this->assertSame('5 µL', Quantidade::formatar(0.000005, 'L'));
    }

    public function test_zero_e_unidade_sem_subdivisao(): void
    {
        $this->assertSame('0 kg', Quantidade::formatar(0, 'kg'));
        $this->assertSame('12 un', Quantidade::formatar(12, 'un'));
    }
}
