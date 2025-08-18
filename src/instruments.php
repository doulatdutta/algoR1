<?php
// Load Upstox master.csv and find contracts by expiry/strike/type
class Instruments {
    private $csvPath;
    private $rows=[];
    public function __construct($csvPath){
        $this->csvPath = $csvPath;
        if (!file_exists($csvPath)) throw new Exception('Instrument CSV missing at '.$csvPath);
        $this->load();
    }
    private function load(){
        $h = fopen($this->csvPath,'r');
        $head = fgetcsv($h);
        while(($r=fgetcsv($h))!==false){
            if (count($r)!=count($head)) continue;
            $this->rows[] = array_combine($head,$r);
        }
        fclose($h);
    }
    public function next_weekly_thu(){
        $now = new DateTime('now');
        $dow = (int)$now->format('N'); // 1 Mon ... 7 Sun
        $add = 4 - $dow; if ($add<0) $add+=7;
        $d = clone $now; $d->modify("+$add day");
        return $d->format('Ymd');
    }
    public function month_expiry_thu(){
        $now = new DateTime('now');
        $y=(int)$now->format('Y'); $m=(int)$now->format('m');
        $d = new DateTime('last day of '.$y.'-'.$m);
        while((int)$d->format('N')!==4) $d->modify('-1 day');
        $cand=$d->format('Ymd');
        if ((int)$cand < (int)$now->format('Ymd')){
            $m+=1; if($m>12){$m=1;$y+=1;}
            $d = new DateTime('last day of '.$y.'-'.$m);
            while((int)$d->format('N')!==4) $d->modify('-1 day');
            $cand=$d->format('Ymd');
        }
        return $cand;
    }
    public function find_option($ulCode,$expiryYmd,$strike,$type,$exchange='NSE_FO'){
        $type=strtoupper($type); // CE/PE
        foreach($this->rows as $r){
            $ex = strtoupper($r['exchange'] ?? $r['Exchange'] ?? '');
            $instType = strtoupper($r['instrument_type'] ?? $r['Instrument Type'] ?? '');
            $sym = strtoupper($r['tradingsymbol'] ?? $r['Trading Symbol'] ?? '');
            $optType = strtoupper($r['option_type'] ?? $r['Option Type'] ?? '');
            $expiry = preg_replace('/[^0-9]/','', $r['expiry'] ?? $r['Expiry'] ?? '');
            $strikeCsv = (int)($r['strike_price'] ?? $r['Strike Price'] ?? 0);
            if ($ex !== strtoupper($exchange)) continue;
            if (!($instType==='OPTIDX' || $instType==='OPTSTK')) continue;
            if (strpos($sym, strtoupper($ulCode)) === false) continue;
            if ($expiry !== $expiryYmd) continue;
            if ((int)$strike !== $strikeCsv) continue;
            if ($optType !== $type) continue;
            return $r;
        }
        return null;
    }
}
?>
