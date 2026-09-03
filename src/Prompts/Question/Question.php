<?php

namespace Svinx\Console\Prompts\Question;

interface Question
{
    /**
     * Pose la question dans le terminal et retourne la réponse déjà
     * validée / filtrée.
     */
    public function ask(): mixed;
}
