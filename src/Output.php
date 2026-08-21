<?php
declare(strict_types=1);

namespace Svinx\Console;

final class Output
{
    public function __construct(private readonly bool $ansi=true) {}
    public function write(string $text=''): void { echo $text; }
    public function writeln(string $text=''): void { echo $text.PHP_EOL; }
    public function info(string $text): void { $this->writeln($this->style('INFO',$text,'36')); }
    public function success(string $text): void { $this->writeln($this->style('OK',$text,'32')); }
    public function warning(string $text): void { $this->writeln($this->style('WARNING',$text,'33')); }
    public function error(string $text): void { $this->writeln($this->style('ERROR',$text,'31')); }
    public function table(array $rows, array $headers=[]): void {
        $data=$headers?[ $headers, ...$rows ]:$rows; if(!$data)return;
        $widths=[]; foreach($data as $row)foreach(array_values($row) as $i=>$v)$widths[$i]=max($widths[$i]??0,strlen((string)$v));
        foreach($data as $ri=>$row){$line=[];foreach(array_values($row) as $i=>$v)$line[]=str_pad((string)$v,$widths[$i]??0);$this->writeln(rtrim(implode('  ',$line)));if($headers&&$ri===0)$this->writeln(implode('  ',array_map(fn($w)=>str_repeat('-',$w),$widths)));}
    }
    public function progress(int $current,int $total,int $width=30): void {
        $ratio=$total>0?min(1,$current/$total):1;$filled=(int)round($ratio*$width);
        $this->write("\r[".str_repeat('#',$filled).str_repeat(' ',$width-$filled)."] ".(int)round($ratio*100)."%");
        if($current>=$total)$this->writeln();
    }
    public function confirm(string $question,bool $default=false): bool {
        $suffix=$default?' [Y/n]':' [y/N]';$this->write($question.$suffix.' ');
        $answer=trim((string)fgets(STDIN)); if($answer==='')return $default;
        return in_array(strtolower($answer),['y','yes'],true);
    }
    public function ask(string $question, ?string $default=null): string {
        $suffix=$default!==null?" [{$default}]":'';$this->write($question.$suffix.': ');
        $answer=trim((string)fgets(STDIN)); return $answer===''&&$default!==null?$default:$answer;
    }
    private function style(string $label,string $text,string $code): string { return $this->ansi?"\033[{$code}m{$label}\033[0m {$text}":"[$label] $text"; }
}
