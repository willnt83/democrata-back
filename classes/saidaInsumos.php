<?php

class SaidaInsumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getSaidas($filters){
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
            SELECT a.id id_saida, a.id_usuario, u.nome nome_usuario, a.dthr_saida
            FROM pcp_saidas a
            JOIN pcp_usuarios u ON u.id = a.id_usuario
            '.$where.'
            ORDER BY a.id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'idSaida' => (int)$row->id_saida,
                'usuario' => array(
                    'id' => (int)$row->id_usuario,
                    'nome' => $row->nome_usuario,
                ),
                'dthrSaida' => $row->dthr_saida
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getInsumosRetirados($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'si.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                si.id_saida,
                si.id id_saida_insumo,
                si.id_armazenagem_insumos,
                ins.id id_insumo,
                ins.ins ins_insumo,
                ins.nome nome_insumo,
                si.id_saida,
                si.id_almoxarifado,
                a.nome nome_almoxarifado,
                si.id_posicao,
                pa.posicao nome_posicao,
                si.quantidade quantidade_retirada
            FROM pcp_saida_insumos si
            JOIN pcp_almoxarifado a ON a.id = si.id_almoxarifado
            JOIN pcp_posicao_armazem pa ON pa.id = si.id_posicao
            JOIN pcp_armazenagem_insumos ai ON ai.id = si.id_armazenagem_insumos
            JOIN pcp_pedidos_insumos pins ON pins.id = ai.id_pedido_insumo
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            '.$where.'
            ORDER BY si.id asc
            ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            /*
            $quantidadeArmazenada = $row->quantidade_total_armazenada !== null ? (int)$row->quantidade_total_armazenada : 0;
            $quantidadeArmazenar = (int)$row->quantidade_entrada - $quantidadeArmazenada;
            */

            $responseData[] = array(
                'idSaida' => (int)$row->id_saida,
                'idSaidaInsumo' => (int)$row->id_saida_insumo,
                'idArmazenagemInsumo' => (int)$row->id_armazenagem_insumos,
                'insumo' => array(
                    'id' => (int)$row->id_insumo,
                    'nome' => $row->nome_insumo,
                    'ins' => $row->ins_insumo,
                    'idAlmoxarifado' => (int)$row->id_almoxarifado,
                    'nomeAlmoxarifado' => $row->nome_almoxarifado,
                    'idPosicao' => (int)$row->id_posicao,
                    'nomePosicao' => $row->nome_posicao,
                    'quantidadeRetirada' => (int)$row->quantidade_retirada
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    // Recupera as informações de entrada
    public function getInsumosDisponiveisParaSaida($filters){
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
                ai.id id_armazenagem_insumo,
                ai.id_entrada_insumo,
                ins.id id_insumo,
                ins.ins ins_insumo,
                ins.nome nome_insumo,
                ai.id_almoxarifado,
                al.nome nome_almoxarifado,
                ai.id_posicao,
                pa.posicao nome_posicao,
                ai.quantidade quantidade_armazenada,
                totais.quantidade_retirada,
                (ai.quantidade - if(totais.quantidade_retirada IS NOT NULL, totais.quantidade_retirada, 0)) quantidade_disponivel,
                e.dthr_entrada
            FROM pcp_armazenagem_insumos ai
            JOIN pcp_armazenagens a ON a.id = ai.id_armazenagem
            JOIN pcp_almoxarifado al ON al.id = ai.id_almoxarifado
            JOIN pcp_posicao_armazem pa ON pa.id = ai.id_posicao
            JOIN pcp_entrada_insumos eins ON eins.id = ai.id_entrada_insumo
            JOIN pcp_pedidos_insumos pins ON pins.id = eins.id_pedido_insumo
            JOIN pcp_insumos ins ON ins.id = pins.id_insumo
            JOIN pcp_entradas e ON e.id = eins.id_entrada
            left JOIN (
                SELECT si.id_armazenagem_insumos, SUM(si.quantidade) quantidade_retirada
                FROM pcp_saida_insumos si
                GROUP BY si.id_armazenagem_insumos
            ) AS totais ON totais.id_armazenagem_insumos = ai.id
            '.$where.'
            GROUP BY ai.id
            ORDER BY e.dthr_entrada ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            /*
            $quantidadeArmazenada = $row->quantidade_total_armazenada !== null ? (int)$row->quantidade_total_armazenada : 0;
            $quantidadeArmazenar = (int)$row->quantidade_entrada - $quantidadeArmazenada;
            */

            $responseData[] = array(
                'idArmazenagemInsumo' => (int)$row->id_armazenagem_insumo,
                'idEntradaInsumo' => (int)$row->id_entrada_insumo,
                'idInsumo' => (int)$row->id_insumo,
                'insInsumo' => $row->ins_insumo,
                'nomeInsumo' => $row->nome_insumo,
                'idAlmoxarifado' => (int)$row->id_almoxarifado,
                'nomeAlmoxarifado' => $row->nome_almoxarifado,
                'idPosicao' => (int)$row->id_posicao,
                'nomePosicao' => $row->nome_posicao,
                'quantidadeDisponivel' => (int)$row->quantidade_disponivel,
                'dataRecebimento' => $row->dthr_entrada
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }    

    public function createUpdateSaida($request){
        try{
            // Validações
            $i = 0;
            while($i < count($request['lancamentos'])){
                if(
                    !array_key_exists('idArmazenagemInsumos', $request['lancamentos'][$i])
                    or $request['lancamentos'][$i]['idArmazenagemInsumos'] === ''
                    or $request['lancamentos'][$i]['idArmazenagemInsumos'] === null
                )
                    throw new \Exception('Campo idArmazenagemInsumos é obrigatório.');
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
            if($request['idSaida']){
                $sql = '
                    update pcp_saidas
                    set
                        dthr_saida = now(),
                        id_usuario = :idUsuario
                    where id = :idSaida;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->bindParam(':idSaida', $request['idSaida']);
                $stmt->execute();
                $idSaida = $request['idSaida'];
                $msg = 'Armazenagem atualizada com sucesso.';
            }
            // Insert
            else{
                $sql = '
                    insert into pcp_saidas
                    set
                        dthr_saida = now(),
                        id_usuario = :idUsuario
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->execute();
                $idSaida = $this->pdo->lastInsertId();
                $msg = 'Saída registrada com sucesso.';
            }
            
            // Removendo todos as armazenagens realizadas para o insumo do pedido
            $sqlDelete = '
                delete from pcp_saida_insumos
                where id_saida = :idSaida;
            ';
            $stmt = $this->pdo->prepare($sqlDelete);
            $stmt->bindParam(':idSaida', $idSaida);
            $stmt->execute();

            // Inserindo registros de armazenagem
            $i = 0;
            while($i < count($request['lancamentos'])){
                $sql = '
                    insert into pcp_saida_insumos
                    set
                        id_saida = :idSaida,
                        id_armazenagem_insumos = :idArmazenagemInsumos,
                        id_almoxarifado = :idAlmoxarifado,
                        id_posicao = :idPosicao,
                        quantidade = :quantidade
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idSaida', $idSaida);
                $stmt->bindParam(':idArmazenagemInsumos', $request['lancamentos'][$i]['idArmazenagemInsumos']);
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
            $cod = $etiquetas[$i]['idPedidoInsumo'].'-'.$etiquetas[$i]['idAlmoxarifado'].'-'.$etiquetas[$i]['idPosicao'];
            $strBase64 = 'data:image/png;base64,'.base64_encode($generator->getBarcode($cod, $generator::TYPE_CODE_128));
            $img = $this->generateImage($strBase64, 'etiquetasArmazenagem/images/'.$cod.'.png');
          
            $pdf->Cell(45, 12, utf8_decode('Código: '.$cod), 'LTRB', 0, 'L');
            $pdf->Cell(50, 12, $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTRB', 0, 'L', false);
            if($proximo){
                $pdf->Cell(2, 12, '', 0, 0, 'C');
                
                $cod = $etiquetas[$i]['idPedidoInsumo'].'-'.$etiquetas[$i+1]['idAlmoxarifado'].'-'.$etiquetas[$i+1]['idPosicao'];
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