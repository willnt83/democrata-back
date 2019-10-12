<?php

class WMSProdSaidas{
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
            FROM pcp_saida_produtos a
            JOIN pcp_usuarios u ON u.id = a.id_usuario
            '.$where.'
            ORDER BY a.id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $dataSaidaFormalizada = new DateTime($row->dthr_saida);
            $responseData[] = array(
                'id' => (int)$row->id_saida,
                'usuario' => array(
                    'id' => (int)$row->id_usuario,
                    'nome' => $row->nome_usuario,
                ),
                'dataSaida' => $dataSaidaFormalizada->format('d/m/Y H:i:s')
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getSaidaProdutos($filters){
        try{
            $where = '';
            if(count($filters) > 0){
                $where = 'where ';
                $i = 0;
                foreach($filters as $key => $value){
                    $and = $i > 0 ? ' and ' : '';
                    $where .= $and.'saidaprod.'.$key.' = :'.$key;
                    $i++;
                }
            }

            $sql = '
                select  saidaprod.id,
                        saidaprod.id_saida_produtos,
                        pro.id as idProduto,
                        pro.nome as nomeProduto,
                        pro.sku as skuProduto,
                        cor.nome as nomeCor,
                        alm.id as idAlmoxarifado,
                        alm.nome as nomeAlmoxarifado,
                        pos.id as idPosicao,
                        pos.posicao as nomePosicao
                from	pcp_saida_produtos_itens saidaprod
                        inner join pcp_produtos pro on pro.id = saidaprod.id_produto
                        inner join pcp_cores cor on cor.id = pro.id_cor
                        inner join wmsprod_almoxarifados alm on alm.id = saidaprod.id_almoxarifado
                        inner join wmsprod_posicoes pos on pos.id = saidaprod.id_posicao
                '.$where.'
                order by saidaprod.id
                ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filters);

            $responseData = array();
            while($row = $stmt->fetch()){
                $responseData[] = array(
                    'id' => (int) $row->id_saida_produtos,
                    'idSaidaProduto' => (int) $row->id,                
                    'produto' => array(
                        'id'    => (int) $row->idProduto,
                        'sku'   => $row->skuProduto,
                        'nome'  => $row->nomeProduto,
                        'cor'   => $row->nomeCor
                    ),
                    'almoxarifado' => array(
                        'id' => (int) $row->idAlmoxarifado,
                        'descricao' => $row->nomeAlmoxarifado
                    ),
                    'posicao' => array(
                        'id' => (int) $row->idPosicao,
                        'descricao' => $row->nomePosicao
                    )             
                );
            }

            return json_encode(array(
                'success' => true,
                'payload' => $responseData
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => 'Erro ao buscar os dados de produtos da saída! Tente novamente.',
                'error' => $e->getMessage()
            ));
        }
    }

    public function lancamentoSaidaProdutos($request){
        try{
            $this->pdo->beginTransaction();

            // Valida a request
            if(!array_key_exists('idUsuario', $request) or $request['idUsuario'] === '' or $request['idUsuario'] === null)
                throw new \Exception('Id do usuário não informado');
            else if(!array_key_exists('barcode', $request) or $request['barcode'] === '' or $request['barcode'] === null)
                throw new \Exception('Código de barras não informado');

            // Verifica se o código de barras é válido
            $barcodeArray = explode('-', $request['barcode']);
            if(count($barcodeArray) < 3)
                throw new \Exception('Código de barras inválido');

            // Quebra o código de barras em: idProduto-IdAlmoxarifado-IdPosicao
            $idProduto      = $barcodeArray[0];
            $IdAlmoxarifado = $barcodeArray[1];
            $IdPosicao      = $barcodeArray[2];

            // Valida se o produto/posição está mesmo armazenado
            $sql = '
                select id 
                from wmsprod_armazenagem_produtos
                where
                    id_produto = :id_produto
                    and id_almoxarifado = :id_almoxarifado
                    and id_posicao = :id_posicao
                order by id desc
                limit 1;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_produto', $idProduto);
            $stmt->bindParam(':id_almoxarifado', $IdAlmoxarifado);
            $stmt->bindParam(':id_posicao', $IdPosicao);
            $stmt->execute();
            $row = $stmt->fetch();
            if($row){
                $idArmazenagemItem = $row->id;
            } else {
                $idArmazenagemItem = 0;
                throw new \Exception('Produto ainda não armazenado.');
            }
            
            if($request['idSaida']){
                $idSaida = $request['idSaida'];
            }
            else{
                $sql = '
                    insert into pcp_saida_produtos
                    set
                        dthr_saida = now(),
                        id_usuario = :idUsuario
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->execute();
                $idSaida = $this->pdo->lastInsertId();
            }

            $sql = '
                insert into pcp_saida_produtos_itens
                set
                    id_saida_produtos = :idSaida,
                    id_armazenagem_produtos_itens = :id_armazenagem_produtos_itens,                    
                    id_produto = :id_produto,
                    id_almoxarifado = :id_almoxarifado,
                    id_posicao = :id_posicao
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idSaida', $idSaida);
            $stmt->bindParam(':id_armazenagem_produtos_itens', $idArmazenagemItem);
            $stmt->bindParam(':id_produto', $idProduto);
            $stmt->bindParam(':id_almoxarifado', $IdAlmoxarifado);
            $stmt->bindParam(':id_posicao', $IdPosicao);
            $stmt->execute();
            $idSaidaProduto = $this->pdo->lastInsertId();

            $this->pdo->commit(); 

            return json_encode(array(
                'success' => true,
                'msg' => 'Saída registrada com sucesso',
                'payload' => array(
                    'idSaida' => $idSaida,
                    'idSaidaProduto'=> $idSaidaProduto
                )
            ));


        } catch(\Exception $e) {
            $this->pdo->rollBack();
            return json_encode(array(
                'success' => false,
                'msg' => 'Erro ao inserir os dados de saída! Tente novamente',
                'error' => $e->getMessage()
            ));
        }
    }
}