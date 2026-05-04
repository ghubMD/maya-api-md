<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406195351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE item_panier (id INT AUTO_INCREMENT NOT NULL, prix_initial NUMERIC(7, 2) DEFAULT NULL, prix NUMERIC(7, 2) NOT NULL, quantite NUMERIC(7, 2) NOT NULL, panier_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_A235783DF77D927C (panier_id), INDEX IDX_A235783DF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, montant_total NUMERIC(7, 2) NOT NULL, statut VARCHAR(1) NOT NULL, date_commande DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_24CC0DF2A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE item_panier ADD CONSTRAINT FK_A235783DF77D927C FOREIGN KEY (panier_id) REFERENCES panier (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_panier ADD CONSTRAINT FK_A235783DF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE panier ADD CONSTRAINT FK_24CC0DF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        // $this->addSql('ALTER TABLE recette_produit DROP FOREIGN KEY `FK_EDDD365D89312FE9`');
        // $this->addSql('ALTER TABLE recette_produit DROP FOREIGN KEY `FK_EDDD365DF347EFB`');
        // $this->addSql('DROP TABLE messenger_messages');
        // $this->addSql('DROP TABLE recette');
        // $this->addSql('DROP TABLE recette_produit');
        // $this->addSql('ALTER TABLE categorie CHANGE image_date_maj image_date_maj DATETIME DEFAULT NULL');
        // $this->addSql('ALTER TABLE produit CHANGE image_date_maj image_date_maj DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD rue VARCHAR(38) NOT NULL, ADD code_postal VARCHAR(5) NOT NULL, ADD ville VARCHAR(33) NOT NULL, ADD date_entree_relation DATE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, headers LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, queue_name VARCHAR(190) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E016BA31DB (delivered_at), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E0FB7336F0 (queue_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        // $this->addSql('CREATE TABLE recette (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        // $this->addSql('CREATE TABLE recette_produit (recette_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_EDDD365D89312FE9 (recette_id), INDEX IDX_EDDD365DF347EFB (produit_id), PRIMARY KEY (recette_id, produit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        // $this->addSql('ALTER TABLE recette_produit ADD CONSTRAINT `FK_EDDD365D89312FE9` FOREIGN KEY (recette_id) REFERENCES recette (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        // $this->addSql('ALTER TABLE recette_produit ADD CONSTRAINT `FK_EDDD365DF347EFB` FOREIGN KEY (produit_id) REFERENCES produit (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_panier DROP FOREIGN KEY FK_A235783DF77D927C');
        $this->addSql('ALTER TABLE item_panier DROP FOREIGN KEY FK_A235783DF347EFB');
        $this->addSql('ALTER TABLE panier DROP FOREIGN KEY FK_24CC0DF2A76ED395');
        $this->addSql('DROP TABLE item_panier');
        $this->addSql('DROP TABLE panier');
        // $this->addSql('ALTER TABLE categorie CHANGE image_date_maj image_date_maj DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        // $this->addSql('ALTER TABLE produit CHANGE image_date_maj image_date_maj DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user DROP rue, DROP code_postal, DROP ville, DROP date_entree_relation');
    }
}
