<?php
    set_time_limit(0);
    require 'database.php';

    $pdo = new Database();

    $sql = '
        SELECT cb.id,cb.codigo, count(cb.codigo) repetidos, cb.lancado
        FROM pcp_codigo_de_barras cb
        GROUP BY cb.codigo
        limit 10000;
    ';
    $pdo->query($sql);
    $row = $pdo->multiple();
    $count = 0;
    foreach($row as $item => $obj){
        echo "\n-----------------count: ".$count."\n";

        if($obj->repetidos > 1){
            echo "\nid: ".$obj->id."| codigo: ".$obj->codigo." possui ".$obj->repetidos;
            $sql = '
                select *
                from pcp_codigo_de_barras cb
                where
                    cb.codigo = :codigo
                    and (
                        lancado = "Y"
                        or conferido = "Y"
                    )
                ;
            ';
            $pdo->query($sql);
            $pdo->bind(':codigo', $obj->codigo);
            $row2 = $pdo->multiple();
            if($pdo->rowCount() > 0){
                echo "\nrepetido\n";
                print_r($row2);
            }
            else{
                echo "\nrepetido mas sem lancamento...";
                echo "\nremovendo duplicados...";
                $sqlNoLanc = '
                    select *
                    from pcp_codigo_de_barras cb
                    where
                        cb.codigo = :codigo
                    order by cb.codigo;
                ';
                $pdo->query($sqlNoLanc);
                $pdo->bind(':codigo', $obj->codigo);
                $rowNoLanc = $pdo->multiple();
                $i = 0;
                foreach($rowNoLanc as $itemNoLanc => $objNoLanc){
                    if($i > 0){
                        $sqlDel = '
                            delete from pcp_codigo_de_barras
                            where id = :id;
                        ';
                        $pdo->query($sqlDel);
                        $pdo->bind(':id', $objNoLanc->id);
                        $pdo->execute();
                        echo "\nregistro id: ".$objNoLanc->id." removido.";
                    }
                    $i++;
                }
            }
        }
        else{
            echo "\nNão é repetido, ok...";
        }
        $count++;
    }
?>