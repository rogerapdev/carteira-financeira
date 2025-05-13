<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Auditoria;
use App\Domain\Interfaces\AuditoriaRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class AuditoriaRepository implements AuditoriaRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function criar(array $dados): Auditoria
    {
        return Auditoria::create($dados);
    }

    /**
     * @inheritDoc
     */
    public function buscarComFiltros(array $filtros): array
    {
        $query = Auditoria::query();

        if (isset($filtros['acao'])) {
            $query->where('acao', 'like', '%' . $filtros['acao'] . '%');
        }

        if (isset($filtros['recurso'])) {
            $query->where('recurso', $filtros['recurso']);
        }

        if (isset($filtros['usuario_id'])) {
            $query->where('usuario_id', $filtros['usuario_id']);
        }

        if (isset($filtros['nivel'])) {
            $query->where('nivel', $filtros['nivel']);
        }

        if (isset($filtros['data_inicio'])) {
            $query->where('created_at', '>=', $filtros['data_inicio']);
        }

        if (isset($filtros['data_fim'])) {
            $query->where('created_at', '<=', $filtros['data_fim']);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * @inheritDoc
     */
    public function buscarPorId(int $id): ?Auditoria
    {
        return Auditoria::find($id);
    }
}