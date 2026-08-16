-- =========================================================
-- StoreManager Pro - Schéma PostgreSQL
-- =========================================================

-- =========================
-- Utilisateurs (Admin, Vente, Stock, Inventaire)
-- =========================
CREATE TABLE utilisateurs (
    id              SERIAL PRIMARY KEY,
    login           VARCHAR(50)  NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,
    role            VARCHAR(20)  NOT NULL,
    date_creation   TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_role CHECK (role IN ('ADMIN', 'VENTE', 'STOCK', 'INVENTAIRE'))
);

-- =========================
-- Produits
-- =========================
CREATE TABLE produits (
    id               SERIAL PRIMARY KEY,
    reference        VARCHAR(50)   NOT NULL UNIQUE,
    nom              VARCHAR(150)  NOT NULL,
    prix_achat       NUMERIC(12,2) NOT NULL,
    prix_vente       NUMERIC(12,2) NOT NULL,
    quantite_stock   INTEGER       NOT NULL DEFAULT 0,
    seuil_alerte     INTEGER       NOT NULL DEFAULT 0,
    CONSTRAINT chk_prix_achat_positif CHECK (prix_achat >= 0),
    CONSTRAINT chk_prix_vente_positif CHECK (prix_vente >= 0),
    CONSTRAINT chk_stock_positif CHECK (quantite_stock >= 0)
);

-- =========================
-- Clients
-- =========================
CREATE TABLE clients (
    id             SERIAL PRIMARY KEY,
    prenom         VARCHAR(100)  NOT NULL,
    nom            VARCHAR(100)  NOT NULL,
    telephone      VARCHAR(30),
    email          VARCHAR(150),
    limite_credit  NUMERIC(12,2) NOT NULL DEFAULT 0,
    CONSTRAINT chk_limite_credit_positive CHECK (limite_credit >= 0)
);

-- =========================
-- Fournisseurs
-- =========================
CREATE TABLE fournisseurs (
    id         SERIAL PRIMARY KEY,
    nom        VARCHAR(150) NOT NULL,
    telephone  VARCHAR(30)  NOT NULL,
    adresse    VARCHAR(200) NOT NULL,
    email      VARCHAR(150)
);

-- =========================
-- Commandes (Ventes)
-- =========================
CREATE TABLE commandes (
    id               SERIAL PRIMARY KEY,
    client_id        INTEGER       NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    utilisateur_id   INTEGER       NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    date_vente       TIMESTAMP     NOT NULL DEFAULT NOW(),
    type_reglement   VARCHAR(20)   NOT NULL,
    montant_total    NUMERIC(12,2) NOT NULL DEFAULT 0,
    statut           VARCHAR(20)   NOT NULL DEFAULT 'VALIDEE',
    CONSTRAINT chk_type_reglement CHECK (type_reglement IN ('ESPECES', 'CREDIT', 'MOBILE_MONEY')),
    CONSTRAINT chk_statut_commande CHECK (statut IN ('VALIDEE', 'ANNULEE'))
);

-- =========================
-- Lignes de commande
-- =========================
CREATE TABLE lignes_commande (
    id               SERIAL PRIMARY KEY,
    commande_id      INTEGER       NOT NULL REFERENCES commandes(id) ON DELETE CASCADE,
    produit_id       INTEGER       NOT NULL REFERENCES produits(id) ON DELETE RESTRICT,
    quantite         INTEGER       NOT NULL,
    prix_unitaire    NUMERIC(12,2) NOT NULL,
    CONSTRAINT chk_quantite_positive CHECK (quantite > 0),
    CONSTRAINT chk_prix_unitaire_positif CHECK (prix_unitaire >= 0)
);

-- =========================
-- Dettes
-- =========================
CREATE TABLE dettes (
    id               SERIAL PRIMARY KEY,
    commande_id      INTEGER       UNIQUE REFERENCES commandes(id) ON DELETE SET NULL,
    client_id        INTEGER       NOT NULL REFERENCES clients(id) ON DELETE RESTRICT,
    montant_initial  NUMERIC(12,2) NOT NULL,
    montant_restant  NUMERIC(12,2) NOT NULL,
    statut           VARCHAR(20)   NOT NULL DEFAULT 'EN_COURS',
    date_echeance    DATE,
    CONSTRAINT chk_montant_initial_positif CHECK (montant_initial >= 0),
    CONSTRAINT chk_montant_restant_positif CHECK (montant_restant >= 0),
    CONSTRAINT chk_statut_dette CHECK (statut IN ('EN_COURS', 'SOLDEE'))
);

-- =========================
-- Paiements
-- =========================
CREATE TABLE paiements (
    id               SERIAL PRIMARY KEY,
    dette_id         INTEGER       NOT NULL REFERENCES dettes(id) ON DELETE CASCADE,
    montant          NUMERIC(12,2) NOT NULL,
    date_paiement    TIMESTAMP     NOT NULL DEFAULT NOW(),
    mode_paiement    VARCHAR(20)   NOT NULL,
    CONSTRAINT chk_montant_paiement_positif CHECK (montant > 0),
    CONSTRAINT chk_mode_paiement CHECK (mode_paiement IN ('ESPECES', 'MOBILE_MONEY', 'VIREMENT'))
);
ALTER TABLE paiements
DROP CONSTRAINT chk_mode_paiement;

ALTER TABLE paiements
ADD CONSTRAINT chk_mode_paiement
CHECK (
    mode_paiement IN (
        'ESPECES',
        'MOBILE_MONEY',
        'VIREMENT',
        'WAVE'
    )
);
ALTER TABLE paiements
DROP CONSTRAINT chk_mode_paiement;

ALTER TABLE paiements
ADD CONSTRAINT chk_mode_paiement
CHECK (
    mode_paiement IN (
        'Orange Money',
        'Wave',
        'Especes',
        'Virement'
    )
);

-- =========================
-- Approvisionnements
-- =========================
CREATE TABLE approvisionnements (
    id               SERIAL PRIMARY KEY,
    fournisseur_id   INTEGER     NOT NULL REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    utilisateur_id   INTEGER     NOT NULL REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    date_reception   TIMESTAMP   NOT NULL DEFAULT NOW(),
    statut           VARCHAR(20) NOT NULL DEFAULT 'EN_ATTENTE',
    CONSTRAINT chk_statut_appro CHECK (statut IN ('EN_ATTENTE', 'RECEPTIONNE'))
);

-- =========================
-- Lignes d'approvisionnement
-- =========================
CREATE TABLE lignes_approvisionnement (
    id                     SERIAL PRIMARY KEY,
    approvisionnement_id   INTEGER       NOT NULL REFERENCES approvisionnements(id) ON DELETE CASCADE,
    produit_id             INTEGER       NOT NULL REFERENCES produits(id) ON DELETE RESTRICT,
    quantite               INTEGER       NOT NULL,
    prix_achat_unitaire    NUMERIC(12,2) NOT NULL,
    CONSTRAINT chk_quantite_appro_positive CHECK (quantite > 0),
    CONSTRAINT chk_prix_achat_appro_positif CHECK (prix_achat_unitaire >= 0)
);

-- =========================
-- Index utiles
-- =========================
CREATE INDEX idx_commandes_client ON commandes(client_id);
CREATE INDEX idx_lignes_commande_commande ON lignes_commande(commande_id);
CREATE INDEX idx_dettes_client ON dettes(client_id);
CREATE INDEX idx_paiements_dette ON paiements(dette_id);
CREATE INDEX idx_appro_fournisseur ON approvisionnements(fournisseur_id);