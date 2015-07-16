-- IF YOU CAN, USE THE COMMAND LINE TO INSTALL THIS

CREATE TABLE `Appkey` (
`id` int(11) NOT NULL,
  `relationKey` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Billet`
--

CREATE TABLE `Billet` (
`id` int(11) NOT NULL,
  `navette_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `tarif_id` int(11) NOT NULL,
  `valide` tinyint(1) NOT NULL,
  `idPayutc` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `isMajeur` tinyint(1) NOT NULL,
  `barcode` int(11) NOT NULL,
  `dateAchat` datetime NOT NULL,
  `accepteDroitImage` tinyint(1) NOT NULL,
  `consomme` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Contraintes`
--

CREATE TABLE `Contraintes` (
`id` int(11) NOT NULL,
  `doitEtreCotisant` tinyint(1) NOT NULL,
  `debutMiseEnVente` datetime NOT NULL,
  `finMiseEnVente` datetime NOT NULL,
  `accessibleExterieur` tinyint(1) NOT NULL,
  `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `doitNePasEtreCotisant` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Evenement`
--

CREATE TABLE `Evenement` (
`id` int(11) NOT NULL,
  `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `quantiteMax` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Log`
--

CREATE TABLE `Log` (
`id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `Date` datetime NOT NULL,
  `Content` longtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Navette`
--

CREATE TABLE `Navette` (
`id` int(11) NOT NULL,
  `trajet_id` int(11) NOT NULL,
  `horaireDepart` datetime NOT NULL,
  `capaciteMax` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `PotCommunTarifs`
--

CREATE TABLE `PotCommunTarifs` (
`id` int(11) NOT NULL,
  `Titre` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tarif`
--

CREATE TABLE `tarif` (
`id` int(11) NOT NULL,
  `contraintes_id` int(11) NOT NULL,
  `evenement_id` int(11) NOT NULL,
  `prix` decimal(10,0) NOT NULL,
  `quantite` int(11) NOT NULL,
  `quantiteParPersonne` int(11) NOT NULL,
  `nomTarif` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `idPayutc` int(11) NOT NULL,
  `potCommun_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Trajet`
--

CREATE TABLE `Trajet` (
`id` int(11) NOT NULL,
  `lieuDepart` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lieuArrivee` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Utilisateur`
--

CREATE TABLE `Utilisateur` (
`id` int(11) NOT NULL,
  `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `birthday` date NOT NULL,
  `admin` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `UtilisateurCAS`
--

CREATE TABLE `UtilisateurCAS` (
`id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `userBadge` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cotisant` tinyint(1) NOT NULL,
  `loginCAS` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `UtilisateurExterieur`
--

CREATE TABLE `UtilisateurExterieur` (
`id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motDePasse` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `Appkey`
--
ALTER TABLE `Appkey`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `Billet`
--
ALTER TABLE `Billet`
 ADD PRIMARY KEY (`id`), ADD KEY `IDX_18AF4FC0DD1420CC` (`navette_id`), ADD KEY `IDX_18AF4FC0FB88E14F` (`utilisateur_id`), ADD KEY `IDX_18AF4FC0357C0A59` (`tarif_id`);

--
-- Index pour la table `Contraintes`
--
ALTER TABLE `Contraintes`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `Evenement`
--
ALTER TABLE `Evenement`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `Log`
--
ALTER TABLE `Log`
 ADD PRIMARY KEY (`id`), ADD KEY `IDX_B7722E25A76ED395` (`user_id`);

--
-- Index pour la table `Navette`
--
ALTER TABLE `Navette`
 ADD PRIMARY KEY (`id`), ADD KEY `IDX_15E9F652D12A823` (`trajet_id`);

--
-- Index pour la table `PotCommunTarifs`
--
ALTER TABLE `PotCommunTarifs`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `tarif`
--
ALTER TABLE `tarif`
 ADD PRIMARY KEY (`id`), ADD KEY `IDX_CFB0A6CDB7EAAE9` (`contraintes_id`), ADD KEY `IDX_CFB0A6CDFD02F13` (`evenement_id`), ADD KEY `IDX_CFB0A6CDA0054F5E` (`potCommun_id`);

--
-- Index pour la table `Trajet`
--
ALTER TABLE `Trajet`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
 ADD PRIMARY KEY (`id`);

--
-- Index pour la table `UtilisateurCAS`
--
ALTER TABLE `UtilisateurCAS`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `UNIQ_C630C826A76ED395` (`user_id`);

--
-- Index pour la table `UtilisateurExterieur`
--
ALTER TABLE `UtilisateurExterieur`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `UNIQ_83C1A8A76ED395` (`user_id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `Appkey`
--
ALTER TABLE `Appkey`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Billet`
--
ALTER TABLE `Billet`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Contraintes`
--
ALTER TABLE `Contraintes`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Evenement`
--
ALTER TABLE `Evenement`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Log`
--
ALTER TABLE `Log`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Navette`
--
ALTER TABLE `Navette`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `PotCommunTarifs`
--
ALTER TABLE `PotCommunTarifs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `tarif`
--
ALTER TABLE `tarif`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Trajet`
--
ALTER TABLE `Trajet`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `Utilisateur`
--
ALTER TABLE `Utilisateur`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `UtilisateurCAS`
--
ALTER TABLE `UtilisateurCAS`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT pour la table `UtilisateurExterieur`
--
ALTER TABLE `UtilisateurExterieur`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `Billet`
--
ALTER TABLE `Billet`
ADD CONSTRAINT `FK_18AF4FC0357C0A59` FOREIGN KEY (`tarif_id`) REFERENCES `Tarif` (`id`),
ADD CONSTRAINT `FK_18AF4FC0DD1420CC` FOREIGN KEY (`navette_id`) REFERENCES `Navette` (`id`),
ADD CONSTRAINT `FK_18AF4FC0FB88E14F` FOREIGN KEY (`utilisateur_id`) REFERENCES `Utilisateur` (`id`);

--
-- Contraintes pour la table `Log`
--
ALTER TABLE `Log`
ADD CONSTRAINT `FK_B7722E25A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Utilisateur` (`id`);

--
-- Contraintes pour la table `Navette`
--
ALTER TABLE `Navette`
ADD CONSTRAINT `FK_15E9F652D12A823` FOREIGN KEY (`trajet_id`) REFERENCES `Trajet` (`id`);

--
-- Contraintes pour la table `tarif`
--
ALTER TABLE `tarif`
ADD CONSTRAINT `FK_CFB0A6CDA0054F5E` FOREIGN KEY (`potCommun_id`) REFERENCES `PotCommunTarifs` (`id`),
ADD CONSTRAINT `FK_CFB0A6CDB7EAAE9` FOREIGN KEY (`contraintes_id`) REFERENCES `Contraintes` (`id`),
ADD CONSTRAINT `FK_CFB0A6CDFD02F13` FOREIGN KEY (`evenement_id`) REFERENCES `Evenement` (`id`);

--
-- Contraintes pour la table `UtilisateurCAS`
--
ALTER TABLE `UtilisateurCAS`
ADD CONSTRAINT `FK_C630C826A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Utilisateur` (`id`);

--
-- Contraintes pour la table `UtilisateurExterieur`
--
ALTER TABLE `UtilisateurExterieur`
ADD CONSTRAINT `FK_83C1A8A76ED395` FOREIGN KEY (`user_id`) REFERENCES `Utilisateur` (`id`);