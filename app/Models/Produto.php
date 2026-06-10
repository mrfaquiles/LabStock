<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produto extends Model
{
    use HasFactory;

    // Define quais campos podem ser salvos via API
    protected $fillable = [
        'nome', 
        'quantidade', 
        'preco'
    ];
}