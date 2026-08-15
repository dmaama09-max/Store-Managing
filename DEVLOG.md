# 📓 Journal de Développement (DEVLOG)
**Nom & Prénom** : [Ton Nom & Prénom]
**Projet** : StoreManager Pro (ERP PHP/POO)

---

## 1. Suivi Chronologique des Phases

### [Vendredi - Phase 1] : Conception & BDD Fallback

#### Step 1.1 : Conception UML (19h00 - 20h30)
- **Heure de réalisation** : 20h00 - 02h30
- **Ce qui a été fait** :
  - Réalisation du diagramme de cas d'utilisation, découpé en 5 sous-diagrammes fonctionnels (Dashboard, Ventes/POS, Gestion des Dettes, Approvisionnement, Produits et Tiers) pour rester lisible.
  - Identification des 4 profils utilisateurs (Admin, Vente, Stock, Inventaire) via une hiérarchie d'acteurs UML (généralisation), chacun héritant de la capacité "Se connecter".
  - Réalisation du diagramme de classes avec les entités : Produit, Client, Fournisseur, Commande, LigneCommande, Dette, Paiement, Approvisionnement, LigneApprovisionnement, Utilisateur, et l'énumération Role.
  - Réflexion sur les associations et cardinalités.
  - Réflexion sur comment on passe du diagramme UML au vrai code PHP.
  - Réflexion sur l'encapsulation.
- **Difficultés / Obstacles** :
  - Difficulté à adapter cette exigence du cahier des charges relatif au Diagramme de Cas d'Utilisation identifiant les 4 profils : Admin, Vente, Stock, Inventaire..
  - Hésitation sur le choix d'utiliser la généralisation d'acteurs.

#### Step 1.2 : Schéma SQL PostgreSQL / SQLite (20h30 - 22h00)
- **Heure de réalisation** :02h30 - 04h00
- **Ce qui a été fait** :
  Écriture de schema.sql (PostgreSQL) avec les 10 tables correspondant au diagramme de classes : utilisateurs, produits, clients, fournisseurs, commandes, lignes_commande, dettes, paiements, approvisionnements, lignes_approvisionnement.
Ajout de contraintes CHECK pour garantir la cohérence métier (ex: quantite_stock >= 0, montant_restant >= 0, role IN (...)).
Ajout de clés étrangères (FK) avec des règles ON DELETE adaptées (CASCADE pour les lignes détail, RESTRICT pour les entités de référence comme clients/produits).
Écriture de schema_sqlite.sql, version équivalente adaptée aux types SQLite (TEXT/REAL/INTEGER au lieu de VARCHAR/NUMERIC/SERIAL), avec activation de PRAGMA foreign_keys = ON.

- **Difficultés / Obstacles** :


#### Step 1.3 : Singleton Database & Fallback Automatique (22h00 - 23h00)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

---

### [Samedi - Phase 2] : POO, Repositories & Ventes POS

#### Step 2.1 : Entités POO Pure (09h00 - 11h00)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

#### Step 2.2 : Repositories & SQL Sécurisé (11h00 - 13h00)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

#### Step 2.3 : Service Métier Vente POS & Transaction SQL (14h00 - 17h00)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

#### Step 2.4 : Controller POS & Vue Caisse (17h00 - 20h00)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

---

### 🚀 [Dimanche - Phase 3] : Dettes, Approvisionnements & Rôles

#### Step 3.1 : Gestion des Dettes & Remboursements (09h00 - 11h30)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

#### Step 3.2 : Approvisionnements & Réception BL (11h30 - 13h30)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

#### Step 3.3 : AuthManager & Contrôle des Rôles (14h30 - 16h30)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

#### Step 3.4 : Rédaction de l'Autopsie & Push Final (16h30 - 18h00)
- **Heure de réalisation** :
- **Ce qui a été fait** :
- **Difficultés / Obstacles** :

---

## 2. Autopsie de 3 Méthodes Clés (Indispensable pour l'oral)

### Méthode 1 : `Database::getInstance()`
- **Fichier** : `src/Core/Database.php`
- **Rôle** :
- **Explication ligne par ligne** :

### Méthode 2 : `VenteService::validerVente()`
- **Fichier** : `src/Service/VenteService.php`
- **Rôle** :
- **Explication ligne par ligne** :

### Méthode 3 : `DetteService::enregistrerPaiement()` (ou `AuthManager::checkAccess()`)
- **Fichier** : `src/Service/...`
- **Rôle** :
- **Explication ligne par ligne** :