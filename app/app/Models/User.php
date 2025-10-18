<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'id', 'estado_id', 'cidade_id', 'nome'
    ];

    /**
     * @return Attribute
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return str_replace(['/'], '-', $value);
            },
        );
    }
    /**
     * @return BelongsTo
     */
    public function Estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    /**
     * @return BelongsTo
     */
    public function Cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }
}
