<?php

namespace App\Repository;

use App\Entity\Authentication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Authentication>
 */
class AuthenticationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Authentication::class);
    }

    public function findActiveAuthentication(User $user)
    {
        return $this->createQueryBuilder('auth')
            ->select('auth')
            ->where('auth.user = :user')
            ->andWhere('auth.signOut IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
