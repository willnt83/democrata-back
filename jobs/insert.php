<?php
    set_time_limit(0);
    require 'database.php';

    $pdo = new Database();

    $sql = '
        SELECT s.id, s.codigo
        FROM wmsprod_armazenagem_produtos s
        where s.id_codigo is null
    ';

    $pdo->query($sql);
    $rowI = $pdo->multiple();

    foreach($rowI as $item => $row){
        echo "\n----\nid: ".$row->id;
        echo "\ncodigo: ".$row->codigo;
        
        $sqlCod = '
            SELECT COUNT(*) qtd, id
            FROM pcp_codigo_de_barras cb
            WHERE cb.codigo = :codigo;
        ';
        $pdo->query($sqlCod);
        $pdo->bind(':codigo', $row->codigo);
        $row2 = $pdo->single();
        echo "\nid: ".$row2->id;
        

        $pdo2 = new Database();
        if($row2->qtd == 0){
            echo "\nqtd: ".$row2->qtd;
            /*$sqlDel = '
                delete from wmsprod_saida_produtos
                where codigo = :codigo;
            ';
            $pdo2->query($sqlDel);
            $pdo2->bind(':codigo', $row->codigo);
            $pdo2->execute();
            */
        }
        else{
            
            //insert
            $sqlIns = '
                update wmsprod_armazenagem_produtos
                set
                    id_codigo = '.$row2->id.'
                where
                    id = '.$row->id.';
            ';
            //echo "\nsqlIns: ".$sqlIns;
            $pdo2->query($sqlIns);
            $pdo2->execute();
            echo "\ninserido...";
        }
    }

?>