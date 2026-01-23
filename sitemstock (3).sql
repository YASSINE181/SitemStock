-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 21 jan. 2026 à 10:58
-- Version du serveur : 10.4.25-MariaDB
-- Version de PHP : 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `sitemstock`
--

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `nom` varchar(20) NOT NULL,
  `prenom` varchar(20) NOT NULL,
  `telephone` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `adresse` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`id`, `nom`, `prenom`, `telephone`, `email`, `adresse`) VALUES
(3, 'najjar', 'mayar', 50530418, 'mayar@gmail.com', '84 avenue des Bruyères - 69 Décines-Charpieu FRANC'),
(4, 'benahmedh', 'ahmed', 25363644, 'ahmedba@gmail.com', '278, rue Lecourbe, 75015 PARISs'),
(7, 'test', 'test', 25300415, 'test@gmail.com', 'aafrdushy hdio');

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id` int(11) NOT NULL,
  `numero_commande` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `date_commande` date NOT NULL,
  `date_livraison` date NOT NULL,
  `montant_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id`, `numero_commande`, `client_id`, `date_commande`, `date_livraison`, `montant_total`) VALUES
(4, 'CMD202601193420', 3, '2026-01-19', '2026-01-26', '1160.00'),
(7, 'CMD202601205098', 7, '2026-01-20', '2026-01-27', '450.00');

-- --------------------------------------------------------

--
-- Structure de la table `commande_produit`
--

CREATE TABLE `commande_produit` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `commande_produit`
--

INSERT INTO `commande_produit` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_unitaire`, `montant_total`) VALUES
(6, 0, 5, 2, '180.00', '360.00'),
(12, 4, 5, 5, '180.00', '900.00'),
(13, 4, 3, 2, '130.00', '260.00'),
(18, 7, 7, 3, '150.00', '450.00');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseur`
--

CREATE TABLE `fournisseur` (
  `id` int(11) NOT NULL,
  `nom` varchar(20) NOT NULL,
  `nomLivreur` varchar(20) NOT NULL,
  `telephone` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `adresse` varchar(50) NOT NULL,
  `etat` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `fournisseur`
--

INSERT INTO `fournisseur` (`id`, `nom`, `nomLivreur`, `telephone`, `email`, `adresse`, `etat`) VALUES
(1, 'atoopc', 'david martin', 332222, 'atoopcContact@gmail.com', '278, rue Lecourbe, 75015 PARIS', '1'),
(2, 'ipo_technologiess', 'samuel_thomas', 336568737, 'ipo_techcontact@gmail.com', '84 avenue des Bruyères - 69 Décines-Charpieu FRANC', '1'),
(19, 'test', 'test', 332222, 'test@gmail.com', 'aafrdushy hdio', '1'),
(20, 'abc', 'aaa', 25366365, 'abc@gmail.com', 'cxcc', '0');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements`
--

CREATE TABLE `mouvements` (
  `id` int(11) NOT NULL,
  `id_produit` int(20) NOT NULL,
  `type` enum('entree','sortie') NOT NULL,
  `quantite` int(20) NOT NULL,
  `date_mouvement` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `mouvements`
--

INSERT INTO `mouvements` (`id`, `id_produit`, `type`, `quantite`, `date_mouvement`) VALUES
(3, 2, 'entree', 50, '2026-01-16'),
(5, 3, 'sortie', 50, '2026-01-20');

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id` int(20) NOT NULL,
  `nom` varchar(20) CHARACTER SET utf8 NOT NULL,
  `description` varchar(100) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `prix_achat` decimal(10,2) NOT NULL,
  `prix_vente` decimal(10,2) NOT NULL,
  `quantite_stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id`, `nom`, `description`, `fournisseur_id`, `prix_achat`, `prix_vente`, `quantite_stock`, `created_at`) VALUES
(1, 'souris', '', 0, '50.00', '120.00', 300, '2026-01-12 13:17:33'),
(2, 'pc dell', '', 0, '1200.00', '1350.00', 357, '2026-01-12 13:17:33'),
(3, 'clavier', '', 2, '90.00', '130.00', 311, '2026-01-13 19:10:49'),
(5, 'casque', '', 1, '130.00', '180.00', 10, '2026-01-16 13:05:24'),
(7, 'test', '', 1, '120.00', '150.00', 17, '2026-01-20 16:48:52');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `etat` enum('0','1') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `nom`, `email`, `mot_de_passe`, `etat`) VALUES
(9, 'abri ahlemm', 'chaima@gmail.com', '$2y$10$90P38oKT7R2U8UvOEu.YMONfHaMyGone0AHWkvjIF5TDkrmWI4jNm', '0'),
(29, 'mohamed', 'mohamed@gmail.com', '$2y$10$nbz7gHAPVBheWmk2682rzOJ9ltLuLXObS/PySipKuvuZtbRCCCeVm', '1'),
(30, 'aymen', 'aymen@gmail.com', '$2y$10$iyz3x2eh71k/BvPQkf0T/ep36DBbNGYAiYy3T605ceHe7pw5s0tXu', '0'),
(32, 'testtt', 'testt@gmail.com', '$2y$10$Kl/evUyJpItLTkmBkobzvea92sJ/Z4h0QwHG9rinnZG6nEGBj1Lb2', '1'),
(33, 'avd', 'avd@gmail.com', '$2y$10$sxQHtkZ2pQimSiBcsVkU/emu2sefpurzKLJB54Ofxh6zEV8bVOdWG', '0'),
(34, 'test12', 'testtt@gmail.com', '$2y$10$hPSKmgRqnpMjosc.XAfPOuAd8sxU2Z.h8iaRGvCA2eERwLpGwgW0O', '0'),
(35, 'yyyyy', 'yy@gmail.com', '$2y$10$wmOrl.Apw/rixv5ThUAZKeYXZ3LquMW7PMXdOV6tfXnTe9kPxcRtS', '0');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_commande` (`numero_commande`),
  ADD KEY `fk_commande_client` (`client_id`);

--
-- Index pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mouvements`
--
ALTER TABLE `mouvements`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `commande_produit`
--
ALTER TABLE `commande_produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `fournisseur`
--
ALTER TABLE `fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `mouvements`
--
ALTER TABLE `mouvements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `fk_commande_client` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
