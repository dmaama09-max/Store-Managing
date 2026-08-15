<?php

/**
 * Enum Role
 *
 * Un "enum" (énumération) définit un ensemble FERMÉ de valeurs possibles.
 * Contrairement à une simple chaîne de caractères, PHP garantit qu'une
 * variable de type Role ne peut JAMAIS contenir autre chose que l'un
 * des 4 "case" définis ci-dessous. Impossible d'écrire Role::ADMINN
 * par erreur : ça ne compilera même pas.
 *
 * "enum Role: string" = un enum "backed" par des chaînes de caractères,
 * ce qui permet de faire correspondre chaque cas à une valeur stockée
 * en base de données (colonne "role" en VARCHAR/TEXT).
 */
enum Role: string
{
    case ADMIN      = 'ADMIN';
    case VENTE      = 'VENTE';
    case STOCK      = 'STOCK';
    case INVENTAIRE = 'INVENTAIRE';

    /**
     * Libellé lisible pour l'affichage (ex: dans l'interface utilisateur),
     * pratique pour ne pas afficher "ADMIN" en majuscules brutes à l'écran.
     */
    public function libelle(): string
    {
        return match ($this) {
            self::ADMIN      => 'Administrateur',
            self::VENTE      => 'Vente',
            self::STOCK      => 'Stock',
            self::INVENTAIRE => 'Inventaire',
        };
    }

    /**
     * Convertit une chaîne venant de la base de données (ex: "ADMIN")
     * en véritable objet Role. Lève une erreur automatiquement si la
     * valeur ne correspond à aucun des 4 cas (grâce à from() natif de PHP).
     *
     * Exemple d'utilisation dans UtilisateurRepository (Step 2.2) :
     *   $role = Role::from($ligneSql['role']);
     */
    public static function depuisChaine(string $valeur): self
    {
        return self::from($valeur);
    }
}