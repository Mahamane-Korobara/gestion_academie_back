CREATE TABLE `annees_academiques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `annee` varchar(255) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_cloturee` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `annees_academiques_annee_unique` (`annee`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `annonces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `type` enum('globale','filiere','niveau','cours','individuelle') NOT NULL DEFAULT 'globale',
  `filiere_id` bigint(20) unsigned DEFAULT NULL,
  `niveau_id` bigint(20) unsigned DEFAULT NULL,
  `cours_id` bigint(20) unsigned DEFAULT NULL,
  `destinataire_id` bigint(20) unsigned DEFAULT NULL,
  `auteur_id` bigint(20) unsigned NOT NULL,
  `priorite` enum('normale','importante','urgente') NOT NULL DEFAULT 'normale',
  `date_expiration` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `annonces_filiere_id_foreign` (`filiere_id`),
  KEY `annonces_niveau_id_foreign` (`niveau_id`),
  KEY `annonces_cours_id_foreign` (`cours_id`),
  KEY `annonces_destinataire_id_foreign` (`destinataire_id`),
  KEY `annonces_auteur_id_foreign` (`auteur_id`),
  KEY `annonces_type_index` (`type`),
  KEY `annonces_date_expiration_index` (`date_expiration`),
  CONSTRAINT `annonces_auteur_id_foreign` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annonces_cours_id_foreign` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annonces_destinataire_id_foreign` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annonces_filiere_id_foreign` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `annonces_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `bulletins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etudiant_id` bigint(20) unsigned NOT NULL,
  `semestre_id` bigint(20) unsigned DEFAULT NULL,
  `moyenne_generale` decimal(5,2) NOT NULL,
  `rang` int(11) DEFAULT NULL,
  `total_etudiants` int(11) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `decision` enum('admis','rattrapage','redoublant','diplome','passe_classe_superieure','ajourne') DEFAULT NULL,
  `fichier_pdf` varchar(255) DEFAULT NULL,
  `est_genere` tinyint(1) NOT NULL DEFAULT 0,
  `date_generation` timestamp NULL DEFAULT NULL,
  `genere_par` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bulletins_etudiant_id_semestre_id_unique` (`etudiant_id`,`semestre_id`),
  KEY `bulletins_semestre_id_foreign` (`semestre_id`),
  KEY `bulletins_genere_par_foreign` (`genere_par`),
  CONSTRAINT `bulletins_etudiant_id_foreign` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bulletins_genere_par_foreign` FOREIGN KEY (`genere_par`) REFERENCES `users` (`id`),
  CONSTRAINT `bulletins_semestre_id_foreign` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cours` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `coefficient` decimal(4,2) NOT NULL,
  `nombre_heures` int(11) DEFAULT NULL,
  `niveau_id` bigint(20) unsigned NOT NULL,
  `semestre_id` bigint(20) unsigned DEFAULT NULL,
  `annee_academique_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cours_code_unique` (`code`),
  KEY `cours_annee_academique_id_foreign` (`annee_academique_id`),
  KEY `cours_niveau_id_semestre_index` (`niveau_id`),
  KEY `cours_semestre_id_foreign` (`semestre_id`),
  KEY `cours_niveau_id_semestre_id_index` (`niveau_id`,`semestre_id`),
  CONSTRAINT `cours_annee_academique_id_foreign` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cours_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cours_semestre_id_foreign` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cours_professeur` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cours_id` bigint(20) unsigned NOT NULL,
  `professeur_id` bigint(20) unsigned NOT NULL,
  `annee_academique_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cours_prof_annee_unique` (`cours_id`,`professeur_id`,`annee_academique_id`),
  KEY `cours_professeur_professeur_id_foreign` (`professeur_id`),
  KEY `cours_professeur_annee_academique_id_foreign` (`annee_academique_id`),
  CONSTRAINT `cours_professeur_annee_academique_id_foreign` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cours_professeur_cours_id_foreign` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cours_professeur_professeur_id_foreign` FOREIGN KEY (`professeur_id`) REFERENCES `professeurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `documents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expediteur_id` bigint(20) unsigned NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('pdf','word','excel','powerpoint','image') NOT NULL,
  `fichier_path` varchar(255) NOT NULL,
  `fichier_original` varchar(255) NOT NULL,
  `taille` bigint(20) unsigned NOT NULL,
  `filiere_id` bigint(20) unsigned NOT NULL,
  `niveau_id` bigint(20) unsigned NOT NULL,
  `cours_id` bigint(20) unsigned NOT NULL,
  `date_expiration` timestamp NULL DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_expediteur_id_foreign` (`expediteur_id`),
  KEY `documents_niveau_id_foreign` (`niveau_id`),
  KEY `documents_cours_id_type_index` (`cours_id`),
  KEY `documents_filiere_id_niveau_id_index` (`filiere_id`,`niveau_id`),
  CONSTRAINT `documents_cours_id_foreign` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_expediteur_id_foreign` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_filiere_id_foreign` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `emploi_du_temps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cours_id` bigint(20) unsigned NOT NULL,
  `niveau_id` bigint(20) unsigned NOT NULL,
  `professeur_id` bigint(20) unsigned NOT NULL,
  `salle_id` bigint(20) unsigned DEFAULT NULL,
  `semestre_id` bigint(20) unsigned NOT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `type_seance` enum('cours','td','tp','examen') NOT NULL DEFAULT 'cours',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_niveau_creneau` (`jour`,`heure_debut`,`heure_fin`,`niveau_id`,`semestre_id`),
  UNIQUE KEY `unique_prof_creneau` (`jour`,`heure_debut`,`heure_fin`,`professeur_id`,`semestre_id`),
  KEY `emplois_du_temps_cours_id_foreign` (`cours_id`),
  KEY `emplois_du_temps_semestre_id_jour_index` (`semestre_id`,`jour`),
  KEY `edt_professeur_semestre_jour_idx` (`professeur_id`,`semestre_id`,`jour`),
  KEY `edt_niveau_semestre_jour_idx` (`niveau_id`,`semestre_id`,`jour`),
  KEY `edt_salle_semestre_jour_idx` (`salle_id`,`semestre_id`,`jour`),
  CONSTRAINT `emplois_du_temps_cours_id_foreign` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_professeur_id_foreign` FOREIGN KEY (`professeur_id`) REFERENCES `professeurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `emplois_du_temps_salle_id_foreign` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `emplois_du_temps_semestre_id_foreign` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `etudiants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `matricule` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `lieu_naissance` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `email_personnel` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `telephone_urgence` varchar(255) DEFAULT NULL,
  `filiere_id` bigint(20) unsigned NOT NULL,
  `niveau_id` bigint(20) unsigned NOT NULL,
  `annee_academique_id` bigint(20) unsigned NOT NULL,
  `statut` enum('actif','redoublant','rattrapage','diplome','passe_classe_superieure','abandonne','suspendu') NOT NULL DEFAULT 'actif',
  `date_inscription` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `etudiants_matricule_unique` (`matricule`),
  KEY `etudiants_user_id_foreign` (`user_id`),
  KEY `etudiants_niveau_id_foreign` (`niveau_id`),
  KEY `etudiants_annee_academique_id_foreign` (`annee_academique_id`),
  KEY `etudiants_filiere_id_niveau_id_index` (`filiere_id`,`niveau_id`),
  KEY `etudiants_statut_index` (`statut`),
  CONSTRAINT `etudiants_annee_academique_id_foreign` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`),
  CONSTRAINT `etudiants_filiere_id_foreign` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`),
  CONSTRAINT `etudiants_niveau_id_foreign` FOREIGN KEY (`niveau_id`) REFERENCES `niveaux` (`id`),
  CONSTRAINT `etudiants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cours_id` bigint(20) unsigned NOT NULL,
  `type_evaluation_id` bigint(20) unsigned NOT NULL,
  `semestre_id` bigint(20) unsigned NOT NULL,
  `titre` varchar(255) NOT NULL,
  `coefficient` decimal(4,2) NOT NULL,
  `date_evaluation` date DEFAULT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `salle_id` bigint(20) unsigned DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `statut` enum('planifiee','en_cours','terminee','annulee') NOT NULL DEFAULT 'planifiee',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluations_type_evaluation_id_foreign` (`type_evaluation_id`),
  KEY `evaluations_semestre_id_foreign` (`semestre_id`),
  KEY `evaluations_salle_id_foreign` (`salle_id`),
  KEY `evaluations_cours_id_semestre_id_index` (`cours_id`,`semestre_id`),
  CONSTRAINT `evaluations_cours_id_foreign` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_salle_id_foreign` FOREIGN KEY (`salle_id`) REFERENCES `salles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `evaluations_semestre_id_foreign` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_type_evaluation_id_foreign` FOREIGN KEY (`type_evaluation_id`) REFERENCES `types_evaluations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `filieres` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `duree_annees` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filieres_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etudiant_id` bigint(20) unsigned NOT NULL,
  `cours_id` bigint(20) unsigned NOT NULL,
  `annee_academique_id` bigint(20) unsigned NOT NULL,
  `semestre_id` bigint(20) unsigned NOT NULL,
  `date_inscription` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `etudiant_cours_semestre_unique` (`etudiant_id`,`cours_id`,`semestre_id`),
  KEY `inscriptions_cours_id_foreign` (`cours_id`),
  KEY `inscriptions_annee_academique_id_foreign` (`annee_academique_id`),
  KEY `inscriptions_semestre_id_foreign` (`semestre_id`),
  CONSTRAINT `inscriptions_annee_academique_id_foreign` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_cours_id_foreign` FOREIGN KEY (`cours_id`) REFERENCES `cours` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_etudiant_id_foreign` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_semestre_id_foreign` FOREIGN KEY (`semestre_id`) REFERENCES `semestres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `log_activites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `log_activites_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `log_activites_model_type_model_id_index` (`model_type`,`model_id`),
  CONSTRAINT `log_activites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `expediteur_id` bigint(20) unsigned NOT NULL,
  `destinataire_id` bigint(20) unsigned NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `is_lu` tinyint(1) NOT NULL DEFAULT 0,
  `date_lecture` timestamp NULL DEFAULT NULL,
  `deleted_at_expediteur` timestamp NULL DEFAULT NULL,
  `deleted_at_destinataire` timestamp NULL DEFAULT NULL,
  `message_parent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_expediteur_id_foreign` (`expediteur_id`),
  KEY `messages_message_parent_id_foreign` (`message_parent_id`),
  KEY `messages_destinataire_id_is_lu_index` (`destinataire_id`,`is_lu`),
  KEY `messages_deleted_at_expediteur_index` (`deleted_at_expediteur`),
  KEY `messages_deleted_at_destinataire_index` (`deleted_at_destinataire`),
  CONSTRAINT `messages_destinataire_id_foreign` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_expediteur_id_foreign` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_message_parent_id_foreign` FOREIGN KEY (`message_parent_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `niveaux` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filiere_id` bigint(20) unsigned NOT NULL,
  `nom` varchar(255) NOT NULL,
  `ordre` int(11) NOT NULL,
  `nombre_semestres` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `niveaux_filiere_id_nom_unique` (`filiere_id`,`nom`),
  CONSTRAINT `niveaux_filiere_id_foreign` FOREIGN KEY (`filiere_id`) REFERENCES `filieres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etudiant_id` bigint(20) unsigned NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `note` decimal(5,2) NOT NULL,
  `is_absent` tinyint(1) NOT NULL DEFAULT 0,
  `commentaire` text DEFAULT NULL,
  `statut` enum('brouillon','soumise','validee') NOT NULL DEFAULT 'brouillon',
  `saisi_par` bigint(20) unsigned NOT NULL,
  `valide_par` bigint(20) unsigned DEFAULT NULL,
  `date_saisie` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_validation` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notes_etudiant_id_evaluation_id_unique` (`etudiant_id`,`evaluation_id`),
  KEY `notes_saisi_par_foreign` (`saisi_par`),
  KEY `notes_valide_par_foreign` (`valide_par`),
  KEY `notes_evaluation_id_statut_index` (`evaluation_id`,`statut`),
  CONSTRAINT `notes_etudiant_id_foreign` FOREIGN KEY (`etudiant_id`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notes_saisi_par_foreign` FOREIGN KEY (`saisi_par`) REFERENCES `users` (`id`),
  CONSTRAINT `notes_valide_par_foreign` FOREIGN KEY (`valide_par`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `professeurs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `code_professeur` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `specialite` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `email_professionnel` varchar(255) NOT NULL,
  `telephone` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `professeurs_code_professeur_unique` (`code_professeur`),
  KEY `professeurs_user_id_foreign` (`user_id`),
  CONSTRAINT `professeurs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `salles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `batiment` varchar(255) DEFAULT NULL,
  `capacite` int(11) NOT NULL,
  `equipements` text DEFAULT NULL,
  `is_disponible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `semestres` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `annee_academique_id` bigint(20) unsigned NOT NULL,
  `numero` enum('S1','S2') NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `date_debut_examens` date DEFAULT NULL,
  `date_fin_examens` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `semestres_annee_academique_id_numero_unique` (`annee_academique_id`,`numero`),
  CONSTRAINT `semestres_annee_academique_id_foreign` FOREIGN KEY (`annee_academique_id`) REFERENCES `annees_academiques` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `types_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `coefficient_defaut` decimal(4,2) NOT NULL DEFAULT 1.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `types_evaluations_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;