<?php

/**
 * Classe Database
 *
 * Pattern SINGLETON : garantit qu'une seule connexion PDO existe
 * pendant toute la durée de vie de l'application.
 *
 * Fallback automatique : essaie PostgreSQL en premier,
 * bascule sur SQLite (fichier erp.db) si PostgreSQL échoue.
 */
class Database
{

    private static ?Database $instance = null;
    private PDO $connexion;
    private string $driverActif;

    private function __construct()
    {
    
        $hostPg     = 'localhost';
        $portPg     = '5432';
        $dbNamePg   = 'storemanager';
        $userPg     = 'postgres';
        $passwordPg = 'postgres';

        $cheminSqlite = __DIR__ . '/../../erp.db';

        try {
            $dsn = "pgsql:host={$hostPg};port={$portPg};dbname={$dbNamePg}";

            $this->connexion = new PDO($dsn, $userPg, $passwordPg, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $this->driverActif = 'pgsql';
            echo "[Database] Connexion PostgreSQL réussie." . PHP_EOL;

        } catch (PDOException $e) {
            echo "[Database] PostgreSQL indisponible ({$e->getMessage()}). Bascule sur SQLite." . PHP_EOL;

            try {
                $this->connexion = new PDO('sqlite:' . $cheminSqlite, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                $this->connexion->exec('PRAGMA foreign_keys = ON;');

                $this->driverActif = 'sqlite';
                echo "[Database] Connexion SQLite réussie (fichier: {$cheminSqlite})." . PHP_EOL;

            } catch (PDOException $eSqlite) {
                die('[Database] Erreur critique : impossible de se connecter '
                    . 'ni à PostgreSQL ni à SQLite. ' . $eSqlite->getMessage());
            }
        }
    }

    
    public static function getInstance(): PDO
    {
        // Si aucune instance n'existe encore, on la crée UNE SEULE FOIS.
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connexion;
    }

   
    public static function getDriverActif(): string
    {
        if (self::$instance === null) {
            self::getInstance(); // force la connexion si pas encore faite
        }

        return self::$instance->driverActif;
    }
}