<?php

class ArmazenagemInsumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getArmazenagens($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'a.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT a.id id_armazenagem, a.id_usuario, u.nome nome_usuario, a.dthr_armazenagem
            FROM pcp_armazenagens a
            JOIN pcp_usuarios u ON u.id = a.id_usuario
            '.$where.'
            ORDER BY a.id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'idArmazenagem' => (int)$row->id_armazenagem,
                'usuario' => array(
                    'id' => (int)$row->id_usuario,
                    'nome' => $row->nome_usuario,
                ),
                'dthrArmazenagem' => $row->dthr_armazenagem
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    // Recupera todos os insumos entrados mas ainda não armazenados
    public function getInsumosArmazenar($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'ia.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                p.id id_pedido,
                eins.id id_entrada_insumo,
                ins.id id_insumo,
                ins.nome nome_insumo,
                ins.ins ins_insumo,
                eins.quantidade quantidade_entrada,
                sum(ai.quantidade) quantidade_armazenada,
                ent.dthr_entrada
            FROM pcp_entrada_insumos eins
            JOIN pcp_entradas ent ON ent.id = eins.id_entrada
            JOIN pcp_pedidos_insumos pins ON pins.id = eins.id_pedido_insumo
            JOIN pcp_pedidos p ON p.id = pins.id_pedido
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            LEFT JOIN pcp_armazenagem_insumos ai ON ai.id_entrada_insumo = eins.id
            '.$where.'
            GROUP BY eins.id_pedido_insumo
            ORDER BY eins.id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $quantidadeArmazenada = $row->quantidade_armazenada !== null ? (int)$row->quantidade_armazenada : 0;
            $quantidadeArmazenar = (float)$row->quantidade_entrada - $quantidadeArmazenada;

            $responseData[] = array(
                'idPedido' => (int)$row->id_pedido,
                'idEntradaInsumo' => (int)$row->id_entrada_insumo,
                'insumo' => array(
                    'id' => (int)$row->id_insumo,
                    'nome' => $row->nome_insumo,
                    'ins' => $row->ins_insumo,
                    'quantidadeEntrada' => (float)$row->quantidade_entrada,
                    'quantidadeArmazenar' => $quantidadeArmazenar,
                    'dataEntrada' => $row->dthr_entrada
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    // Recupera as informações de entrada
    public function getInsumosArmazenados($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'ai.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                ai.id_entrada_insumo,
                pins.id_insumo,
                ins.nome nome_insumo,
                ins.ins ins_insumo,
                um.unidade unidadeMedida,
                ai.id_almoxarifado,
                a.nome nomeAlmoxarifado,
                ai.id_posicao,
                pa.posicao nomePosicao,
                ai.quantidade,
                ei.quantidade quantidade_entrada,
                totais.totalQuantidadeArmazenada quantidade_total_armazenada,
                ei.dthr_entrada
            FROM pcp_armazenagem_insumos ai
            JOIN pcp_almoxarifado a ON a.id = ai.id_almoxarifado
            JOIN pcp_posicao_armazem pa ON pa.id = ai.id_posicao AND pa.id_almoxarifado = ai.id_almoxarifado
            JOIN pcp_entrada_insumos ei ON ei.id = ai.id_entrada_insumo
            JOIN (
                SELECT ai2.id_entrada_insumo, SUM(ai2.quantidade) totalQuantidadeArmazenada
                FROM pcp_armazenagem_insumos ai2
                GROUP BY ai2.id_entrada_insumo
            ) AS totais ON totais.id_entrada_insumo = ai.id_entrada_insumo
            JOIN pcp_pedidos_insumos pins ON pins.id = ei.id_pedido_insumo
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            JOIN pcp_unidades_medida um ON um.id = ins.id_unidade_medida
            '.$where.'
            GROUP BY ai.id
            ORDER BY ei.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $quantidadeArmazenada = $row->quantidade_total_armazenada !== null ? (int)$row->quantidade_total_armazenada : 0;
            $quantidadeArmazenar = (float)$row->quantidade_entrada - $quantidadeArmazenada;

            $responseData[] = array(
                'idEntradaInsumo' => (int)$row->id_entrada_insumo,
                'insumo' => array(
                    'id' => (int)$row->id_insumo,
                    'nome' => $row->nome_insumo,
                    'ins' => $row->ins_insumo,
                    'unidadeMedida' => $row->unidadeMedida,
                    'idAlmoxarifado' => (int)$row->id_almoxarifado,
                    'nomeAlmoxarifado' => $row->nomeAlmoxarifado,
                    'idPosicao' => (int)$row->id_posicao,
                    'nomePosicao' => $row->nomePosicao,
                    'quantidade' => (float)$row->quantidade,
                    'quantidadeEntrada' => (float)$row->quantidade_entrada,
                    'quantidadeArmazenar' => $quantidadeArmazenar,
                    'dataRecebimento' => $row->dthr_entrada
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }    

    public function createUpdateArmazenagem($request){
        try{
            // Validações
            $i = 0;
            while($i < count($request['lancamentos'])){
                if(
                    !array_key_exists('idEntradaInsumo', $request['lancamentos'][$i])
                    or $request['lancamentos'][$i]['idEntradaInsumo'] === ''
                    or $request['lancamentos'][$i]['idEntradaInsumo'] === null
                )
                    throw new \Exception('Campo idEntradaInsumo é obrigatório.');
                if(
                    !array_key_exists('idAlmoxarifado', $request['lancamentos'][$i])
                    or $request['lancamentos'][$i]['idAlmoxarifado'] === ''
                    or $request['lancamentos'][$i]['idAlmoxarifado'] === null
                )
                    throw new \Exception('Campo idAlmoxarifado é obrigatório.');
                
                if(!array_key_exists('idPosicao', $request['lancamentos'][$i])
                    or $request['lancamentos'][$i]['idPosicao'] === ''
                    or $request['lancamentos'][$i]['idPosicao'] === null
                )
                    throw new \Exception('Campo idPosicao é obrigatório.');
                
                if(!array_key_exists('quantidade', $request['lancamentos'][$i])
                    or $request['lancamentos'][$i]['quantidade'] === ''
                    or $request['lancamentos'][$i]['quantidade'] === null
                )
                    throw new \Exception('Campo quantidade é obrigatório.');

                $i++;
            }
            // Edit
            if($request['idArmazenagem']){
                $sql = '
                    update pcp_armazenagens
                    set
                        dthr_armazenagem = now(),
                        id_usuario = :idUsuario
                    where id = :idArmazenagem;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->bindParam(':idArmazenagem', $request['idArmazenagem']);
                $stmt->execute();
                $idArmazenagem = $request['idArmazenagem'];
                $msg = 'Armazenagem atualizada com sucesso.';
            }
            // Insert
            else{
                $sql = '
                    insert into pcp_armazenagens
                    set
                        dthr_armazenagem = now(),
                        id_usuario = :idUsuario
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->execute();
                $idArmazenagem = $this->pdo->lastInsertId();
                $msg = 'Armazenagem registrada com sucesso.';
            }
            
            // Removendo todos as armazenagens realizadas para o insumo do pedido
            $sqlDelete = '
                delete from pcp_armazenagem_insumos
                where id_armazenagem = :idArmazenagem;
            ';
            $stmt = $this->pdo->prepare($sqlDelete);
            $stmt->bindParam(':idArmazenagem', $idArmazenagem);
            $stmt->execute();

            // Inserindo registros de armazenagem
            $i = 0;
            while($i < count($request['lancamentos'])){
                $sql = '
                    insert into pcp_armazenagem_insumos
                    set
                        id_armazenagem = :idArmazenagem,
                        id_entrada_insumo = :idEntradaInsumo,
                        id_almoxarifado = :idAlmoxarifado,
                        id_posicao = :idPosicao,
                        quantidade = :quantidade
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idArmazenagem', $idArmazenagem);
                $stmt->bindParam(':idEntradaInsumo', $request['lancamentos'][$i]['idEntradaInsumo']);
                $stmt->bindParam(':idAlmoxarifado', $request['lancamentos'][$i]['idAlmoxarifado']);
                $stmt->bindParam(':idPosicao', $request['lancamentos'][$i]['idPosicao']);
                $stmt->bindParam(':quantidade', $request['lancamentos'][$i]['quantidade']);
                $stmt->execute();
                $i++;
            }
            // Reponse
            return json_encode(array(
                'success' => true,
                'msg' => $msg
            ));
        }catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function generateImage($img, $file){
        $file_parts = explode('/', $file);
        if(!is_dir($file_parts[0].'/'.$file_parts[1])){
            mkdir($file_parts[0].'/'.$file_parts[1], 0777, true);
        }

        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        
        file_put_contents($file, $image_base64);
        return $file;
    }

    public function geracaoEtiquetasArmazenagem($request){
        require('../vendor/fpdf/fpdf.php');
        $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

        $pdf = new FPDF();
        $i = 0;
        $etiquetas = array();
        $todayDT = new Datetime();
        $today = $todayDT->format('Ymd_His');

        while($i < count($request)){
            $x = 1;
            while($x <= (int)$request[$i]['quantidadeEtiquetas']){
                $etiquetas[] = $request[$i];
                $x++;
            }
            $i++;
        }
        
        $i = 0;
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);

        while($i < count($etiquetas)){
            $dataRecebimento1DT = new Datetime($etiquetas[$i]['dataRecebimento']);
            $dataRecebimento1 = $dataRecebimento1DT->format('d/m/Y H:i:s');
            if(($i+1) < count($etiquetas)){
                $proximo = true;
                $dataRecebimento2DT = new Datetime($etiquetas[$i+1]['dataRecebimento']);
                $dataRecebimento2 = $dataRecebimento2DT->format('d/m/Y H:i:s');
            }
            else $proximo = false;

            $pdf->Cell(45, 7.5, utf8_decode('CÓDIGO'), 'LTR', 0, 'L');
            $pdf->Cell(50, 7.5, $etiquetas[$i]['codigo'], 'LTR', 0, 'L');
            if($proximo){
                $pdf->Cell(2, 7.5, '', 0, 0, 'C');
                $pdf->Cell(45, 7.5, utf8_decode('CÓDIGO'), 'LTR', 0, 'L');
                $pdf->Cell(50, 7.5, $etiquetas[$i+1]['codigo'], 'LTR', 0, 'L');
            }
            $pdf->Ln();

            $pdf->Cell(45, 7.5, 'NOME', 'LTR', 0, 'L');
            $pdf->Cell(50, 7.5, utf8_decode($etiquetas[$i]['nome']), 'LTR', 0, 'L');
            if($proximo){
                $pdf->Cell(2, 7.5, '', 0, 0, 'C');
                $pdf->Cell(45, 7.5, 'NOME', 'LTR', 0, 'L');
                $pdf->Cell(50, 7.5, utf8_decode($etiquetas[$i+1]['nome']), 'LTR', 0, 'L');
            }
            $pdf->Ln();

            $pdf->Cell(45, 7.5, utf8_decode('LOCAL FÍSICO'), 'LTR', 0, 'L');
            $pdf->Cell(50, 7.5, utf8_decode($etiquetas[$i]['localFisico']), 'LTR', 0, 'L');
            if($proximo){
                $pdf->Cell(2, 7.5, '', 0, 0, 'C');
                $pdf->Cell(45, 7.5, utf8_decode('LOCAL FÍSICO'), 'LTR', 0, 'L');
                $pdf->Cell(50, 7.5, utf8_decode($etiquetas[$i+1]['localFisico']), 'LTR', 0, 'L');
            }
            $pdf->Ln();

            $pdf->Cell(45, 7.5, 'DATA DE RECEBIMENTO', 'LTR', 0, 'L');
            $pdf->Cell(50, 7.5, $dataRecebimento1, 'LTR', 0, 'L');
            if($proximo){
                $pdf->Cell(2, 7.5, '', 0, 0, 'C');
                $pdf->Cell(45, 7.5, 'DATA DE RECEBIMENTO', 'LTR', 0, 'L');
                $pdf->Cell(50, 7.5, $dataRecebimento2, 'LTR', 0, 'L');
            }
            $pdf->Ln();

            $pdf->Cell(45, 7.5, 'QUANTIDADE', 'LTRB', 0, 'L');
            $pdf->Cell(50, 7.5, $etiquetas[$i]['quantidade'], 'LTR', 0, 'L');
            if($proximo){
                $pdf->Cell(2, 7.5, '', 0, 0, 'C');
                $pdf->Cell(45, 7.5, 'QUANTIDADE', 'LTRB', 0, 'L');
                $pdf->Cell(50, 7.5, $etiquetas[$i+1]['quantidade'], 'LTR', 0, 'L');
            }
            $pdf->Ln();

            $pdf->Cell(45, 7.5, 'UNIDADE DE MEDIDA', 'LTRB', 0, 'L');
            $pdf->Cell(50, 7.5, utf8_decode($etiquetas[$i]['unidadeMedida']), 'LTRB', 0, 'L');
            if($proximo){
                $pdf->Cell(2, 7.5, '', 0, 0, 'C');
                $pdf->Cell(45, 7.5, 'UNIDADE DE MEDIDA', 'LTRB', 0, 'L');
                $pdf->Cell(50, 7.5, utf8_decode($etiquetas[$i+1]['unidadeMedida']), 'LTRB', 0, 'L');
            }
            $pdf->Ln();

            // Código de Barras
            $cod = $etiquetas[$i]['idInsumo'].'-'.$etiquetas[$i]['idAlmoxarifado'].'-'.$etiquetas[$i]['idPosicao'];
            $strBase64 = 'data:image/png;base64,'.base64_encode($generator->getBarcode($cod, $generator::TYPE_CODE_128));
            $img = $this->generateImage($strBase64, 'etiquetasArmazenagem/images/'.$cod.'.png');
          
            $pdf->Cell(45, 12, utf8_decode('Código: '.$cod), 'LTRB', 0, 'L');
            $pdf->Cell(50, 12, $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTRB', 0, 'L', false);
            if($proximo){
                $pdf->Cell(2, 12, '', 0, 0, 'C');
                
                $cod = $etiquetas[$i]['idInsumo'].'-'.$etiquetas[$i+1]['idAlmoxarifado'].'-'.$etiquetas[$i+1]['idPosicao'];
                $strBase64 = 'data:image/png;base64,'.base64_encode($generator->getBarcode($cod, $generator::TYPE_CODE_128));
                $img = $this->generateImage($strBase64, 'etiquetasArmazenagem/images/'.$cod.'.png');
                $pdf->Cell(45, 12, utf8_decode('Código: '.$cod), 'LTRB', 0, 'L');
                $pdf->Cell(50, 12, $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTRB', 0, 'L', false);

            }
            $pdf->Ln();
            $pdf->Ln();
            $i += 2;
        }
        $path = 'etiquetasArmazenagem/etiquetaArmazenagem-'.$today.'.pdf';
        
        $pdf->Output('F', $path, true);

        return json_encode(array(
            'success' => true,
            'payload' => array(
                'url' => $path
            )
        ));
    }
}