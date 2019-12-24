<?php
/**
 * @author roxblnfk
 * Date: 16.06.2019
 */

namespace roxblnfk\Soco\Repository;

use Collections\Vector;
use Countable;

interface RepositoryInterface
{
    public function find($id): ?object;

    /**
     * @return Countable|Vector
     */
    public function findAll();

    /**
     * @param array      $criteria
     * @param array|null $orderBy
     * @param null       $limit
     * @param null       $offset
     * @return Countable|Vector
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null);

    public function findOneBy(array $criteria): ?Object;

    public function getRandom(): ?object;
}
