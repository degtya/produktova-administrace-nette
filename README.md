# Administrace produktů

Tento projekt je technické zadání pro správu produktů.

## Jak aplikaci zprovoznit
1. **Stažení závislostí:**
   V kořenu projektu spusťte: `composer install`
2. **Databáze:**
   - V MySQL 8 vytvořte databázi.
   - Importujte soubor `database.sql` (součástí kořenové složky).
3. **Konfigurace:**
   - V `app/config/` vytvořte soubor `local.neon`.
   - Vložte do něj údaje k vaší databázi (viz `local.neon.example`).
4. **Spuštění:**
   - Nasměrujte server do složky `www/`.
   - Ujistěte se, že složka `temp/` a `log/` mají práva pro zápis.

## Přihlašovací údaje
* **Email:** `admin@test.cz`
* **Heslo:** `admin123`
