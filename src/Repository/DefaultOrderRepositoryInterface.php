<?php

namespace App\Repository;

interface DefaultOrderRepositoryInterface
{
    /**
     * Default ORDER BY clauses applied to every collection query.
     * Keys are entity property names, values are 'ASC' or 'DESC'.
     *
     * @return array<string, 'ASC'|'DESC'>
     */
    public function getDefaultOrder(): array;
}
