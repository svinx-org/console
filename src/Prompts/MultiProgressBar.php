<?php

namespace Svinx\Console\Prompts;

/**
 * Affiche et met à jour plusieurs barres de progression empilées en
 * même temps (ex : plusieurs fichiers téléchargés en parallèle).
 *
 * Exemple :
 *   $bars = new MultiProgressBar();
 *   $bars->add('file1', total: 5_000_000, prefix: 'fichier1.zip');
 *   $bars->add('file2', total: 12_000_000, prefix: 'fichier2.zip');
 *
 *   $bars->advance('file1', 1024);
 *   $bars->advance('file2', 2048);
 *
 *   $bars->finish('file1');
 *   $bars->finishAll();
 */
class MultiProgressBar
{
    /** @var array<string, ProgressBar> */
    private array $bars = [];
    private bool $rendered = false;

    public function add(
        string $key,
        int $total,
        string $prefix = '',
        string $unit = 'bytes',
        bool $showSpeedAndEta = true,
    ): ProgressBar {
        $bar = new ProgressBar(
            total: $total,
            prefix: $prefix,
            unit: $unit,
            showSpeedAndEta: $showSpeedAndEta,
        );

        $this->bars[$key] = $bar;
        $this->render();

        return $bar;
    }

    public function advance(string $key, int $step = 1): void
    {
        $this->get($key)->advance($step);
        $this->render();
    }

    public function setProgress(string $key, int $current): void
    {
        $this->get($key)->setProgress($current);
        $this->render();
    }

    public function finish(string $key): void
    {
        $this->get($key)->finish();
        $this->render();
    }

    public function finishAll(): void
    {
        foreach ($this->bars as $bar) {
            $bar->finish();
        }
        $this->render();
    }

    private function get(string $key): ProgressBar
    {
        if (!isset($this->bars[$key])) {
            throw new \InvalidArgumentException("Aucune barre de progression enregistrée sous la clé \"{$key}\".");
        }

        return $this->bars[$key];
    }

    /**
     * Redessine toutes les barres. Chaque ProgressBar gère elle-même le
     * throttling de son propre rendu ; ici on se contente de replacer le
     * curseur au bon endroit avant de tout réafficher.
     */
    private function render(): void
    {
        if ($this->rendered) {
            Terminal::cursorUp(count($this->bars));
        }

        foreach ($this->bars as $bar) {
            Terminal::moveToColumn0();
            Terminal::clearLine();
            // On force le rendu individuel en passant par setProgress(current)
            // pour contourner le throttle interne lors du redraw groupé.
            echo $bar->renderLine() . "\n";
        }

        $this->rendered = true;
    }
}
