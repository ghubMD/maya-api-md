<?php

namespace App\Repository;

use App\Entity\Produit;
// use App\Entity\ProduitRecherche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;


/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // /**
    //  * @return Query
    //  */
    // public function findAllByCriteria(ProduitRecherche $produitRecherche): Query
    // {
    //     // le "p" est un alias utilisé dans la requête
    //     $qb = $this->createQueryBuilder('p')
    //         ->orderBy('p.libelle', 'ASC');

    //     if ($produitRecherche->getLibelle()) {
    //         $qb->andWhere('p.libelle LIKE :libelle')
    //             ->setParameter('libelle', $produitRecherche->getLibelle() . '%');
    //     }

    //     if ($produitRecherche->getPrixMini()) {
    //         $qb->andWhere('p.prix >= :prixMini')
    //             ->setParameter('prixMini', $produitRecherche->getPrixMini());
    //     }

    //     if ($produitRecherche->getPrixMaxi()) {
    //         $qb->andWhere('p.prix < :prixMaxi')
    //             ->setParameter('prixMaxi', $produitRecherche->getPrixMaxi());
    //     }
    //     return $qb->getQuery();
    // }

    /**
     * @return Query
     */
    public function findAllOrderByLibelle(): Query
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT p
           FROM App\Entity\Produit p
           ORDER BY p.libelle ASC'
        );

        return $query;
    }

}
