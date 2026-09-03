# Svinx\Console\Prompts

Module de prompts interactifs façon **Inquirer.js**, plus une **barre de
progression** pour suivre des téléchargements, pensé pour s'intégrer au
framework `Eorbah545 Console`.

## Emplacement

```
src/Prompts/
├── Terminal.php              (bas niveau : mode raw, touches, curseur)
├── Colors.php                (coloration ANSI)
├── Prompt.php                (façade principale)
├── ProgressBar.php           (barre de progression simple)
├── MultiProgressBar.php      (plusieurs barres empilées)
└── Question/
    ├── Question.php          (interface commune)
    ├── TextQuestion.php
    ├── PasswordQuestion.php
    ├── ConfirmQuestion.php
    ├── NumberQuestion.php
    ├── SelectQuestion.php
    └── MultiSelectQuestion.php
```

Namespace racine : `Svinx\Console\Prompts` (à adapter dans le PSR-4 de ton
`composer.json` si besoin — actuellement pensé pour un mapping du type
`"Svinx\\Console\\": "src/"`).

## Prompts

```php
use Svinx\Console\Prompts\Prompt;

$name = Prompt::text('Nom du projet ?', default: 'my-app');

$password = Prompt::password('Mot de passe ?', validate: fn ($v) =>
    strlen($v) >= 6 ? true : 'Au moins 6 caractères.'
);

$useGit = Prompt::confirm('Initialiser Git ?'); // O/n

$workers = Prompt::number('Nombre de workers ?', default: 4, min: 1, max: 32);

$driver = Prompt::select('Base de données ?', [
    ['label' => 'SQLite', 'value' => 'sqlite'],
    ['label' => 'MySQL', 'value' => 'mysql'],
]);

$features = Prompt::multiselect('Fonctionnalités ?', [
    ['label' => 'Cache', 'value' => 'cache', 'checked' => true],
    ['label' => 'Queue', 'value' => 'queue'],
]);
```

Ou en enchaînant plusieurs questions d'un coup, comme `inquirer.prompt()` :

```php
$answers = Prompt::ask([
    ['type' => 'text', 'name' => 'appName', 'message' => 'Nom de l\'app ?'],
    ['type' => 'confirm', 'name' => 'useDocker', 'message' => 'Docker ?', 'default' => true],
]);

echo $answers['appName'];
```

Types supportés : `text`, `password`, `confirm`, `number`, `select`,
`multiselect`. `select`/`multiselect` se pilotent aux flèches (↑/↓),
espace pour cocher, Entrée pour valider, Ctrl+C pour quitter proprement.

## Barre de progression

```php
use Svinx\Console\Prompts\ProgressBar;

$bar = ProgressBar::forDownload(totalBytes: 15_728_640, prefix: 'main.zip');

while (!feof($stream)) {
    $chunk = fread($stream, 8192);
    $bar->advance(strlen($chunk));
}

$bar->finish();
// main.zip [██████████░░░░░░░░░░] 7.5 Mb/15.0 Mb  50% 1.2 Mb/s ETA 6s
```

Trois modes d'unité :
- `unit: 'bytes'` (par défaut) → formatage auto en `o/Kb/Mb/Gb/Tb`, avec
  débit et ETA.
- `unit: 'percent'` → juste la barre + le pourcentage, sans compteur.
- `unit: 'fichiers'` (ou toute chaîne libre) → compteur `x/total unité`.

```php
$bar = new ProgressBar(total: 8, prefix: 'Installation', unit: 'paquets');
$bar->setProgress(3);
// Installation [███████░░░░░░░░░░░░░] 3/8 paquets  38%
```

Si la taille totale n'est connue qu'après le début du téléchargement
(ex : `Content-Length` lu au fil de l'eau), utilise `setTotal()`.

### Plusieurs téléchargements en parallèle

```php
use Svinx\Console\Prompts\MultiProgressBar;

$bars = new MultiProgressBar();
$bars->add('a', total: 5_000_000, prefix: 'fichier-a.zip');
$bars->add('b', total: 9_000_000, prefix: 'fichier-b.zip');

$bars->advance('a', 250_000);
$bars->advance('b', 450_000);

$bars->finishAll();
```

## Voir aussi

`examples/demo.php` : démo exécutable qui passe en revue tous les types de
prompts, une barre simple et deux barres empilées.

## Limites connues / pistes d'extension

- Le mode "raw" s'appuie sur `stty` (Linux/macOS) ; sous Windows les
  prompts `select`/`multiselect`/`password` retombent en mode ligne
  simple (pas de navigation aux flèches).
- Pas encore de prompt `autocomplete` (recherche filtrée dans une longue
  liste) — bon candidat pour une prochaine itération si besoin.
