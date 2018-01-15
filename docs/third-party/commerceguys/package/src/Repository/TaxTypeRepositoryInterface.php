<?php

namespace CommerceGuys\Tax\Repository;

/**
 * Tax type repository interface.
 */
interface TaxTypeRepositoryInterface
{
    /**
     * Returns a tax type instance matching the provided id.
     *
     * @param string $id The id.
     *
     * @return TaxTypeInterface
     */
    public function get($id);

    /**
     * Returns all available tax type instances.
     *
     * @return TaxTypeInterface[] An array of tax type instances.
     */
    public function getAll();
}
