--
-- Database: database
--
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


-- Drop Tabelle (ordine inverso delle dipendenze)
DROP TABLE IF EXISTS User_has_ugroup;
DROP TABLE IF EXISTS Ugroup_has_service;
DROP TABLE IF EXISTS Oggetto_Ordine;
DROP TABLE IF EXISTS Ordine;
DROP TABLE IF EXISTS Carrello;
DROP TABLE IF EXISTS Recensione;
DROP TABLE IF EXISTS Messaggio_Assistenza;
DROP TABLE IF EXISTS Immagine_Prodotto;
DROP TABLE IF EXISTS Magazzino;
DROP TABLE IF EXISTS Preferiti;
DROP TABLE IF EXISTS Indirizzo_Spedizione;
DROP TABLE IF EXISTS Prodotto;
DROP TABLE IF EXISTS Ugroup;
DROP TABLE IF EXISTS Service;
DROP TABLE IF EXISTS Categoria;
DROP TABLE IF EXISTS Marca;
DROP TABLE IF EXISTS Promozione;
DROP TABLE IF EXISTS Corriere;
DROP TABLE IF EXISTS Coupon;
DROP TABLE IF EXISTS Metodo_Pagamento;
DROP TABLE IF EXISTS Utente;

-- --------------------------------  CREAZIONE TABELLE ----------------------------------

CREATE TABLE IF NOT EXISTS Utente (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS Categoria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_categoria VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS Marca (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_marca VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS Promozione (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_promozione VARCHAR(100) NOT NULL,
    descrizione VARCHAR(500),
    sconto_percentuale DECIMAL(5, 2) NOT NULL,
    data_inizio DATE NOT NULL,
    data_fine DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS Prodotto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_prodotto VARCHAR(100) NOT NULL,
    descrizione VARCHAR(500),
    prezzo DECIMAL(10, 2) NOT NULL,
    genere ENUM('uomo', 'donna', 'bambino') DEFAULT 'uomo',
    id_categoria INT NOT NULL,
    id_marca INT,
    id_promozione INT,
    FOREIGN KEY (id_categoria) REFERENCES Categoria(id),
    FOREIGN KEY (id_marca) REFERENCES Marca(id),
    FOREIGN KEY (id_promozione) REFERENCES Promozione(id)
);

CREATE TABLE IF NOT EXISTS Corriere (
    id INT PRIMARY KEY AUTO_INCREMENT,
    prezzo DECIMAL(10, 2) NOT NULL,
    azienda VARCHAR(50) NOT NULL,
    giorni_consegna INT NOT NULL
);

CREATE TABLE IF NOT EXISTS Coupon (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codice_coupon VARCHAR(50) NOT NULL,
    sconto_percentuale DECIMAL(5, 2) NOT NULL
);

CREATE TABLE IF NOT EXISTS Metodo_Pagamento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_pagamento VARCHAR(100) UNIQUE
);

CREATE TABLE IF NOT EXISTS Indirizzo_Spedizione (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    indirizzo VARCHAR(200) NOT NULL,
    citta VARCHAR(100) NOT NULL,
    regione VARCHAR(100) NOT NULL,
    provincia VARCHAR(100) NOT NULL,
    CAP VARCHAR(10) NOT NULL,

    UNIQUE (id_utente, indirizzo, citta, regione, provincia),
    FOREIGN KEY (id_utente) REFERENCES Utente(id)
);

CREATE TABLE IF NOT EXISTS Ordine (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    id_corriere INT NOT NULL,
    id_coupon INT,
    id_metodo_pagamento INT NOT NULL,
    id_indirizzo_spedizione INT NOT NULL,
    data_ordine DATE NOT NULL,
    data_spedizione DATE,
    prezzo_ordine DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES Utente(id),
    FOREIGN KEY (id_corriere) REFERENCES Corriere(id),
    FOREIGN KEY (id_coupon) REFERENCES Coupon(id),
    FOREIGN KEY (id_metodo_pagamento) REFERENCES Metodo_Pagamento(id),
    FOREIGN KEY (id_indirizzo_spedizione) REFERENCES Indirizzo_Spedizione(id)
);

CREATE TABLE IF NOT EXISTS Oggetto_Ordine (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_ordine INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita_prodotto INT NOT NULL,
    FOREIGN KEY (id_ordine) REFERENCES Ordine(id),
    FOREIGN KEY (id_prodotto) REFERENCES Prodotto(id)
);

CREATE TABLE IF NOT EXISTS Carrello (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    id_prodotto INT NOT NULL,
    quantita_prodotto INT NOT NULL,
    taglia_prodotto ENUM('S', 'M', 'L', 'XL') DEFAULT 'M',
    FOREIGN KEY (id_utente) REFERENCES Utente(id),
    FOREIGN KEY (id_prodotto) REFERENCES Prodotto(id)
);

CREATE TABLE IF NOT EXISTS Recensione (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    id_prodotto INT NOT NULL,
    testo_recensione TEXT(500),
    valutazione INT NOT NULL,
    data_recensione DATE NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES Utente(id),
    FOREIGN KEY (id_prodotto) REFERENCES Prodotto(id)
);

CREATE TABLE IF NOT EXISTS Messaggio_Assistenza (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    richiesta TEXT(500),
    risposta TEXT(500) DEFAULT NULL,
    data_richiesta DATE NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES Utente(id)
);

CREATE TABLE IF NOT EXISTS Immagine_Prodotto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_prodotto INT NOT NULL, 
    url_immagine VARCHAR(300) NOT NULL,
    FOREIGN KEY (id_prodotto) REFERENCES Prodotto(id)
);

CREATE TABLE IF NOT EXISTS Magazzino (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_prodotto INT NOT NULL, 
    quantita INT NOT NULL DEFAULT 0,
    taglia ENUM('S', 'M', 'L', 'XL') DEFAULT 'M',
    FOREIGN KEY (id_prodotto) REFERENCES Prodotto(id),
    UNIQUE (id_prodotto, taglia)
);

CREATE TABLE IF NOT EXISTS Service (
    id INT PRIMARY KEY AUTO_INCREMENT,
    script VARCHAR(100) DEFAULT NULL,
    description TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS Ugroup (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS Ugroup_has_service (
    ugroup_id INT DEFAULT NULL,
    service_id INT DEFAULT NULL,
    FOREIGN KEY (ugroup_id) REFERENCES ugroup(id) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (service_id) REFERENCES service(id) 
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS User_has_ugroup (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    id_ugroup INT NOT NULL,
    FOREIGN KEY (id_utente) REFERENCES utente(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_ugroup) REFERENCES ugroup(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS Preferiti (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_utente INT NOT NULL,
    id_prodotto INT NOT NULL,
    data_aggiunta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (id_utente, id_prodotto),
    FOREIGN KEY (id_utente) REFERENCES Utente(id) ON DELETE CASCADE,
    FOREIGN KEY (id_prodotto) REFERENCES Prodotto(id) ON DELETE CASCADE
);



-- --------------------------------  INSERIMENTO DATI ----------------------------------

-- Inserimento di valori nella tabella Utente
INSERT INTO Utente (nome, cognome, email, password)
VALUES
('Mario', 'Rossi', 'mario.rossi@example.com', 'password123'),
('Giulia', 'Bianchi', 'giulia.bianchi@example.com', 'pass1234'),
('Luca', 'Verdi', 'luca.verdi@example.com', 'securepass'),
('Martina', 'Gialli', 'martina.gialli@example.com', '123456'),
('Simone', 'Neri', 'simone.neri@example.com', 'password456'),
('Alessandro', 'Ferrari', 'alessandro.ferrari@example.com', 'alex2023'),
('Francesca', 'Romano', 'francesca.romano@example.com', 'fran_pass'),
('Marco', 'Esposito', 'marco.esposito@example.com', 'marco1985'),
('Chiara', 'Russo', 'chiara.russo@example.com', 'chiara_pwd'),
('Roberto', 'Colombo', 'roberto.colombo@example.com', 'rob_col123'),
('Valentina', 'Ricci', 'valentina.ricci@example.com', 'val_2023'),
('Andrea', 'Marino', 'andrea.marino@example.com', 'andrea_pwd'),
('Laura', 'Greco', 'laura.greco@example.com', 'laura_secure'),
('Davide', 'Conti', 'davide.conti@example.com', 'dav_con2023'),
('Sofia', 'Mancini', 'sofia.mancini@example.com', 'sofia_pass');


-- Inserimento di valori nella tabella Categoria
INSERT INTO Categoria (nome_categoria)
VALUES
('t-shirt'),
('felpe'),
('pantaloni'),
('maglioni'),
('gonne'),
('camice'),
('giacche'),
('scarpe'),
('accessori'),
('shorts'),
('abiti'),
('cappelli'),
('calze'),
('intimo'),
('costumi'),
('pantaloni_tuta');

-- Inserimento di valori nella tabella Marca
INSERT INTO Marca (nome_marca)
VALUES
('Nike'),
('Adidas'),
('Gucci'),
('Element'),
('Levis'),
('H&H'),
('Puma'),
('Stradivarius'),
('Zara'),
('Bershka'),
('Pull&Bear'),
('Calvin Klein'),
('Tommy Hilfiger'),
('Converse'),
('Vans'),
('Reebok');


-- Inserimento di valori nella tabella Promozione
INSERT INTO Promozione (nome_promozione, descrizione, sconto_percentuale, data_inizio, data_fine)
VALUES
('Sconto Estivo', 'Sconto del 20% su tutti gli articoli estivi', 20.00, '2026-06-01', '2026-08-31'),
('Promo Invernale', 'Sconto del 30% su abbigliamento invernale', 30.00, '2025-12-01', '2026-02-28'),
('Offerta Limitata', 'Sconto del 15% su una selezione di prodotti', 15.00, '2026-01-10', '2026-01-15'),
('Promo Giugno', 'Sconto del 25% su tutti gli articoli', 25.00, '2026-06-01', '2026-06-30'),
('Promo Nuovi Arrivi', 'Sconto del 10% su nuovi arrivi', 10.00, '2026-03-01', '2026-04-30'),
('Sconto Fedeltà ', 'Sconto del 5% per i clienti abituali', 5.00, '2025-11-01', '2026-12-31'),
('Sconto Compleanno', 'Sconto del 50% per il mese di compleanno', 50.00, '2026-01-01', '2026-12-31'),
('Sconto Black Friday', 'Sconto del 70% su tutti gli articoli', 70.00, '2026-11-24', '2026-11-30'),
('Sconto Natale', 'Sconto del 40% su articoli natalizi', 40.00, '2026-12-01', '2026-12-31'),
('Sconto Primavera', 'Sconto del 20% su articoli primaverili', 20.00, '2026-03-01', '2026-05-31');

-- Inserimento di valori nella tabella Prodotto
INSERT INTO Prodotto (nome_prodotto, descrizione, prezzo, genere, id_categoria, id_marca, id_promozione)
VALUES
('T-shirt Classica', 'T-shirt nera in cotone organico con logo minimalista', 29.99, 'uomo', 1, 4, NULL),
('T-shirt Premium', 'T-shirt grigia in tessuto traspirante ad asciugatura rapida', 39.99, 'uomo', 1, 1, 1),
('T-shirt Vintage', 'T-shirt con stampa retrò e dettagli invecchiati', 24.99, 'uomo', 1, 15, NULL),
('Felpa con Cappuccio', 'Felpa sportiva con tasche frontali e cappuccio regolabile', 59.99, 'uomo', 2, 2, 2),
('Felpa Tecnica', 'Felpa termica con interno in pile e cerniera a tutta lunghezza', 79.99, 'uomo', 2, 7, NULL),
('Jeans Slim Fit', 'Jeans elasticizzati a vita media con finitura stone-washed', 89.99, 'uomo', 3, 5, 3),
('Pantaloni Chino', 'Pantaloni eleganti in cotone con taglio dritto', 69.99, 'uomo', 3, 9, NULL),
('Camicia Oxford', 'Camicia formale in tessuto Oxford con bottoni sulla collana', 49.99, 'uomo', 6, 13, NULL),
('Maglione Lana', 'Maglione in lana merino con collo rotondo e trama intrecciata', 89.99, 'uomo', 4, 11, 5),
('Giacca Bomber', 'Giacca bomber impermeabile con polsini elasticizzati', 119.99, 'uomo', 7, 10, NULL),
('Sneakers Casual', 'Sneakers in tela con suola in gomma anti-scivolo', 69.99, 'uomo', 8, 14, NULL),
('Shorts Sportivi', 'Shorts leggeri con tasche laterali e coulisse in vita', 34.99, 'uomo', 10, 16, 4),
('Cappello Bucket', 'Cappello bucket reversibile in cotone', 24.99, 'uomo', 12, 4, NULL),
('Abito Estivo', 'Abito leggero a maniche corte con stampa floreale', 79.99, 'donna', 11, 8, 1),
('Gonna Plissettata', 'Gonna midi plissettata con elastico in vita', 49.99, 'donna', 5, 9, NULL),
('Camicia Seta', 'Camicia elegante in seta con bottoni madreperla', 89.99, 'donna', 6, 3, 6),
('T-shirt Bambino', 'T-shirt colorata con stampa cartoon e collo rotondo', 19.99, 'bambino', 1, 2, NULL),
('Jeans Bambino', 'Jeans con ginocchia rinforzate e vita regolabile', 39.99, 'bambino', 3, 5, 7),
('Felpa Bambino', 'Felpa con cappuccio e grafica divertente', 34.99, 'bambino', 2, 7, NULL),
('Costume da Bagno', 'Costume da bagno asciugatura rapida con fantasia tropicale', 29.99, 'uomo', 15, 12, 8),
('Calze Sportive', 'Set di 3 paia di calze sportive con rinforzo sul tallone', 14.99, 'uomo', 13, 1, NULL),
('Pantaloni Tuta', 'Pantaloni sportivi in tessuto tecnico con zip alle caviglie', 49.99, 'uomo', 16, 2, 9);


-- Inserimento di valori nella tabella Corriere
INSERT INTO Corriere (prezzo, azienda, giorni_consegna)
VALUES
(5.99, 'CorriereExpress', 7),
(9.99, 'SpeedyShipping', 3),
(3.99, 'EcoDelivery', 4),
(7.99, 'QuickShip', 8),
(4.99, 'LocalCourier', 2),
(6.99, 'FastTrack', 5),
(8.99, 'GlobalCourier', 1),
(2.99, 'BudgetDelivery', 4),
(10.99, 'PremiumShipping', 1),
(12.99, 'ExpressLogistics', 1);

-- Inserimento di valori nella tabella Coupon
INSERT INTO Coupon (codice_coupon, sconto_percentuale)
VALUES
('SUMMER2023', 10.00),
('SALE25', 25.00),
('FREESHIP', 100.00),
('WELCOME10', 10.00),
('FLASH50', 50.00),
('BLACKFRIDAY', 70.00),
('NEWYEAR2025', 30.00),
('SPRINGSALE', 20.00),
('WINTERCLEARANCE', 40.00),
('LOYALTY5', 5.00),
('BIRTHDAYGIFT', 50.00),
('EASTER2025', 15.00),
('FALLFASHION', 20.00),
('BACKTOSCHOOL', 30.00),
('VALENTINE2025', 25.00);

-- Inserimento di valori nella tabella Metodo_Pagamento
INSERT INTO Metodo_Pagamento (tipo_pagamento)
VALUES
('Carta di Credito'),
('PayPal'),
('Apple Pay'),
('Google Pay'),
('Bonifico'),
('Contrassegno');

-- Inserimento di valori nella tabella Indirizzo_Spedizione
INSERT INTO Indirizzo_Spedizione (id_utente, indirizzo, citta, regione, provincia, CAP)
VALUES
(1, 'Via Roma 1', 'Milano', 'Lombardia', 'MI', '20100'),
(2, 'Via Verdi 10', 'Roma', 'Lazio', 'RM', '00100'),
(3, 'Piazza Garibaldi 5', 'Napoli', 'Campania', 'NA', '80100'),
(4, 'Rue de la Paix 3', 'Parigi', 'Ile-de-France', '75', '75001'),
(5, 'Broadway 100', 'New York', 'New York', 'NY', '10001'),
(6, 'Via Toledo 45', 'Firenze', 'Toscana', 'FI', '50123'),
(7, 'Corso Italia 78', 'Torino', 'Piemonte', 'TO', '10121'),
(8, 'Via Nazionale 22', 'Bologna', 'Emilia-Romagna', 'BO', '40121'),
(9, 'Viale Europa 55', 'Venezia', 'Veneto', 'VE', '30123'),
(10, 'Via Marconi 12', 'Palermo', 'Sicilia', 'PA', '90100'),
(11, 'Via dei Mille 33', 'Genova', 'Liguria', 'GE', '16121'),
(12, 'Corso Cavour 67', 'Bari', 'Puglia', 'BA', '70121'),
(13, 'Via Libertà 28', 'Catania', 'Sicilia', 'CT', '95121'),
(14, 'Piazza Dante 9', 'Verona', 'Veneto', 'VR', '37121'),
(15, 'Via XX Settembre 41', 'Cagliari', 'Sardegna', 'CA', '09125');


-- Inserimento di valori nella tabella Ordine
INSERT INTO Ordine (id_utente, id_corriere, id_coupon, id_metodo_pagamento, id_indirizzo_spedizione, data_ordine, data_spedizione, prezzo_ordine)
VALUES
(1, 1, NULL, 1, 1, '2023-06-05', '2023-06-07', 49.99),
(2, 3, 2, 3, 2, '2023-06-06', '2023-06-08', 69.99),
(3, 2, NULL, 4, 1, '2023-06-06', NULL, 119.99),
(4, 4, 3, 2, 1, '2023-06-07', NULL, 999.99),
(5, 1, NULL, 5, 1, '2023-06-07', '2023-06-09', 29.99),
(6, 2, 1, 2, 6, '2023-06-10', '2023-06-12', 149.99);


-- Inserimento di valori nella tabella Oggetto_Ordine
INSERT INTO Oggetto_Ordine (id_ordine, id_prodotto, quantita_prodotto)
VALUES
(1, 3, 2),
(2, 2, 1),
(3, 4, 1),
(4, 1, 1),
(5, 5, 3);

-- Inserimento di valori nella tabella Carrello
INSERT INTO Carrello (id_utente, id_prodotto, quantita_prodotto, taglia_prodotto)
VALUES
(1, 1, 1, 'S'),
(2, 2, 2, 'M'),
(3, 3, 1, 'M'),
(4, 4, 1, 'L'),
(5, 5, 2, 'XL');

-- Inserimento di valori nella tabella Recensione
INSERT INTO Recensione (id_utente, id_prodotto, testo_recensione, valutazione, data_recensione)
VALUES
(1, 1, 'T-shirt di ottima qualità, il cotone è resistente e il taglio è perfetto. La indosso spesso e dopo diversi lavaggi mantiene ancora la forma e il colore. Ottimo rapporto qualità-prezzo.', 5, '2023-06-07'),
(2, 3, 'Mi piace molto la stampa vintage, dà un tocco originale. Il tessuto è comodo ma leggermente più sottile di quanto mi aspettassi. Comunque soddisfatto dell\'acquisto.', 4, '2023-06-01'),
(3, 2, 'La t-shirt premium vale ogni centesimo. Il tessuto traspirante è perfetto per l\'attività fisica, asciuga velocemente e non trattiene gli odori. Calza come previsto dalla tabella taglie.', 5, '2023-05-11'),
(4, 4, 'Felpa caldissima e confortevole. Il cappuccio è ben proporzionato e le tasche sono spaziose. L\'ho presa in offerta e ne valeva assolutamente la pena.', 5, '2023-06-04'),
(5, 5, 'Felpa tecnica di buona fattura, tiene davvero caldo anche con temperature basse. La cerniera è robusta. Unico difetto: il polsino si è leggermente allentato dopo qualche settimana.', 4, '2023-04-12'),
(6, 6, 'I jeans slim fit sono comodi e vestono bene, ma la taglia è leggermente più piccola del previsto. Consiglio di prendere una taglia in più.', 3, '2023-05-20'),
(7, 8, 'Camicia elegante e ben rifinita. I bottoni sono di buona qualità e il tessuto non si stropiccia facilmente. Perfetta per occasioni formali.', 5, '2023-03-15'),
(8, 9, 'Il maglione in lana è molto bello esteticamente ma un po\' troppo pesante. Inoltre tende a prudere leggermente sulla pelle. Qualità comunque buona.', 3, '2023-02-10'),
(9, 10, 'Giacca bomber eccezionale, impermeabile come promesso. Ha resistito a un acquazzone senza problemi. Stile unico e materiali premium.', 5, '2023-05-30'),
(10, 7, 'Pantaloni chino di buona qualità ma il colore è leggermente diverso da quello mostrato nelle foto. Il taglio dritto veste bene.', 4, '2023-04-22'),
(11, 11, 'Scarpe comode fin dal primo utilizzo. La suola ha una buona aderenza e il design è versatile, si abbina con tutto.', 5, '2023-06-10'),
(12, 13, 'Cappello bucket di qualità mediocre. Il materiale sembra economico e la taglia è troppo piccola. Non lo consiglio.', 2, '2023-05-25'),
(13, 14, 'Abito estivo leggero e fresco, la stampa floreale è bellissima e i dettagli sono curati. Perfetto per le giornate calde.', 5, '2023-06-15'),
(14, 15, 'La gonna plissettata è elegante ma il tessuto tende a elettrizzarsi facilmente. La vestibilità è comunque ottima.', 4, '2023-05-05'),
(15, 20, 'Costume da bagno fantastico, asciuga rapidamente come promesso e la fantasia è ancora più bella dal vivo.', 5, '2023-06-20');

-- Inserimento di valori nella tabella Immagine_Prodotto
INSERT INTO Immagine_Prodotto (id_prodotto, url_immagine)
VALUES
(1, 'products/M-element-Tshirt-front-black.jpg'),
(1, 'products/M-element-Tshirt-back-black.jpg'),
(2, 'products/M-element-Tshirt2-front-grey.jpg'),
(2, 'products/M-element-Tshirt2-back-grey.jpg'),
(3, 'products/M-element-Tshirt3-front-yellow.jpg'),
(3, 'products/M-element-Tshirt3-back-yellow.jpg'),
(4, 'products/M-adidas-sweatshirt-front-black.jpg'),
(5, 'products/M-adidas-sweatshirt-front-white.jpg'),
(6, 'products/M-H&H-sweatshirt-front-grey.jpg'),
(7, 'products/M-H&H-sweatshirt-front-white.jpg'),
(8, 'products/M-levis-jeans-front-blu.jpg'),
(9, 'products/M-levis-jeans-front-darkblu.jpg'),
(10, 'products/M-levis-pants-front-beige.jpg'),
(11, 'products/M-levis-pants-front-red.jpg'), 
(13, 'products/D-Stradivarius-camicia-front-green.jpg'),
(13, 'products/D-Stradivarius-camicia-back-green.jpg'),
(14, 'products/D-Stradivarius-camicia2-front-lightBlu.jpg'),
(14, 'products/D-Stradivarius-camicia2-back-lightBlu.jpg'), 
(15, 'products/D-Stradivarius-gonna-front-grey.jpg'),
(16, 'products/B-Stradivarius-Tshirt-front-lightBlu.jpg'),
(17, 'products/B-Stradivarius-Tshirt2-front-black.jpg');

-- Inserimento di valori nella tabella Magazzino
INSERT INTO Magazzino (id_prodotto, quantita, taglia)
VALUES
(1, 10, 'S'),
(1, 10, 'M'),
(1, 0 , 'L'),
(1, 0 , 'XL'),
(2, 5, 'S'),
(2, 5, 'M'),
(2, 0, 'L'),
(2, 5, 'XL'),
(3, 0, 'S'),
(3, 3, 'M'),
(3, 0, 'L'),
(3, 3, 'XL'),
(4, 8, 'S'),
(4, 8, 'M'),
(4, 8, 'L'),
(4, 8, 'XL'),
(5, 20, 'S'),
(5, 20, 'M'),
(5, 20, 'L'),
(5, 20, 'XL'),
(6, 20, 'S'),
(6, 20, 'M'),
(6, 0, 'L'),
(6, 10, 'XL'),
(7, 70, 'S'),
(7, 20, 'M'),
(7, 20, 'L'),
(7, 20, 'XL'),
(8, 0, 'S'),
(8, 20, 'M'),
(8, 10, 'L'),
(8, 20, 'XL'),
(9, 20, 'S'),
(9, 20, 'M'),
(9, 20, 'L'),
(9, 20, 'XL'),
(10, 20, 'S'),
(10, 40, 'M'),
(10, 20, 'L'),
(10, 0, 'XL'),
(11, 30, 'S'),
(11, 0, 'M'),
(11, 30, 'L'),
(11, 30, 'XL'),
(13, 30, 'S'),
(13, 30, 'M'),
(13, 30, 'L'),
(13, 30, 'XL'),
(14, 30, 'S'),
(14, 30, 'M'),
(14, 30, 'L'),
(14, 30, 'XL'),
(15, 30, 'S'),
(15, 30, 'M'),
(15, 30, 'L'),
(15, 30, 'XL'),
(16, 30, 'S'),
(16, 30, 'M'),
(16, 30, 'L'),
(16, 30, 'XL'),
(17, 30, 'S'),
(17, 30, 'M'),
(17, 30, 'L'),
(17, 30, 'XL'),
(12, 25, 'S'),
(12, 25, 'M'),
(12, 25, 'L'),
(12, 25, 'XL'),
(18, 15, 'S'),
(18, 15, 'M'),
(18, 15, 'L'),
(18, 0, 'XL'),
(19, 20, 'S'),
(19, 20, 'M'),
(19, 0, 'L'),
(19, 10, 'XL'),
(20, 30, 'S'),
(20, 30, 'M'),
(20, 30, 'L'),
(20, 0, 'XL'),
(21, 50, 'S'),
(21, 50, 'M'),
(21, 50, 'L'),
(21, 50, 'XL'),
(22, 15, 'S'),
(22, 15, 'M'),
(22, 15, 'L'),
(22, 15, 'XL');


-- Inserimento di valori nella tabella Messaggio_Assistenza
INSERT INTO Messaggio_Assistenza (id,id_utente,richiesta,risposta, data_richiesta) VALUES
(1,1,'quando sarà disponibile la prossima promozione?','la prossima promozione sarà una promozione sui prodotti estivi e sarà disponibile fra 3 mesi', '2023-06-07'),
(2,1,'saranno mai venduti occhiali da sole?',NULL, '2023-06-07'),
(3,1,'saranno mai venduti bracciali?',NULL, '2023-06-07'),
(4,2,'ho eseguito un ordine 3 mesi fa, è possibile eseuguire un reso?',NULL, '2023-06-07'),
(5,2,'ho appena eseguito un ordine, è possibile annullarlo?',NULL, '2023-06-07'),
(6,3,'vorrei sapere se è possibile acquistare capi personalizzati','Al momento non offriamo servizi di personalizzazione, ma stiamo valutando di introdurli in futuro', '2023-05-20'),
(7,4,'ho ricevuto un prodotto difettoso, come posso procedere per il reso?','Può avviare la procedura di reso dalla sezione "I miei ordini" del suo account', '2023-06-10'),
(8,5,'è possibile modificare l\'indirizzo di spedizione dopo aver effettuato l\'ordine?',NULL, '2023-06-15'),
(9,6,'ho dimenticato la password, come posso reimpostarla?','Può utilizzare la funzione "Password dimenticata" nella pagina di login', '2023-05-30'),
(10,7,'quando arriverà la collezione autunno/inverno?','La nuova collezione autunno/inverno sarà disponibile da metà settembre', '2023-06-18'),
(11,8,'è possibile pagare in contanti alla consegna?',NULL, '2023-06-01'),
(12,9,'offrite sconti per studenti?','Al momento non abbiamo sconti specifici per studenti, ma organizziamo promozioni periodiche', '2023-05-25'),
(13,10,'quanto costa la spedizione internazionale?','Il costo della spedizione internazionale varia in base al paese di destinazione, può verificarlo in fase di checkout', '2023-06-08'),
(14,11,'ho problemi ad applicare un coupon, cosa posso fare?',NULL, '2023-06-12'),
(15,12,'vorrei sapere se i vostri capi sono prodotti con materiali sostenibili','La maggior parte della nostra collezione utilizza materiali sostenibili e processi di produzione eco-friendly', '2023-05-15'),
(16,13,'il prodotto che ho ordinato è esaurito, quando sarà nuovamente disponibile?',NULL, '2023-06-20'),
(17,14,'posso acquistare un buono regalo?','Certamente, i buoni regalo sono disponibili nella sezione "Gift Card" del nostro sito', '2023-06-05'),
(18,15,'c\'è un limite massimo di prodotti acquistabili in un singolo ordine?',NULL, '2023-06-17'),
(19,3,'avete negozi fisici o siete solo online?','Al momento siamo presenti solo online, ma stiamo pianificando l\'apertura di negozi fisici', '2023-06-09'),
(20,8,'offrite servizi di sartoria per gli abiti acquistati?','Purtroppo non offriamo servizi di sartoria, ma possiamo consigliare professionisti nella sua zona', '2023-05-28');

-- Inserimento di valori nella tabella Preferiti (per test)
INSERT INTO Preferiti (id_utente, id_prodotto, data_aggiunta)
VALUES
(1, 1, '2023-06-01 10:30:00'),
(1, 3, '2023-06-02 14:15:00'),
(1, 5, '2023-06-03 16:45:00'),
(2, 2, '2023-06-01 11:20:00'),
(2, 4, '2023-06-04 09:30:00'),
(3, 1, '2023-06-05 13:15:00'),
(4, 6, '2023-06-06 15:00:00'),
(5, 7, '2023-06-07 17:30:00'),
(5, 8, '2023-06-08 12:45:00'),
(5, 9, '2023-06-09 14:20:00');

-- Inserimento di valori nella tabella Service
INSERT INTO Service (id, script, description) VALUES
(1, 'admin.php', NULL),
(2, 'profile.php', NULL);

-- Inserimento di valori nella tabella Ugroup
INSERT INTO Ugroup (id, name, description) VALUES
(1, 'Administrator', NULL),
(2, 'User', NULL);

-- Inserimento di valori nella tabella Ugroup_has_service
INSERT INTO Ugroup_has_service (ugroup_id, service_id) VALUES
(1, 1),
(2, 2);

-- Inserimento di valori nella tabella User_has_ugroup
INSERT INTO User_has_ugroup (id, id_utente, id_ugroup) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 2),
(4, 4, 2),
(5, 5, 2),
(6, 6, 2),
(7, 7, 2),
(8, 8, 2),
(9, 9, 2),
(10, 10, 2),
(11, 11, 2),
(12, 12, 2),
(13, 13, 2),
(14, 14, 2),
(15, 15, 2);



