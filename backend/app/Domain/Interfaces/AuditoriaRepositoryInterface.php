<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\Auditoria;

interface AuditoriaRepositoryInterface
{
    /**
     * Cria um novo registro de auditoria.
     *
     * @param array $dados Dados do registro de auditoria
     * @return Auditoria
     */
    public function criar(array $dados): Auditoria;

    /**
     * Busca registros de auditoria com filtros.
     *
     * @param array $filtros
     * @return array
     */
    public function buscarComFiltros(array $filtros): array;

    /**
     * Busca um registro de auditoria pelo ID.
     *
     * @param int $id
     * @return Auditoria|null
     */
    public function buscarPorId(int $id): ?Auditoria;
}