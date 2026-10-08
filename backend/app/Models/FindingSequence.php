<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FindingSequence extends Model
{
    protected $fillable = ['fiscal_year', 'sequence'];
    protected $primaryKey = 'fiscal_year';
    protected $keyType = 'int';
    public $incrementing = false;
}
