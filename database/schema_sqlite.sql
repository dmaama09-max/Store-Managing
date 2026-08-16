-- =========================================================
-- StoreManager Pro - Schéma SQLite (fallback si PostgreSQL indisponible)
-- =========================================================

PRAGMA foreign_keys = ON;

-- =========================
-- Utilisateurs (Admin, Vente, Stock, Inventaire)
-- =========================
CREATE TABLE utilisateurs (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    login           TEXT    NOT NULL UNIQUE,
    mot_de_passe    TEXT    NOT NULL,
    role            TEXT    NOT NULL CHECK (role IN ('ADMIN', 'VENTE', 'STOCK', 'INVENTAIRE')),
    date_creation   TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- =========================
-- Produits
-- =========================
CREATE TABLE produits (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    reference        TEXT    NOT NULL UNIQUE,
    nom              TEXT    NOT NULL,
    prix_achat       REAL    NOT NULL CHECK (prix_achat >= 0),
    prix_vente       REAL    NOT NULL CHECK (prix_vente >= 0),
    quantite_stock   INTEGER NOT NULL DEFAULT 0 CHECK (quantite_stock >= 0),
    seuil_alerte     INTEGER NOT NULL DEFAULT 0
);

-- =========================
-- Clients
-- =========================
CREATE TABLE clients (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    prenom         TEXT    NOT NULL,
    nom            TEXT    NOT NULL,
    telephone      TEXT,
    email          TEXT,
    limite_credit  REAL    NOT NULL DEFAULT 0 CHECK (limite_credit >= 0)
);

-- =========================
-- Fournisseurs
-- =========================
CREATE TABLE fournisseurs (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    nom        TEXT NOT NULL,
    telephone  TEXT NOT NULL,
    adresse    TEXT NOT NULL,
    email      TEXT
);

-- =========================
-- Commandes (Ventes)
-- =========================
CREATE TABLE commandes (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id        INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    utilisateur_id   INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    date_vente       TEXT    NOT NULL DEFAULT (datetime('now')),
    type_reglement   TEXT    NOT NULL CHECK (type_reglement IN ('ESPECES', 'CREDIT', 'MOBILE_MONEY')),
    montant_total    REAL    NOT NULL DEFAULT 0,
    statut           TEXT    NOT NULL DEFAULT 'VALIDEE' CHECK (statut IN ('VALIDEE', 'ANNULEE'))
);

-- =========================
-- Lignes de commande
-- =========================
CREATE TABLE lignes_commande (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    commande_id      INTEGER NOT NULL REFERENCES commandes(id) ON DELETE CASCADE,
    produit_id       INTEGER NOT NULL REFERENCES produits(id) ON DELETE RESTRICT,
    quantite         INTEGER NOT NULL CHECK (quantite > 0),
    prix_unitaire    REAL    NOT NULL CHECK (prix_unitaire >= 0)
);

-- =========================
-- Dettes
-- =========================
CREATE TABLE dettes (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    commande_id      INTEGER UNIQUE REFERENCES commandes(id) ON DELETE SET NULL,
    client_id        INTEGER NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    montant_initial  REAL    NOT NULL CHECK (montant_initial >= 0),
    montant_restant  REAL    NOT NULL CHECK (montant_restant >= 0),
    statut           TEXT    NOT NULL DEFAULT 'EN_COURS' CHECK (statut IN ('EN_COURS', 'SOLDEE')),
    date_echeance    TEXT
);

-- =========================
-- Paiements
-- =========================
CREATE TABLE paiements (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    dette_id         INTEGER NOT NULL REFERENCES dettes(id) ON DELETE CASCADE,
    montant          REAL    NOT NULL CHECK (montant > 0),
    date_paiement    TEXT    NOT NULL DEFAULT (datetime('now')),
    mode_paiement    TEXT    NOT NULL CHECK (mode_paiement IN ('Orange Money', 'Wave', 'Especes', 'Virement'))
);

-- =========================
-- Approvisionnements
-- =========================
CREATE TABLE approvisionnements (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    fournisseur_id   INTEGER NOT NULL REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    utilisateur_id   INTEGER NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    date_reception   TEXT    NOT NULL DEFAULT (datetime('now')),
    statut           TEXT    NOT NULL DEFAULT 'EN_ATTENTE' CHECK (statut IN ('EN_ATTENTE', 'RECEPTIONNE'))
);

-- =========================
-- Lignes d'approvisionnement
-- =========================
CREATE TABLE lignes_approvisionnement (
    id                     INTEGER PRIMARY KEY AUTOINCREMENT,
    approvisionnement_id   INTEGER NOT NULL REFERENCES approvisionnements(id) ON DELETE CASCADE,
    produit_id             INTEGER NOT NULL REFERENCES produits(id) ON DELETE RESTRICT,
    quantite               INTEGER NOT NULL CHECK (quantite > 0),
    prix_achat_unitaire    REAL    NOT NULL CHECK (prix_achat_unitaire >= 0)
);

-- =========================
-- Index utiles
-- =========================
CREATE INDEX idx_commandes_client ON commandes(client_id);
CREATE INDEX idx_lignes_commande_commande ON lignes_commande(commande_id);
CREATE INDEX idx_dettes_client ON dettes(client_id);
CREATE INDEX idx_paiements_dette ON paiements(dette_id);
CREATE INDEX idx_appro_fournisseur ON approvisionnements(fournisseur_id);