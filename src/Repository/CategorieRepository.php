<?php

namespace App\Repository;

use App\Entity\Categorie;
use App\Entity\CategorieRecherche;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;


/**
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    /**
     * @return Query
     */
    public function findAllByCriteria(CategorieRecherche $categorieRecherche): Query
    {
        // le "p" est un alias utilisé dans la requête
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.libelle', 'ASC');

        if ($categorieRecherche->getLibelle()) {
            $qb->andWhere('p.libelle LIKE :libelle')
                ->setParameter('libelle', $categorieRecherche->getLibelle() . '%');
        }

        return $qb->getQuery();
        // $query = $qb->getQuery();
        // return $query->execute();
    }

        /**
     * @return Query
     */
    public function findAllOrderByLibelle(): Query
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT c
           FROM App\Entity\Categorie c
           ORDER BY c.libelle ASC'
        );

        return $query;
        //    // retourne un tableau d'objets de type Produit
        //    return $query->getResult();
    }

    //    /**
    //     * @return Categorie[] Returns an array of Categorie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Categorie
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
