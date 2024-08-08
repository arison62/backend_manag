-- Table Utilisateur
CREATE TABLE Utilisateur (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  mot_de_passe TEXT NOT NULL,
  date_creation TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Table Client
CREATE TABLE Client (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  utilisateur_id INTEGER,
  nom TEXT NOT NULL,
  email TEXT,
  telephone TEXT,
  adresse TEXT,
  FOREIGN KEY(utilisateur_id) REFERENCES Utilisateur(id)
);

-- Table Fournisseur
CREATE TABLE Fournisseur (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  email TEXT,
  telephone TEXT,
  adresse TEXT
);

-- Table Produit
CREATE TABLE Produit (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  prix_unitaire REAL
);

-- Table Service
CREATE TABLE Service (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  description TEXT,
  tarif REAL
);

-- Table Facture
CREATE TABLE Facture (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  numero TEXT UNIQUE NOT NULL,
  date_emission TEXT NOT NULL,
  date_echeance TEXT,
  montant_total REAL,
  type TEXT CHECK(type IN ('client', 'fournisseur')),
  utilisateur_id INTEGER,
  FOREIGN KEY(utilisateur_id) REFERENCES Utilisateur(id)
);

-- Table Transaction

CREATE TABLE Transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  facture_id INTEGER,
  type TEXT CHECK(type IN ('depense', 'entree')),
  montant REAL,
  date_transaction TEXT,
  mode_paiement TEXT,
  reference_paiement TEXT,
  FOREIGN KEY(facture_id) REFERENCES Facture(id)
);



-- Table Facture_Client
CREATE TABLE Facture_Client (
  facture_id INTEGER,
  client_id INTEGER,
  PRIMARY KEY (facture_id, client_id),
  FOREIGN KEY (facture_id) REFERENCES Facture(id),
  FOREIGN KEY (client_id) REFERENCES Client(id)
);

-- Table Facture_Fournisseur
CREATE TABLE Facture_Fournisseur (
  facture_id INTEGER,
  fournisseur_id INTEGER,
  PRIMARY KEY (facture_id, fournisseur_id),
  FOREIGN KEY (facture_id) REFERENCES Facture(id),
  FOREIGN KEY (fournisseur_id) REFERENCES Fournisseur(id)
);

-- Table LigneFacture
CREATE TABLE LigneFacture (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  facture_id INTEGER,
  produit_id INTEGER,
  service_id INTEGER,
  quantite INTEGER,
  prix_unitaire REAL,
  montant REAL,
  FOREIGN KEY (facture_id) REFERENCES Facture(id),
  FOREIGN KEY (produit_id) REFERENCES Produit(id),
  FOREIGN KEY (service_id) REFERENCES Service(id)
);
