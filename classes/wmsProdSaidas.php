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
            FROM wmsprod_saidas a
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
                        saidaprod.id_saida,
                        pro.id as idProduto,
                        pro.nome as nomeProduto,
                        pro.sku as skuProduto,
                        cor.id as idCor,
                        cor.nome as nomeCor
                from	wmsprod_saida_produtos saidaprod
                        inner join pcp_produtos pro on pro.id = saidaprod.id_produto
                        inner join pcp_cores cor on cor.id = pro.id_cor
                '.$where.'
                order by saidaprod.id
                ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filters);

            $responseData = array();
            while($row = $stmt->fetch()){
                $responseData[] = array(
                    'id' => (int) $row->id_saida,
                    'idSaidaProduto' => (int) $row->id,                
                    'produto' => array(
                        'id'    => (int) $row->idProduto,
                        'sku'   => $row->skuProduto,
                        'nome'  => $row->nomeProduto
                    ),
                    'cor' => array(
                        'id' => (int) $row->idCor,
                        'descricao' => $row->nomeCor
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
            if(count($barcodeArray) < 2)
                throw new \Exception('Código de barras inválido');

            // Quebra o código de barras e recupera o id produto
            $idProduto = $barcodeArray[1];
            
            // Update ou insert
            if($request['idSaida']){
                $idSaida = $request['idSaida'];
            }
            else{
                $sql = '
                    insert into wmsprod_saidas
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
                insert into wmsprod_saida_produtos
                set
                    id_saida = :idSaida,
                    id_produto = :id_produto,
                    codigo = :codigo
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idSaida', $idSaida);
            $stmt->bindParam(':id_produto', $idProduto);
            $stmt->bindParam(':codigo', $request['barcode']);
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