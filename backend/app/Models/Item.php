<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id_item';
    public $timestamps = false;
}