<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250329230515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE fecha_recordatorio (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, persona_id INTEGER NOT NULL, fecha DATE NOT NULL, descripcion CLOB DEFAULT NULL, es_recurrente BOOLEAN NOT NULL, CONSTRAINT FK_F87D7BA0F5F88DB9 FOREIGN KEY (persona_id) REFERENCES persona (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F87D7BA0F5F88DB9 ON fecha_recordatorio (persona_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE observacion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, persona_id INTEGER NOT NULL, fecha DATETIME NOT NULL, texto CLOB NOT NULL, CONSTRAINT FK_8B8B4C6F5F88DB9 FOREIGN KEY (persona_id) REFERENCES persona (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8B8B4C6F5F88DB9 ON observacion (persona_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE persona (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, apellido VARCHAR(255) NOT NULL, fecha_nacimiento DATE DEFAULT NULL, observaciones CLOB DEFAULT NULL, foto VARCHAR(255) DEFAULT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE relacion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, persona_origen_id INTEGER NOT NULL, persona_destino_id INTEGER NOT NULL, tipo_relacion_id INTEGER NOT NULL, CONSTRAINT FK_AF47286F92693CDF FOREIGN KEY (persona_origen_id) REFERENCES persona (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AF47286F178E4CFF FOREIGN KEY (persona_destino_id) REFERENCES persona (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AF47286FA50B1DE3 FOREIGN KEY (tipo_relacion_id) REFERENCES tipo_relacion (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AF47286F92693CDF ON relacion (persona_origen_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AF47286F178E4CFF ON relacion (persona_destino_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AF47286FA50B1DE3 ON relacion (tipo_relacion_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, descripcion CLOB DEFAULT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tipo_relacion (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, nombre_inverso VARCHAR(255) NOT NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE fecha_recordatorio
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE observacion
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE persona
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE relacion
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tag
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tipo_relacion
        SQL);
    }
}
