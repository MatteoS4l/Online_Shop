-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Lug 23, 2025 alle 18:56
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `database`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `cognome` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `email`, `password`) VALUES
(1, 'Mario', 'Rossi', 'mario.rossi@example.com', '5f8893c1660fd33eee69d01ee8750e4e'),
(2, 'Giulia', 'Bianchi', 'giulia.bianchi@example.com', 'bfb700f6f4837eea301b3db1e0a3d121'),
(3, 'Luca', 'Verdi', 'luca.verdi@example.com', '4643ebd881793c26b6c64c436d510c95'),
(4, 'Martina', 'Gialli', 'martina.gialli@example.com', 'c26be8aaf53b15054896983b43eb6a65'),
(5, 'Simone', 'Neri', 'simone.neri@example.com', '365abb9d0bfe8244b37e291ef9f87551'),
(6, 'Alessandro', 'Ferrari', 'alessandro.ferrari@example.com', 'e10e2b2e128c047c7d9993ce4b1295d7'),
(7, 'Francesca', 'Romano', 'francesca.romano@example.com', '083934c88b2186d4be0a8a930369eef9'),
(8, 'Marco', 'Esposito', 'marco.esposito@example.com', 'ad1546b7a0d119a2020847e1fce51efd'),
(9, 'Chiara', 'Russo', 'chiara.russo@example.com', '62577fab45b01054a9fd655c4e4b4d08'),
(10, 'Roberto', 'Colombo', 'roberto.colombo@example.com', '6ff38e104ddb6d7aa3bcb67d132645d9'),
(11, 'Valentina', 'Ricci', 'valentina.ricci@example.com', 'c6245363b2c006c1e6e9005167a5de12'),
(12, 'Andrea', 'Marino', 'andrea.marino@example.com', 'b4429d7006c54471096e1ebd6d967631'),
(13, 'Laura', 'Greco', 'laura.greco@example.com', 'aaad94013952059c58aea71e5d577d35'),
(14, 'Davide', 'Conti', 'davide.conti@example.com', '58fced6e7f859ee9e580b6f5816f9b66'),
(15, 'Sofia', 'Mancini', 'sofia.mancini@example.com', '1a7b9a791bae2e38a9abdfaf0d5ea1a8'),
(16, 'gianni', 'gna', 'gna@gmail.com', '7b701456aacd2bce5959b1ae4d4fcd79');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
