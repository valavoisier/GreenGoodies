<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Retourne les commandes d’un utilisateur triées par date décroissante.
     *
     * - Filtre sur l’utilisateur (WHERE o.user = :user)
     * - Trie directement en base (ORDER BY o.createdAt DESC)
     * - Renvoie une liste d’entités Order déjà ordonnée
     * 
     * Détails de la requête :
     * - createQueryBuilder('o') : construit une requête sur l’entité Order, aliasée "o"
     * - where('o.user = :user') : filtre uniquement les commandes de cet utilisateur
     * - setParameter('user', $user) : sécurise la valeur du paramètre :user
     * - orderBy('o.createdAt', 'DESC') : trie les commandes de la plus récente à la plus ancienne
     * - getQuery()->getResult() : exécute la requête et renvoie un tableau d’entités Order
     * 
     * @return Order[]
     */
    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
