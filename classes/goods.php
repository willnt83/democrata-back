<?php
class Goods{

    /*
    Recebe array de filtros e retorna string where para query
    in: array
    out: string
    */
    public function buildFilters($filters){
        $str = 'where ';
        $i = 0;
        foreach($filters as $key => $value){
            $and = $i === 0 ? '' : ' and ';
            $quote = is_numeric($value) ? "" : '"';
            $str .= $and.$key.' = '.$quote.$value.$quote;
            $i++;
        }
        return $str;
    }

    /*
    Recebe array de key-value e retorna uma string para query de insert ou update
    in: array
    out: string
    */
    public function buildAttribution($attributes){
        $str = 'set ' ;
        $i = 0;
        foreach($attributes as $key => $value){
            if($value !== null){
                
                $comma = $i > 0 ? ', ' : '';
                $quote = is_numeric($value) ? "" : '"';
                $str .= $comma.$key.' = '.$quote.$value.$quote;
                $i++;
            }
        }
        return $str;
    }

    /*
    Recebe array de objetos key-value e retorna a posição no vetor de uma determinada quey
    in: array, int
    out: int ou false
    
    public function searchIndexbyKey($objArray, $searchingKey){
        $i = 0;
        foreach($objArray as $key => $value){
            if($searchingKey === $key)
                return $i;
            $i++;
        }
        return false;
    }
    */
}