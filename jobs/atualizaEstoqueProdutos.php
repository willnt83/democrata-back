<?php
    set_time_limit(0);
    require 'database.php';

    $pdo = new Database();
    $offset = 0;
    echo "\nIniciando...";
    while(true){
        $sql = '
            SELECT *
            FROM wmsprod_armazenagem_produtos ap
            where pros = "N"
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
                    SELECT COUNT(*) qtd, sai.id
                    FROM wmsprod_saida_produtos sai
                    WHERE sai.id_codigo = :id_codigo;
                ';
                $pdo->query($sqlSai);
                $pdo->bind(':id_codigo', $row->id_codigo);
                $row2 = $pdo->single();

                $pdo2 = new Database();
                if($row2->qtd == 0){
                    echo "\nNAO ENCONTROU!!!";
                    //Repondo o estoque
                    $sqlIns = '
                        update wmsprod_armazenagem_produtos
                        set
                            estoque = "Y",
                            pros = "Y"
                        where
                            id_codigo = '.$row->id_codigo.';
                    ';
                    $pdo2->query($sqlIns);
                    $pdo2->execute();
                    echo "\nRepondo no estoque: ".$row->id_codigo;
                }
                else{
                    echo "\nENCONTROU...";
                    //Retira do estoque
                    $sqlIns = '
                        update wmsprod_armazenagem_produtos
                        set
                            estoque = "N",
                            pros = "Y"
                        where
                            id_codigo = '.$row->id_codigo.';
                    ';
                    $pdo2->query($sqlIns);
                    $pdo2->execute();
                    echo "\nRetirando do estoque: ".$row->id_codigo;
                }
            }

            $offset += 1000;
            echo "\n\nOFFSET: ".$offset;
            sleep(1);
        }
        else{
            echo "\nFIM";
            break;
        }
    }
?>