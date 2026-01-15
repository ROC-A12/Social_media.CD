# Social Media Platform - Privacy & Follow System

## Setup Instructions

Run deze pagina EENMALIG om de database tables in te stellen:
```
localhost/xampp/htdocs/Github/Social_media.CD/setup_privacy.php
```

Dit voegt toe:
- `is_private` kolom aan users tabel
- `follow_requests` tabel voor follower aanvragen

## Functies

### Privacy Settings
- **Openbare Account (Default)**: Iedereen kan je posts zien en je direct volgen
- **Privé Account**: Mensen moeten een follow-request sturen, jij moet deze accepteren of afwijzen

### Follow System
1. **Openbare Accounts**: Direct volgen
2. **Privé Accounts**: Stuur follow-request → eigenaar accepteert/wijst af → je volgt deze persoon

### Pagina's

#### `/explore.php`
- Ontdek posts van openbare accounts
- Zie populaire gebruikers
- Zie andere gebruikers suggesties

#### `/profile.php`
- **Privacy instellingen**: Toggle privé/openbaar
- **Follow requests**: Accepteer/weiger aanvragen (alleen voor privé accounts)
- **Posts**: Alleen zichtbaar als je volgt (privé) of altijd (openbaar)

#### `/index.php` (Feed)
- Posts van mensen die je volgt
- Delete je eigen posts

## Database Tables

### Gebruikers tabel
- `is_private` (BOOLEAN) - Privacy status

### Follow Requests
- `requester_id` - Wie de request stuurt
- `recipient_id` - Wie de request ontvangt
- `status` - pending/accepted/rejected

### Follows
- `follower_id` - Wie volgt
- `following_id` - Wie wordt gevolgd
