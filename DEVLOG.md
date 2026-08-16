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
  - Hésitation sur le choix d'utiliser la généralisation d'acteurs, sur qui devait avoir accès à quoi entre Vente et Stock.

#### Step 1.2 : Schéma SQL PostgreSQL / SQLite (20h30 - 22h00)
- **Heure de réalisation** :02h30 - 04h00
- **Ce qui a été fait** :
  Écriture de schema.sql (PostgreSQL) avec les 10 tables correspondant au diagramme de classes : utilisateurs, produits, clients, fournisseurs, commandes, lignes_commande, dettes, paiements, approvisionnements, lignes_approvisionnement.
Ajout de contraintes CHECK pour garantir la cohérence métier (ex: quantite_stock >= 0, montant_restant >= 0, role IN (...)).
Ajout de clés étrangères (FK) avec des règles ON DELETE adaptées (CASCADE pour les lignes détail, RESTRICT pour les entités de référence comme clients/produits).
Écriture de schema_sqlite.sql, version équivalente adaptée aux types SQLite (TEXT/REAL/INTEGER au lieu de VARCHAR/NUMERIC/SERIAL), avec activation de PRAGMA foreign_keys = ON.

- **Difficultés / Obstacles** :
- Confusion initiale sur le rôle de schema_sqlite.sql : je pensais que c'était un simple doublon de schema.sql, alors que c'est une traduction syntaxique du même schéma logique adaptée au moteur SQLite (SERIAL → INTEGER AUTOINCREMENT, VARCHAR → TEXT, NUMERIC → REAL, etc.).
- Doute sur l'installation de SQLite : je pensais qu'il fallait démarrer un service comme pour PostgreSQL. Vérification faite avec php -m | grep -i sqlite, qui a confirmé que pdo_sqlite et sqlite3 étaient déjà disponibles — rien à installer côté serveur, SQLite n'étant qu'un fichier lu directement par PHP.
- Compris qu'il fallait activer manuellement les clés étrangères sous SQLite via PRAGMA foreign_keys = ON;, contrairement à PostgreSQL où elles sont actives par défaut.


#### Step 1.3 : Singleton Database & Fallback Automatique (22h00 - 23h00)
- **Heure de réalisation** : 04h00 - 05h00
- **Ce qui a été fait** :
- Création de src/Core/Database.php implémentant le pattern Singleton : constructeur private, propriété statique $instance, et méthode statique getInstance() qui renvoie toujours la même connexion PDO.
- Implémentation du fallback try/catch : tentative de connexion PostgreSQL en premier, bascule automatique sur SQLite (fichier erp.db) si la connexion échoue, sans faire planter l'application.
- Décision de ne pas utiliser les namespaces PHP ni l'autoload Composer pour ce projet (trop tôt dans mon apprentissage POO), donc adaptation du code pour fonctionner avec un simple require_once classique.
- **Difficultés / Obstacles** :
- Compréhension du principe du Singleton : pourquoi le constructeur doit être private et à quoi sert la méthode statique getInstance() pour garantir une seule instance de connexion dans toute l'application.
- Choix conscient de na pas utiliser namespace App\Core; : n'ayant pas encore mis en place l'autoload Composer, j'ai préféré une version simplifiée avec require_once pour rester maîtrisable en autonomie, quitte à devoir répéter le require dans chaque fichier qui utilise Database.
- Compris qu'il fallait activer PRAGMA foreign_keys = ON; directement dans le code PHP au moment de la connexion SQLite, et pas seulement dans le script schema_sqlite.sql.

---

### [Samedi - Phase 2] : POO, Repositories & Ventes POS

#### Step 2.1 : Entités POO Pure (09h00 - 11h00)
- **Heure de réalisation** : 09h00 - 11h00 (avec débordement pour les ajustements liés au HTML/CSS)
- **Ce qui a été fait** :
- Création des 9 entités du diagramme de classes dans src/Model/Entity/ : Produit, Client, Fournisseur, Utilisateur, Commande, LigneCommande, Dette, Paiement, Approvisionnement, LigneApprovisionnement.
- Toutes les entités respectent l'encapsulation stricte : attributs private, accès uniquement via getters et méthodes métier (decrementerStock(), peutAcheterACredit(), enregistrerPaiement(), etc.).
- Remplacement des constantes de classe pour le rôle utilisateur par un vrai enum PHP (Role.php dans src/Model/Enum/), pour rester fidèle au diagramme de classes UML qui spécifiait enum Role. Le typage Role (au lieu de string) empêche à la compilation d'assigner une valeur de rôle invalide.
- Confrontation des entités au fichier HTML/CSS de la maquette (storemanager_pro_app.html) déjà réalisé : plusieurs écarts détectés entre les champs des formulaires et les entités (ex: Client avait un seul champ nom alors que le formulaire sépare prenom/nom et ajoute un email; Fournisseur avait un champ générique contact alors que le formulaire distingue telephone/adresse/email).
- Décision de ne pas modifier le HTML (déjà finalisé) et d'adapter les entités PHP pour coller exactement aux champs du formulaire, plutôt que l'inverse.
- Pour Produit, le formulaire ne demande que nom, prix_unitaire et quantite_stock (pas de reference, prix_achat, seuil_alerte). Adaptation : reference générée automatiquement via preg_replace/substr/strtoupper sur le nom + un code aléatoire, prixAchat initialisé à 0 et recalculé en moyenne pondérée dans incrementerStock() lors d'une future réception d'approvisionnement, seuilAlerte avec une valeur par défaut.
- Mise à jour de schema.sql et schema_sqlite.sql en conséquence (tables clients et fournisseurs), sans risque car les scripts n'avaient pas encore été exécutés en base.
- **Difficultés / Obstacles** :
- Différence entre constantes de classe et enum PHP mal comprise au départ : compris que l'enum apporte une vérification à la compilation (impossible d'assigner une valeur invalide), alors que les constantes ne sont vérifiées qu'à l'exécution via un in_array() manuel.
- Question sur la cohérence entre le diagramme de classes UML (qui prévoyait un enum Role) et le code initial (constantes) : correction nécessaire pour que le code soit fidèle à la conception, un point qui peut être demandé à l'oral.
- Écart découvert tardivement entre les formulaires HTML déjà conçus et les entités PHP en cours de développement (Client, Fournisseur, Produit) : nécessité d'arbitrer entre modifier le HTML ou adapter le PHP. Choix fait de préserver le HTML et d'enrichir la logique PHP (génération automatique de référence, moyenne pondérée du prix d'achat) pour rester cohérent sans tout reprendre.
- Blocage temporaire à l'exécution de schema_sqlite.sql dans VS Code (erreur de syntaxe), résolu en vérifiant l'outil utilisé et le fichier exact exécuté.
- Compréhension de la syntaxe imbriquée strtoupper(substr(preg_replace(...))) pour générer un préfixe de référence à partir du nom d'un produit (fonctions PHP évaluées de l'intérieur vers l'extérieur).

#### Step 2.2 : Repositories & SQL Sécurisé (11h00 - 13h00)
- **Heure de réalisation** :11h00 - 13h00
- **Ce qui a été fait** :
- Création de ProduitRepository.php, ClientRepository.php, FournisseurRepository.php dans src/Repository/, chacun avec les méthodes create(), findById(), findAll(), update(), delete().
- Toutes les requêtes SQL utilisent des requêtes préparées PDO (prepare() + execute([...]) avec des paramètres nommés :xxx) pour se protéger des injections SQL, conformément à la charte du projet.
- Mise en place du principe d'hydratation : une méthode privée hydrater() dans chaque Repository transforme une ligne SQL brute (tableau associatif) en véritable objet (Produit, Client, Fournisseur), en passant par le constructeur avec des arguments nommés.
- Ajout de méthodes spécifiques au métier : findEnRupture() dans ProduitRepository (produits sous le seuil d'alerte) et findDebiteurs() dans ClientRepository (clients ayant un encours de dette actif, calculé via une jointure SQL avec la table dettes plutôt que stocké directement dans clients).
- **Difficultés / Obstacles** :
- Compréhension du rôle exact de la méthode hydrater() : bien saisir que c'est la seule fonction du Repository qui connaît à la fois les noms de colonnes SQL et la structure du constructeur PHP, ce qui centralise les changements en un seul endroit si le schéma évolue.
- Découverte de la syntaxe des "named arguments" de PHP 8 (nom: $ligne['nom']) pour éviter les erreurs d'ordre dans les constructeurs à plusieurs paramètres.
- La requête de findDebiteurs() utilise une jointure (INNER JOIN) combinée à GROUP BY et HAVING, plus avancée que les requêtes simples déjà maîtrisées (SELECT ... WHERE) — notion à approfondir avant l'oral pour être capable de l'expliquer ligne par ligne si le formateur la choisit au hasard.
- Attention particulière portée à la BDD qui doit être fonctionnelle (SQLite/PostgreSQL) avant de pouvoir tester réellement create()/findAll(), contrairement aux entités du Step 2.1 qui pouvaient être testées sans base de données.

#### Step 2.3 : Service Métier Vente POS & Transaction SQL (14h00 - 17h00)
- **Heure de réalisation** : 14h00 - 17h00
- **Ce qui a été fait** :
- Création de src/Service/VenteService.php, qui centralise toute la logique métier d'une vente : construction du panier, vérification du stock disponible ligne par ligne, vérification de la limite de crédit du client si le règlement est à crédit, décrémentation du stock, création de la Dette associée si besoin.
- Implémentation de la méthode validerVente() sous une transaction SQL complète (beginTransaction() / commit() / rollBack()) : soit toutes les écritures (commande, lignes, décrémentation stock, dette) sont validées ensemble, soit aucune ne l'est en cas d'erreur en cours de route.
- Le prix unitaire de chaque LigneCommande est figé au moment de la vente (copié depuis Produit::getPrixVente()), pour ne pas être affecté si le prix du produit change plus tard.
- Choix assumé de garder les méthodes de persistance (creerCommande(), creerLigneCommande(), creerDette()) en méthodes privées directement dans VenteService, plutôt que de créer déjà CommandeRepository/DetteRepository, car ces Repository sont prévus dimanche (Step 3.1). Ce SQL sera extrait proprement à ce moment-là.
- **Difficultés / Obstacles** :

#### Step 2.4 : Controller POS & Vue Caisse (17h00 - 20h00)
- **Heure de réalisation** :17h00 - 20h00
- **Ce qui a été fait** :
- Création de src/Controller/POSController.php : gère l'affichage de la caisse (GET) et le traitement du formulaire de vente (POST), sans contenir lui-même de règle métier ni de SQL — il délègue tout à VenteService et aux Repository.
- Création de views/pos/index.php, adaptée directement du HTML/CSS déjà réalisé (storemanager_pro_app.html, section #view-pos) : les <select> client et produit sont désormais générés dynamiquement via des boucles PHP sur $clients et $produits, plutôt que codés en dur.
- Revue complète de VenteService::validerVente() après relecture attentive du formulaire HTML existant : découverte que le design gère un cas plus fin que prévu — un montant "versé" (avance) comparé au total de la vente, avec le reste qui devient automatiquement une Dette (badges "CRÉDIT TOTAL" si rien n'est versé, "AVANCE (Credit)" si versement partiel). Adaptation de la logique pour comparer montant_verse au total, déterminer le type_reglement en conséquence, et appliquer immédiatement l'avance sur la Dette nouvellement créée via Dette::enregistrerPaiement() (méthode déjà codée au Step 2.1, réutilisée telle quelle).
- Adaptation du JavaScript du panier (addToCart(), render()) pour générer des champs cachés produit_id[] et quantite[] à chaque ajout d'article, afin que $_POST transmette le panier à POSController — aucune modification des champs visibles du formulaire, uniquement le mécanisme interne d'envoi des données.
- Ajout d'une méthode listerCommandesAvecLignes() dans le controller (requêtes SQL directes avec jointures) pour alimenter le "Registre Général des Ventes" avec les vraies données de la base.
- **Difficultés / Obstacles** :
- Écart découvert tardivement entre la logique métier initialement codée (simple choix ESPECES vs CREDIT) et le vrai comportement attendu par le formulaire HTML (montant versé partiel avec calcul automatique du reste à devoir) — nécessité de revoir VenteService en profondeur après la relecture du HTML.
- Réflexion sur le fait que seul le montant RESTANT (et non le total de la vente) doit être comparé à la limite de crédit du client, puisque la partie déjà versée ne constitue pas une dette.
- Compréhension du fonctionnement de require pour partager les variables entre POSController et la vue, sans passer explicitement chaque variable.
- Décision consciente de garder listerCommandesAvecLignes() avec du SQL direct dans le controller plutôt qu'un CommandeRepository dédié, ce dernier n'étant pas prévu au planning avant dimanche (voire pas du tout prévu explicitement) — point à assumer à l'oral comme une simplification liée au temps disponible.  
- Gestion encore provisoire de l'utilisateur connecté ($_SESSION['utilisateur_id'] en dur) en attendant AuthManager, prévu dimanche (Step 3.3).

---

###  [Dimanche - Phase 3] : Dettes, Approvisionnements & Rôles

#### Step 3.1 : Gestion des Dettes & Remboursements (09h00 - 11h30)
- **Heure de réalisation** :09h00 - 11h30
- **Ce qui a été fait** :
- Création de DetteRepository.php et PaiementRepository.php dans src/Repository/, avec requêtes préparées PDO (create, findById, findActives, findByClient, update, findByDette, sommeTotale).
- Création de src/Service/DebtService.php : la méthode rembourserDette() orchestre sous une seule transaction SQL la mise à jour de la Dette (montant_restant/statut), l'enregistrement du Paiement, et la diminution de l'encours du Client — les trois doivent réussir ensemble ou échouer ensemble.
- Refactorisation de VenteService.php pour utiliser le nouveau DetteRepository au lieu du SQL temporaire écrit hier (Step 2.3), comme prévu et documenté dans le DEVLOG de la veille.
- Révision de Paiement.php : les modes de paiement (Orange Money, Wave, Especes, Virement) ont été alignés EXACTEMENT sur les valeurs du formulaire HTML de remboursement, plutôt que d'utiliser des constantes génériques (MOBILE_MONEY) qui ne correspondaient pas au design déjà réalisé.
- Création de DetteController.php (non explicitement prévu dans le planning, mais nécessaire pour orchestrer l'affichage et le traitement du formulaire, sur le même principe que POSController) et de views/dettes/index.php, reprenant fidèlement la structure du registre des dettes actives, avec les tiroirs (drawers) Paiements / Articles / Remboursement.
- Correction de la contrainte CHECK PostgreSQL sur paiements.mode_paiement via ALTER TABLE ... DROP/ADD CONSTRAINT, pour que les valeurs acceptées en base correspondent exactement (casse comprise) à ce que le formulaire envoie.
- **Difficultés / Obstacles** :
- Erreur de casse dans la première tentative de correction de la contrainte CHECK ('ESPECES', 'VIREMENT', 'WAVE' en majuscules) alors que le formulaire HTML envoie 'Especes', 'Virement', 'Wave' — rappel que SQL est sensible à la casse par défaut sur les comparaisons de chaînes, et qu'une contrainte mal alignée rejette silencieusement les insertions valides côté PHP.
- Constat que MOBILE_MONEY (valeur générique initialement prévue) ne correspondait à aucune valeur réelle du formulaire, qui distingue Orange Money et Wave séparément — nécessité de corriger à la fois l'entité Paiement et la contrainte SQL en cohérence.
- Découverte que ALTER TABLE ... DROP CONSTRAINT est une syntaxe PostgreSQL non disponible telle quelle sous SQLite, confirmant que la connexion active est bien PostgreSQL et non le fallback.
- Confusion passagère entre les deux vues views/pos/index.php (caisse) et views/dettes/index.php (registre des dettes) lors de la comparaison de fichiers de longueurs différentes — clarification nécessaire pour ne pas écraser l'une par l'autre, les deux fichiers étant indépendants et à conserver chacun à leur emplacement.
- Compréhension du mécanisme d'hydratation "rejouée" dans DetteRepository : lors de la relecture d'une dette déjà partiellement remboursée depuis la base, le Repository appelle enregistrerPaiement() avec le montant déjà versé (calculé par différence) plutôt que de forcer directement montantRestant, afin de réutiliser la même règle métier que pour un nouveau remboursement et de respecter l'encapsulation de l'entité.

#### Step 3.2 : Approvisionnements & Réception BL (11h30 - 13h30)
- **Heure de réalisation** :11h30 - 13h30
- **Ce qui a été fait** :
- Création de ApprovisionnementRepository.php : gestion des bons de livraison et de leurs lignes (create, createLigne, findById, findLignesBrutes, findEnAttenteAvecDetails, updateStatut, updateQuantiteLigne).
- Création de src/Service/SupplyService.php avec deux méthodes principales : creerBonDeLivraison() (nouveau BL au statut EN_ATTENTE, sans toucher au stock) et receptionnerBonDeLivraison() (le cœur du step), qui incrémente réellement le stock de chaque produit concerné et fait passer le BL à RECEPTIONNE, le tout sous transaction SQL.
- Réutilisation directe de Produit::incrementerStock($qte, $prixAchatUnitaire), codée dès le Step 2.1, pour recalculer automatiquement le prix d'achat moyen pondéré du produit à chaque réception — la logique métier anticipée dès la conception des entités trouve enfin son utilité concrète.
- Gestion du cas d'une livraison partielle : la quantité réellement reçue (saisie dans le formulaire) peut différer de la quantité initialement commandée ; dans ce cas, la ligne d'approvisionnement est mise à jour en base pour refléter la réalité.
- Création de SupplyController.php et views/supplies/index.php, avec le registre des BL en attente (fidèle au HTML existant) et un formulaire de création rapide de BL, nécessaire pour pouvoir tester la réception (non explicitement prévu dans le planning initial, qui ne listait que la réception).
- **Difficultés / Obstacles** :
- Constat que le planning ne prévoyait que la réception des BL, sans mécanisme de création — nécessité d'ajouter creerBonDeLivraison() pour pouvoir disposer de données de test réalistes, en s'appuyant sur un formulaire déjà présent dans le HTML (quick_supply_product, initialement prévu sur la vue Dashboard) plutôt que d'en inventer un nouveau.
- Réflexion sur la reconstruction de l'entité Approvisionnement lors de la réception : les lignes lues en base (tableaux associatifs bruts) doivent être retransformées en véritables objets LigneApprovisionnement et ajoutées via ajouterLigne(), afin de pouvoir appeler la méthode métier receptionner() de l'entité (qui vérifie qu'il y a au moins une ligne) plutôt que de modifier le statut directement.
- Gestion du cas d'une quantité reçue différente de la quantité commandée (livraison partielle), qui nécessite une mise à jour de la ligne en base en plus de l'incrémentation du stock — un cas non trivial à anticiper avant de relire attentivement le formulaire HTML de réception.

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