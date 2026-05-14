# Script d'oral — Étudiant 1 — BorneInteract
## BTS CIEL-IR · Lycée César Baggio × Adictiz · Session 2025-2026
## Durée totale : 15 minutes

---

## SLIDE 1 — Titre (1 min)

**"Bonjour. Je m'appelle [prénom nom], et je vais vous présenter ma contribution au projet BorneInteract, réalisé en partenariat avec Adictiz dans le cadre de notre BTS CIEL option IR."**

BorneInteract, c'est une borne interactive phygitale installée en magasin. L'idée est simple : le client reçoit un ticket de caisse avec un code, il le scanne sur la borne, et il joue à un mini-jeu pour tenter de gagner une réduction ou un bon cadeau.

Notre équipe est composée de trois étudiants. Mon périmètre — l'Étudiant 1 — couvre six missions principales :
- La sécurité de la base de données
- L'intégration du lecteur RFID RC522
- Le capteur de couleur TCS3200 pour les jetons physiques
- Le jeu Memory en HTML5 JavaScript
- Le système de deuxième chance
- La programmation de la caméra ESP32-CAM

---

## SLIDE 2 — Contexte & Architecture (1 min 30)

**"Avant de rentrer dans le détail de mes missions, voici le parcours client et la stack technique du projet."**

Concrètement : le client passe en caisse, reçoit un ticket avec un QR code ou un code-barres, scanne ce ticket sur la borne, joue un mini-jeu, et peut gagner un bon cadeau. S'il perd, il a une deuxième chance via son smartphone — j'y reviens tout à l'heure.

Côté technique, le projet repose sur :
- Un microcontrôleur **ESP8266 NodeMCU** qui gère le RFID, le capteur couleur, et le WiFi
- Un backend **PHP 8.2 sur MySQL 8.0**, déployé avec **Docker Compose**
- Une interface borne en **HTML5 / JavaScript** et une app mobile **Flutter**

Les trois périmètres sont complémentaires : moi je m'occupe du hardware et de la sécurité, l'Étudiant 2 de l'application Flutter et des jeux web, l'Étudiant 3 de l'API PHP, de la base de données et du dashboard administrateur.

---

## SLIDE 3 — Mes 6 missions (30 sec)

**"Voici mes six missions en un coup d'œil."**

Je vais maintenant détailler chacune d'entre elles.

---

## SLIDE 4 — Sécurité BDD (2 min)

**"La sécurité de la base de données était une priorité absolue. Voici les quatre mesures mises en place."**

**Premièrement, le hachage des mots de passe avec bcrypt.** Aucun mot de passe n'est jamais stocké en clair dans la base. J'utilise la fonction `password_hash` de PHP avec l'algorithme `PASSWORD_BCRYPT`, qui est adaptatif — c'est-à-dire que le coût de calcul augmente avec le temps pour rester résistant face aux machines plus puissantes.

**Deuxièmement, les Prepared Statements PDO.** C'est la protection contre les injections SQL. Au lieu de concaténer les variables directement dans la requête — ce qui permettrait à un attaquant d'injecter du code SQL malveillant — je lie les paramètres séparément. L'exemple à droite de la slide montre la différence : la version dangereuse en rouge, la version sécurisée en vert.

**Troisièmement, les tokens CSRF.** Chaque formulaire génère un token unique côté serveur. Si la requête ne contient pas ce token, elle est rejetée — ce qui empêche les attaques de type "requête forgée depuis un site tiers".

**Quatrièmement, le rate limiting.** On limite à trois tentatives d'inscription par heure par adresse IP, pour bloquer les scripts automatisés. Et enfin, tous les scans sont enregistrés dans des logs avec timestamp, UID et résultat — pour la traçabilité et les audits de sécurité.

---

## SLIDE 5 — RFID RC522 (2 min)

**"Passons au hardware. Le lecteur RFID RC522 permet d'identifier le client via son badge."**

Le RC522 communique avec l'ESP8266 via le bus SPI. Le câblage est visible à gauche de la slide. Les broches importantes : SDA sur D2, SCK sur D5, MOSI sur D7, MISO sur D6, RST sur D1.

Quand le client approche son badge, l'ESP8266 lit l'UID — le numéro unique du badge — le formate en hexadécimal majuscule, et l'envoie par une requête HTTP POST à l'endpoint `/api/rfid_scan.php`. L'API vérifie si le badge est dans la base, ouvre une session de jeu, et répond.

Le code à droite montre les deux fonctions clés : `lireUID()` qui parcourt les bytes du badge pour construire la chaîne hexadécimale, et `envoyerScan()` qui fait le POST HTTP.

Un point technique important : l'**anti-rebond**. Sans ça, un seul appui peut déclencher 5 ou 10 scans en moins d'une seconde. J'ai implémenté un timer de 3 secondes — si le même UID est lu moins de 3 secondes après le scan précédent, il est ignoré.

---

## SLIDE 6 — TCS3200 & Jeton (2 min)

**"En complément du badge RFID, la borne accepte aussi des jetons physiques. Un jeton rouge est détecté par le capteur de couleur TCS3200."**

Le TCS3200 est un capteur qui émet de la lumière et mesure la fréquence de sortie proportionnelle à l'intensité réfléchie. En jouant sur les entrées S2 et S3, on sélectionne le filtre : rouge, vert, ou bleu. On mesure donc les trois composantes RGB du jeton en face du capteur.

Le câblage : S2 sur D3, S3 sur D4, la sortie OUT sur D0. S0 et S1 sont câblés fixes sur 3.3V et GND pour régler la fréquence de sortie.

La logique de détection est dans la fonction `jetonValide()` visible à droite : un jeton rouge a une valeur R faible — entre 0 et 200 — et des valeurs G et B élevées — entre 200 et 999. Si les trois conditions sont vraies simultanément, le jeton est validé et le signal est envoyé à l'API.

Le calibrage est fait en pratique : on lance le sketch `TCS3200_Sketch.ino`, on présente le vrai jeton rouge 20 fois devant le capteur, on note les valeurs minimales et maximales pour chaque couleur, et on les inscrit dans `config.h`. Ce calibrage est à refaire avec le jeton officiel d'Adictiz.

---

## SLIDE 7 — Jeu Memory (2 min)

**"Côté logiciel, j'ai développé le jeu Memory en HTML5 JavaScript."**

Le principe est classique : 16 cartes cachées formant 8 paires d'émojis. Le joueur clique pour retourner les cartes, deux par deux. Si elles forment une paire, elles restent visibles. Sinon, elles se referment après une seconde.

L'aspect technique le plus intéressant est l'algorithme de mélange. J'utilise **Fisher-Yates**, visible à droite de la slide. C'est un algorithme O(n) qui garantit une distribution uniforme — chaque ordre de cartes a exactement la même probabilité d'apparaître. Un simple `Math.random()` naïf introduirait un biais.

L'état du jeu est géré par trois variables : `flipped` pour les cartes actuellement retournées, `matched` pour le nombre de paires trouvées, et `moves` pour compter les coups. À la fin de la partie, le score est envoyé à `enregistrer_partie.php` et stocké en base de données.

Les dotations associées — bouteille d'eau, stylo, clé USB, collier Baggio — sont définies dans la table `dotations` de la base, ce qui permet de les modifier sans toucher au code.

---

## SLIDE 8 — Système 2ème Chance (1 min 30)

**"Le système de deuxième chance est l'une des fonctionnalités les plus originales du projet."**

Quand un client perd à la borne, au lieu de repartir déçu, la borne affiche un QR code. Il le scanne avec son smartphone, l'application Flutter s'ouvre, et il peut rejouer le même jeu gratuitement.

Du côté de la base de données, j'ai fait un choix d'implémentation important : quand le client joue pour la première fois, on fait un **INSERT** — une nouvelle ligne dans la table `parties`. Si le client active la deuxième chance, on fait un **UPDATE** de cette même ligne — on ne crée pas une deuxième ligne.

La slide montre le SQL : on remplit les colonnes préfixées `dc_*` — `dc_jeu_id`, `dc_gain_libelle`, `dc_gagne`, `dc_score`, `dc_date`. Un seul enregistrement contient les deux résultats. L'avantage : pas de jointures complexes pour calculer les statistiques, traçabilité parfaite, et pas de risque de doublon.

Pour Adictiz, c'est aussi l'occasion de récupérer les coordonnées du client via l'app mobile.

---

## SLIDE 9 — ESP32-CAM (1 min 30)

**"Dernière mission : la vidéosurveillance via l'ESP32-CAM."**

L'ESP32-CAM est une carte avec un capteur OV2640 qui diffuse un flux vidéo MJPEG accessible à l'URL `http://[IP-ESP32-CAM]/stream`. Ce flux peut être intégré dans le dashboard administrateur pour surveiller la borne en temps réel.

Le défi principal de l'ESP32-CAM, c'est qu'il n'a pas de port USB natif pour la programmation. La solution : utiliser un ESP8266 comme pont de programmation. On neutralise l'ESP8266 en reliant son RST à GND, on connecte ses TX et RX aux broches U0T et U0R de l'ESP32-CAM, on met GPIO0 de l'ESP32-CAM à GND pour activer le mode flash, on uploade le firmware, et on débranche GPIO0 avant de faire un RESET.

La procédure est documentée dans le fichier `BranchementsESP32CAM.txt`.

---

## SLIDE 10 — Infrastructure Docker (1 min)

**"Tout l'environnement serveur tourne avec Docker Compose — trois services."**

Le service `db` : MySQL 8.0, données persistées sur un volume local. Le service `web` : PHP 8.2 Apache, qui sert l'application sur le port 80. Apache gère le HTTP, et Tailscale assure le tunnel HTTPS en amont — c'était d'ailleurs un bug corrigé : la redirection HTTP vers HTTPS causait des erreurs de connexion. Le service `phpmyadmin` sur le port 8081 pour l'administration visuelle de la base.

L'avantage du Docker Compose : l'environnement est identique en développement et en production. Plus de problèmes de "ça marche sur ma machine". Un simple `docker compose up -d` suffit pour tout démarrer.

---

## SLIDE 11 — Défis surmontés (1 min)

**"Voici les principaux défis rencontrés et comment je les ai résolus."**

Le plus critique était la mémoire IRAM de l'ESP8266 qui était à 92% — la limite est à 65 ko, et à 92% le firmware crashait aléatoirement. J'ai réduit cette utilisation à 34.8% en supprimant 90% des `Serial.println()` et en utilisant `PROGMEM` pour stocker les chaînes de caractères en flash plutôt qu'en RAM.

Le calibrage TCS3200 a nécessité plus de vingt mesures répétées avec le jeton rouge pour établir des plages min/max fiables.

L'anti-rebond RFID, le choix UPDATE vs INSERT pour la 2ème chance, la procédure de flash de l'ESP32-CAM, et les Prepared Statements PDO — tous ces défis ont leur solution documentée dans le code.

---

## SLIDE 12 — Résultats & Démos (30 sec)

**"En résumé, l'ensemble de mes six missions est opérationnel."**

Je peux faire des démonstrations en direct si vous le souhaitez :
- Scanner un badge RFID et voir la LED verte s'allumer
- Insérer le jeton rouge et voir le capteur réagir
- Jouer au Memory sur la borne
- Montrer les logs dans phpMyAdmin
- Ouvrir le flux de la caméra ESP32-CAM dans un navigateur

---

## SLIDE 13 — Conclusion (30 sec)

**"Pour conclure : j'ai réalisé six missions couvrant le hardware IoT, la sécurité de la base de données, un jeu complet, un mécanisme de fidélisation original, et la vidéosurveillance."**

Les points techniques saillants sont l'optimisation mémoire de l'ESP8266, la conformité sécurité avec bcrypt et PDO, et le choix d'architecture UPDATE vs INSERT pour la deuxième chance.

La prochaine étape est le calibrage TCS3200 avec le jeton officiel d'Adictiz, puis les tests en conditions réelles.

**"Je vous remercie pour votre attention. Je suis prêt pour vos questions."**

---

## Questions probables du jury — Réponses préparées

**"Pourquoi UPDATE et pas INSERT pour la 2ème chance ?"**
> Un INSERT créerait deux lignes distinctes pour un même achat. Il faudrait ensuite les joindre pour calculer les statistiques — requêtes plus complexes, risque d'incohérence. Avec l'UPDATE, tout est dans une seule ligne : 1 ticket = 1 ligne = 2 résultats possibles. C'est plus simple et plus traceable.

**"Comment avez-vous réduit la IRAM de 92% à 34.8% ?"**
> L'ESP8266 a 64 ko d'IRAM (RAM d'instructions). Chaque `Serial.println()` avec une chaîne en dur alloue cette chaîne en IRAM. J'ai supprimé 90% de ces logs et utilisé la macro `PROGMEM` pour les chaînes restantes, qui les stocke en flash plutôt qu'en IRAM. J'ai aussi remplacé certains `delay()` par `delayMicroseconds()` pour économiser des cycles.

**"Pourquoi PDO et pas mysqli ?"**
> PDO est indépendant du moteur de base de données — si on changeait MySQL pour PostgreSQL, le code PHP ne changerait pas. L'API est plus propre, les exceptions sont plus faciles à gérer, et les Prepared Statements sont natifs sans configuration supplémentaire.

**"Qu'est-ce que le Fisher-Yates ?"**
> C'est un algorithme de mélange en O(n) qui garantit une distribution uniforme : chaque permutation possible des 16 cartes a exactement la même probabilité d'apparaître. L'alternative naïve — `array.sort(() => Math.random() - 0.5)` — introduit un biais statistique car le comportement de `sort` n'est pas garanti avec des comparateurs non-déterministes.

**"Quel protocole de communication utilise le RC522 ?"**
> Le SPI — Serial Peripheral Interface. C'est un bus synchrone 4 fils : MOSI (données vers l'esclave), MISO (données depuis l'esclave), SCK (horloge), et SS/SDA (sélection de l'esclave). C'est plus rapide que I2C et bien adapté au RC522 qui doit transférer des données de badge rapidement.

**"Quel est le rôle du GPIO0 dans le flash de l'ESP32-CAM ?"**
> L'ESP32 vérifie au démarrage l'état de GPIO0. Si GPIO0 est à GND (niveau bas), il entre en mode bootloader — c'est-à-dire qu'il attend un firmware à charger. Si GPIO0 est libre (niveau haut), il démarre normalement. C'est pourquoi on doit connecter GPIO0 à GND seulement pendant le flash, puis le débrancher avant de faire un RESET pour lancer le firmware.

---

## Timing suggéré

| Slide | Contenu | Durée |
|-------|---------|-------|
| 1 | Titre & présentation | 1 min |
| 2 | Contexte & architecture | 1 min 30 |
| 3 | Vue d'ensemble 6 missions | 30 sec |
| 4 | Sécurité BDD | 2 min |
| 5 | RFID RC522 | 2 min |
| 6 | TCS3200 & Jeton | 2 min |
| 7 | Jeu Memory | 2 min |
| 8 | 2ème Chance | 1 min 30 |
| 9 | ESP32-CAM | 1 min 30 |
| 10 | Docker | 1 min |
| 11 | Défis | 1 min |
| 12 | Résultats | 30 sec |
| 13 | Conclusion | 30 sec |
| **Total** | | **~17 min** |

> Conseil : garder 2 minutes de marge pour les transitions et les questions éventuelles pendant l'oral.
