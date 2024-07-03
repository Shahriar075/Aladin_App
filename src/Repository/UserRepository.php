<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all users belonging to a specific team.
     *
     * @param int $teamId The ID of the team
     * @return User[] Returns an array of User objects
     */
    public function findAllByTeamId(int $teamId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.team = :teamId')
            ->setParameter('teamId', $teamId)
            ->getQuery()
            ->getResult();
    }

    public function findUsersByName($name)
    {
        return $this->createQueryBuilder('u')
            ->where('u.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }

}
