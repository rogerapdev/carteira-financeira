<?php

namespace App\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{

    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\AuditoriaFactory::new();
    }

    protected $table = 'auditoria';

    protected $fillable = [
        'acao',
        'recurso',
        'usuario_id',
        'request_id',
        'ip',
        'method',
        'url',
        'user_agent',
        'detalhes',
        'nivel'
    ];

    protected $casts = [
        'detalhes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obtém o usuário relacionado ao registro de auditoria.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}