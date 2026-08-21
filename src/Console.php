<?php
declare(strict_types=1);

namespace Svinx\Console;

use InvalidArgumentException;
use Throwable;

final class Console
{
    private string $name='console';
    private ?string $description=null;
    private ?string $version=null;
    private array $commands=[];
    private array $aliases=[];
    private array $beforeHooks=[];
    private array $afterHooks=[];
    private bool $suggestions=true;

    public function name(string $name): self { $this->name=$name; return $this; }
    public function description(string $description): self { $this->description=$description; return $this; }
    public function version(string $version): self { $this->version=$version; return $this; }
    public function alias(string ...$aliases): self { foreach($aliases as $a)$this->aliases[$a]=$this->name; return $this; }
    public function before(callable $hook): self { $this->beforeHooks[]=$hook; return $this; }
    public function after(callable $hook): self { $this->afterHooks[]=$hook; return $this; }
    public function suggestions(bool $enabled=true): self { $this->suggestions=$enabled; return $this; }

    public function command(string $spec): Command
    {
        $cmd=new Command($spec);
        $this->commands[$cmd->name()]=$cmd;
        return $cmd;
    }
    public function addCommand(Command $command): self { $this->commands[$command->name()]=$command; return $this; }
    public function commands(): array { return $this->commands; }

    public function parse(?array $argv=null): int
    {
        $argv ??= array_slice($_SERVER['argv']??[],1);
        $output=new Output();
        try {
            if (!$argv || in_array($argv[0],['-h','--help'],true)) { $output->writeln($this->help()); return 0; }
            if (in_array($argv[0],['-V','--version'],true)) { $output->writeln($this->version??'unknown'); return 0; }

            $name=array_shift($argv);
            $command=$this->resolveCommand($name);
            if (!$command) {
                $hint=$this->suggestions ? $this->suggest($name) : null;
                throw new InvalidArgumentException("Unknown command: {$name}".($hint ? "\nDid you mean: {$hint}?" : ''));
            }
            foreach($this->beforeHooks as $hook)$hook($command,$argv,$output);
            $code=$command->run($argv,$output);
            foreach($this->afterHooks as $hook)$hook($command,$code,$output);
            return $code;
        } catch(Throwable $e) { $output->error($e->getMessage()); return 1; }
    }

    public function parseAsync(?array $argv=null): int { return $this->parse($argv); }

    private function resolveCommand(string $name): ?Command
    {
        if(isset($this->commands[$name])) return $this->commands[$name];
        foreach($this->commands as $command) if(in_array($name,$command->aliases(),true)) return $command;
        return null;
    }

    private function suggest(string $name): ?string
    {
        $best=null;$distance=PHP_INT_MAX;
        foreach(array_keys($this->commands) as $candidate){
            $d=levenshtein($name,$candidate);
            if($d<$distance){$distance=$d;$best=$candidate;}
        }
        return ($best!==null && $distance<=max(2,(int)floor(strlen($name)/2))) ? $best : null;
    }

    public function help(): string
    {
        $out=[$this->name.($this->version?" {$this->version}":'')];
        if($this->description)$out[]=$this->description;
        $out[]='';$out[]='Usage:';$out[]="  {$this->name} <command> [options]";$out[]='';$out[]='Commands:';
        $groups=[];
        foreach($this->commands as $c) if(!$c->isHidden()) $groups[$c->getGroup()??''][]=$c;
        foreach($groups as $group=>$commands){
            if($group!=='')$out[]="  {$group}:";
            foreach($commands as $c)$out[]=sprintf('  %-24s %s',$c->name(),$c->getSummary()??$c->getDescription()??'');
        }
        $out[]='';$out[]='Options:';$out[]='  -h, --help             Show help';$out[]='  -V, --version          Show version';
        return implode(PHP_EOL,$out).PHP_EOL;
    }
}
