CREATE TABLE Utilisateur (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  mot_de_passe TEXT NOT NULL,
  date_creation TEXT DEFAULT CURRENT_TIMESTAMP
)


CREATE TABLE "Portefeuille" (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  utilisateur_id INTEGER,
  nom TEXT NOT NULL,
  description TEXT,
  solde REAL,
  FOREIGN KEY(utilisateur_id) REFERENCES Utilisateur(id)
)
CREATE TABLE Client (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  utilisateur_id INTEGER,
  nom TEXT NOT NULL,
  email TEXT,
  telephone TEXT,
  adresse TEXT,
  FOREIGN KEY(utilisateur_id) REFERENCES Utilisateur(id)
)
CREATE TABLE Fournisseur (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  email TEXT,
  telephone TEXT,
  adresse TEXT
)
CREATE TABLE Transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  facture_id INTEGER,
  type TEXT CHECK(type IN ('depense', 'entree')),
  montant REAL,
  date_transaction TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  portefeuille_id INTEGER,
  FOREIGN KEY(portefeuille_id) REFERENCES "Portefeuille"(id),
  FOREIGN KEY(facture_id) REFERENCES Facture(id) ON DELETE CASCADE
)
CREATE TABLE Facture_Client (
  facture_id INTEGER,
  client_id INTEGER,
  PRIMARY KEY (facture_id, client_id),
  FOREIGN KEY (facture_id) REFERENCES Facture(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES Client(id) ON DELETE CASCADE
)

CREATE TABLE Facture_Fournisseur (
  facture_id INTEGER,
  fournisseur_id INTEGER,
  PRIMARY KEY (facture_id, fournisseur_id),
  FOREIGN KEY (facture_id) REFERENCES Facture(id),
  FOREIGN KEY (fournisseur_id) REFERENCES Fournisseur(id)
)

CREATE TABLE Produit (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  prix_unitaire REAL
)
CREATE TABLE Service (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  nom TEXT NOT NULL,
  description TEXT,
  tarif REAL
)
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
)
CREATE TABLE Facture (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  numero TEXT,
  date_emission TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_echeance TEXT,
  montant_total REAL,
  status TEXT CHECK(status IN ('en_cours', 'payee', 'annulee')),
  type TEXT CHECK(type IN ('client', 'fournisseur')),
  utilisateur_id INTEGER,
  FOREIGN KEY(utilisateur_id) REFERENCES Utilisateur(id)
)
CREATE TRIGGER add_numero
AFTER INSERT
On Facture
WHEN new.numero IS NULL
BEGIN
	UPDATE Facture SET numero = ('INVOICE_' || id) WHERE numero IS NULL;
END
