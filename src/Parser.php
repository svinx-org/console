<?php
declare(strict_types=1);

namespace Svinx\Console;

use InvalidArgumentException;

final class Parser
{
    public static function parseCommand(Command $command, array $argv): array
    {
        $args = []; $options = []; $positionals = []; $help = false;
        $byLong = []; $byShort = [];

        foreach ($command->options() as $option) {
            $byLong[$option->long] = $option;
            if ($option->short) $byShort[ltrim($option->short, '-')] = $option;
            $options[$option->long] = $option->default;
        }

        for ($i=0,$n=count($argv); $i<$n; $i++) {
            $token=$argv[$i];
            if ($token==='--') { array_push($positionals,...array_slice($argv,$i+1)); break; }
            if ($token==='-h'||$token==='--help') { $help=true; continue; }

            if (str_starts_with($token,'--')) {
                $raw=substr($token,2); $value=null;
                if (str_contains($raw,'=')) [$raw,$value]=explode('=',$raw,2);
                $negated=false;
                if (str_starts_with($raw,'no-') && isset($byLong[substr($raw,3)]) && $byLong[substr($raw,3)]->negatable) {
                    $raw=substr($raw,3); $negated=true;
                }
                if (!isset($byLong[$raw])) throw new InvalidArgumentException("Unknown option: --{$raw}");
                $o=$byLong[$raw];
                if ($o->negatable && !$o->takesValue()) { $options[$o->long]=!$negated; continue; }
                if ($o->takesValue()) {
                    $values=[];
                    if ($value!==null) $values[]=$value;
                    if ($o->variadic) {
                        while ($i+1<$n && !str_starts_with($argv[$i+1],'-')) $values[]=$argv[++$i];
                        $options[$o->long]=$o->process($values);
                    } else {
                        if ($value===null) {
                            if (!isset($argv[$i+1])) throw new InvalidArgumentException("Option --{$raw} expects a value.");
                            $value=$argv[++$i];
                        }
                        $options[$o->long]=$o->process($value);
                    }
                } else $options[$o->long]=true;
                continue;
            }

            if (str_starts_with($token,'-') && $token!=='-') {
                $shorts=substr($token,1);
                if (isset($byShort[$shorts])) {
                    $o=$byShort[$shorts];
                    if ($o->takesValue()) {
                        $value=null;
                        if (strlen($shorts)>1) $value=substr($shorts,1);
                        if ($o->variadic) {
                            $values=[]; if ($value!==null) $values[]=$value;
                            while ($i+1<$n && !str_starts_with($argv[$i+1],'-')) $values[]=$argv[++$i];
                            $options[$o->long]=$o->process($values);
                        } else {
                            if ($value===null) { if (!isset($argv[$i+1])) throw new InvalidArgumentException("Option {$o->short} expects a value."); $value=$argv[++$i]; }
                            $options[$o->long]=$o->process($value);
                        }
                    } else $options[$o->long]=true;
                    continue;
                }

                foreach (str_split($shorts) as $s) {
                    if (!isset($byShort[$s])) throw new InvalidArgumentException("Unknown option: -{$s}");
                    $o=$byShort[$s];
                    if ($o->takesValue()) throw new InvalidArgumentException("Option {$o->short} requires a value and cannot be grouped.");
                    $options[$o->long]=true;
                }
                continue;
            }
            $positionals[]=$token;
        }

        $argumentDefs=$command->argumentsList();
        $posIndex=0;
        foreach ($argumentDefs as $arg) {
            if ($arg->variadic) {
                $value=array_slice($positionals,$posIndex);
                if (!$value && $arg->required && !$help) throw new InvalidArgumentException("Missing argument: {$arg->name}");
                $args[$arg->name]=$arg->validate($value ?: $arg->default);
                $posIndex=count($positionals);
                continue;
            }
            if (array_key_exists($posIndex,$positionals)) $args[$arg->name]=$arg->validate($positionals[$posIndex++]);
            elseif (!$help && $arg->required) throw new InvalidArgumentException("Missing argument: {$arg->name}");
            else $args[$arg->name]=$arg->default;
        }

        if (!$help && $posIndex<count($positionals) && !$command->allowsExcessArguments()) {
            throw new InvalidArgumentException("Too many arguments.");
        }

        foreach ($command->options() as $o) {
            if (!$help && $o->required && $options[$o->long] === $o->default) {
                throw new InvalidArgumentException("Required option '--{$o->long}' not specified.");
            }
        }

        return [new Input($args,$options,array_slice($positionals,$posIndex)),$help];
    }
}
