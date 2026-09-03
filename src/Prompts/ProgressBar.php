<?php

namespace Svinx\Console\Prompts;

/**
 * Barre de progression pour suivre un téléchargement ou toute tâche
 * mesurable (fichiers traités, étapes d'un job, etc.).
 *
 * Exemples :
 *
 *   // Téléchargement, avec unité "octets" formatée automatiquement en Kb/Mb/Gb
 *   $bar = ProgressBar::forDownload(totalBytes: 15_728_640, prefix: 'main.zip');
 *   while (...) {
 *       $bar->advance($chunkSize);
 *   }
 *   $bar->finish();
 *   // -> main.zip [██████████░░░░░░░░░░] 7.5 Mb/15.0 Mb  50% 1.2 Mb/s ETA 6s
 *
 *   // Compteur générique avec une unité libre
 *   $bar = new ProgressBar(total: 8, prefix: 'Installation des paquets', unit: 'paquets');
 *   $bar->setProgress(3);
 *   // -> Installation des paquets [███████░░░░░░░░░░░░░] 3/8 paquets  38%
 *
 *   // Juste un pourcentage, sans compteur
 *   $bar = new ProgressBar(total: 100, prefix: 'Build', unit: 'percent');
 */
class ProgressBar
{
    private int $current = 0;
    private float $startTime;
    private ?float $lastRenderTime = null;
    private bool $finished = false;

    /**
     * @param int    $total             Valeur cible (en octets, en unités, ou 100 pour un %).
     * @param string $prefix            Libellé affiché avant la barre (nom du fichier, de la tâche...).
     * @param string $unit              'bytes' pour un formatage auto Kb/Mb/Gb,
     *                                  'percent' pour n'afficher que le pourcentage,
     *                                  ou tout libellé libre ('fichiers', 'items'...).
     * @param bool   $showSpeedAndEta   Affiche le débit et le temps restant estimé (unité 'bytes' uniquement).
     */
    public function __construct(
        private int $total,
        private string $prefix = '',
        private string $unit = 'bytes',
        private int $width = 30,
        private string $completeChar = '█',
        private string $incompleteChar = '░',
        private bool $showSpeedAndEta = true,
    ) {
        $this->total = max(1, $total);
        $this->startTime = microtime(true);
    }

    public static function forDownload(int $totalBytes, string $prefix = 'Téléchargement'): self
    {
        return new self(total: max(1, $totalBytes), prefix: $prefix, unit: 'bytes');
    }

    public function advance(int $step = 1): void
    {
        $this->setProgress($this->current + $step);
    }

    /**
     * A utiliser quand la taille totale n'est connue qu'après coup
     * (ex : Content-Length récupéré au fil du téléchargement).
     */
    public function setTotal(int $total): void
    {
        $this->total = max(1, $total);
        $this->render(force: true);
    }

    public function setProgress(int $current): void
    {
        $this->current = max(0, min($current, $this->total));
        $this->render();
    }

    public function getProgress(): int
    {
        return $this->current;
    }

    public function finish(): void
    {
        if ($this->finished) {
            return;
        }

        $this->current = $this->total;
        $this->render(force: true);
        echo "\n";
        $this->finished = true;
    }

    /**
     * Construit la ligne formatée sans l'écrire dans le terminal.
     * Utilisé en interne par render(), et par MultiProgressBar pour
     * redessiner plusieurs barres empilées d'un coup.
     */
    public function renderLine(): string
    {
        $percent = max(0, min(1, $this->current / $this->total));
        $filled = (int) round($percent * $this->width);
        $bar = str_repeat($this->completeChar, $filled) . str_repeat($this->incompleteChar, $this->width - $filled);

        $elapsed = max(microtime(true) - $this->startTime, 0.001);
        $speed = $this->current / $elapsed;

        $parts = [];

        if ($this->prefix !== '') {
            $parts[] = $this->prefix;
        }

        $parts[] = '[' . Colors::paint($bar, 'cyan') . ']';

        $counter = $this->formatCounter();
        if ($counter !== '') {
            $parts[] = $counter;
        }

        $parts[] = sprintf('%3d%%', (int) round($percent * 100));

        if ($this->showSpeedAndEta && $this->unit === 'bytes' && $speed > 0 && !$this->finished) {
            $remaining = $this->total - $this->current;
            $eta = $remaining > 0 ? (int) ceil($remaining / $speed) : 0;
            $parts[] = $this->formatBytes($speed) . '/s';
            $parts[] = 'ETA ' . $this->formatDuration($eta);
        }

        return implode(' ', $parts);
    }

    private function render(bool $force = false): void
    {
        $now = microtime(true);

        // Limite à ~20 rendus/seconde pour ne pas saturer le terminal
        // sur des flux très rapides (petits chunks fréquents).
        if (!$force && $this->lastRenderTime !== null && ($now - $this->lastRenderTime) < 0.05) {
            return;
        }
        $this->lastRenderTime = $now;

        $this->write($this->renderLine());
    }

    private function formatCounter(): string
    {
        return match ($this->unit) {
            'percent' => '',
            'bytes' => $this->formatBytes($this->current) . '/' . $this->formatBytes($this->total),
            default => "{$this->current}/{$this->total} {$this->unit}",
        };
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['o', 'Kb', 'Mb', 'Gb', 'Tb'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return sprintf($i === 0 ? '%d %s' : '%.1f %s', $bytes, $units[$i]);
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        return "{$minutes}m{$seconds}s";
    }

    private function write(string $line): void
    {
        Terminal::moveToColumn0();
        Terminal::clearLine();
        echo $line;
    }
}
