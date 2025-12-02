# Modélisation du Système de Stockage - Étudiant 3
## Diagramme entité-relation (ERD)

```
┌─────────────────┐         ┌─────────────────┐
│    CLIENTS      │         │      JEUX        │
├─────────────────┤         ├─────────────────┤
│ PK id           │         │ PK id           │
│    nom          │         │    nom          │
│    prenom       │         │    description  │
│    email        │         │    type_jeu     │
│    telephone    │         │    actif        │
│    code_barre   │         └────────┬────────┘
│    mot_de_passe │                  │ 1
│    date_inscription│               │
│    actif        │         ┌────────▼────────┐
└────────┬────────┘         │   DOTATIONS     │
         │ 1                ├─────────────────┤
         │                  │ PK id           │
    ┌────▼────┐             │ FK jeu_id       │
    │         │             │    libelle      │
    │PARTIES  │─────────────│    valeur       │
    │         │  FK dot.id  │    probabilite  │
    ├─────────┤             │    stock        │
    │ PK id   │             └─────────────────┘
    │FK client│
    │FK jeu   │         ┌─────────────────┐
    │FK dotation│        │  CODES_BARRES   │
    │code_barre│        ├─────────────────┤
    │date     │        │ PK id           │
    │score    │        │    code         │
    │gagne    │        │    utilise      │
    └─────────┘        │    date_scan    │
                        │ FK client_id   │
                        └─────────────────┘

┌─────────────────┐     ┌─────────────────┐
│ ADMINISTRATEURS │     │    SESSIONS     │
├─────────────────┤     ├─────────────────┤
│ PK id           │     │ PK id (token)   │
│    login        │     │ FK client_id    │
│    mot_de_passe │     │ FK admin_id     │
│    role         │     │    ip_address   │
└─────────────────┘     │    date_debut   │
                         │    expiration  │
                         └─────────────────┘
```

## Niveaux d'accès
- **Administrateur** : accès total (CRUD toutes tables)
- **Maintenance** : lecture seule + modification jeux/dotations
- **Client** : accès à son propre profil et ses parties
- **Anonyme** : jouer sans compte (partie sans client_id)
