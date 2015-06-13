<?php

namespace eDemy\FbBundle\Entity;

use Doctrine\ORM\EntityRepository;

class FacebookObjectRepository extends EntityRepository
{
    public function findAllOrdered($namespace)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere('a.namespace = :namespace');
        $qb->andWhere('a.published = true');
        $qb->orderBy('a.orden','ASC');
        $qb->setParameter('namespace', $namespace);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findAllOrderedByActividad($namespace)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->andWhere('a.namespace = :namespace');
        $qb->andWhere('a.published = true');
        $qb->orderBy('a.nombre','ASC');
        $qb->setParameter('namespace', $namespace);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findLastModified($namespace = null)
    {
        $qb = $this->createQueryBuilder('a');
        if($namespace == null) {
            $qb->andWhere('a.namespace is null');
        } else {
            $qb->andWhere('a.namespace = :namespace');
            $qb->setParameter('namespace', $namespace);
        }
        $qb->orderBy('a.updated','DESC');
        $qb->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getSingleResult();
    }
}
