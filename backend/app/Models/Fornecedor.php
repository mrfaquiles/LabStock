<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Útil se quiseres usar Factories

class Fornecedor extends Model
{
    use HasFactory;

    // Define os campos que podem ser gravados via $request->all()
    protected $fillable = [
        'nome', 
        'cnpj', 
        'email'
    ];
}