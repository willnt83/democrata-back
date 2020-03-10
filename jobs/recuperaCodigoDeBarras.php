<?php
    set_time_limit(0);
    require 'database.php';

    $pdo = new Database();
    $offset = 0;
    echo "\nIniciando...";
    while(true){
        echo "\n\nOFFSET: ".$offset;
        sleep(1);
        $sql = '
            SELECT *
            FROM wmsprod_armazenagem_produtos ap
            where ap.pros = "N"
            ORDER BY ap.id
            LIMIT 1000
            OFFSET '.$offset.'
        ';

        $pdo->query($sql);
        $rowI = $pdo->multiple();
        if(count($rowI) > 0){
            foreach($rowI as $item => $row){
                echo "\n----\nid: ".$row->id;
                usleep(500);
                $sqlSai = '
                    SELECT count(*) qtd, cb.codigo, cb.id_produto
                    FROM pcp_codigo_de_barras cb
                    WHERE cb.id = :id_codigo
                    limit 1
                ';
                $pdo->query($sqlSai);
                $pdo->bind(':id_codigo', $row->id_codigo);
                $row2 = $pdo->single();

                $pdo2 = new Database();
                if($row2->qtd == 0){
                    echo "\nNAO ENCONTROU!!!";
                    //leep(100);
                }
                else{
                    echo "\nENCONTROU...";
                    echo "\nid_codigo: ".$row->id_codigo;
                    echo "\ncodigo: ".$row2->codigo;
                    //Retira do estoque
                    $sqlIns = '
                        update wmsprod_armazenagem_produtos
                        set
                            codigo = :codigo,
                            id_produto = :id_produto,
                            pros = "Y"
                        where
                            id_codigo = :id_codigo;
                    ';
                    $pdo2->query($sqlIns);
                    $pdo2->bind(':codigo', $row2->codigo);
                    $pdo2->bind(':id_produto', $row2->id_produto);
                    $pdo2->bind(':id_codigo', $row->id_codigo);
                    $pdo2->execute();
                    echo "\nAtualizando codigo: ".$row2->codigo;
                }
            }

            $offset += 1000;
        }
        else{
            echo "\nFIM";
            break;
        }
    }
?>