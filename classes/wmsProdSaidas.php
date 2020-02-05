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
                    $where .= $and.'sp.'.$key.' = :'.$key;
                    $i++;
                }
            }

            $sql = '
                SELECT
                    sp.id,
                    sp.id_saida,
                    sp.codigo,
                    sp.id_produto,
                    p.nome nome_produto,
                    p.sku sku_produto,
                    c.nome cor_produto
                FROM wmsprod_saida_produtos sp
                JOIN pcp_produtos p ON p.id = sp.id_produto
                JOIN pcp_cores c ON c.id = p.id_cor
                '.$where.'
                ORDER BY sp.id
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filters);

            $responseData = array();
            while($row = $stmt->fetch()){
                $responseData[] = array(
                    'id' => (int) $row->id,
                    'idSaida' => (int) $row->id_saida,
                    'codigo' => $row->codigo,
                    'produto' => array(
                        'id'    => (int) $row->id_produto,
                        'sku'   => $row->sku_produto,
                        'nome'  => $row->nome_produto,
                        'cor' => $row->cor_produto
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

            $barcodeArray = explode('-', $request['barcode']);

            // Verifica se o o produto encontra-se armazenado
            $sqlVer = '
                select count(*) as registros
                    from wmsprod_armazenagem_produtos
                    where
                        codigo = :barcode;
                ';
            $stmtVer = $this->pdo->prepare($sqlVer);
            $stmtVer->bindParam(':barcode', $request['barcode']);
            $stmtVer->execute();
            $rowVer = $stmtVer->fetch();
            $existeRegistro = $rowVer->registros > 0 ? true : false;
            if(!$existeRegistro)
                throw new \Exception('Este produto não encontra-se armazenado');

            // Verifica se o código de barras é válido e o produto existe no pcp_codigo_de_barras
            $sqlVer = '
                select count(*) as registros, id
                    from pcp_codigo_de_barras
                    where
                        codigo = :barcode;
                ';
            $stmtVer = $this->pdo->prepare($sqlVer);
            $stmtVer->bindParam(':barcode', $request['barcode']);
            $stmtVer->execute();
            $rowVer = $stmtVer->fetch();
            $existeRegistro = $rowVer->registros > 0 ? true : false;
            if(!$existeRegistro)
                throw new \Exception('Código de barras inválido');
            else
                $idCodigo = $rowVer->id;

            // Verifica se já foi feita a saída do produto
            $sqlVer = '
                select count(*) as registros, id
                    from wmsprod_saida_produtos
                    where
                        codigo = :barcode;
                ';
            $stmtVer = $this->pdo->prepare($sqlVer);
            $stmtVer->bindParam(':barcode', $request['barcode']);
            $stmtVer->execute();
            $rowVer = $stmtVer->fetch();
            $existeRegistro = $rowVer->registros > 0 ? true : false;


            if($existeRegistro)
                throw new \Exception('Saída do produto já lançada');

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
                    codigo = :codigo,
                    id_codigo = :idCodigo
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idSaida', $idSaida);
            $stmt->bindParam(':id_produto', $idProduto);
            $stmt->bindParam(':codigo', $request['barcode']);
            $stmt->bindParam(':idCodigo', $idCodigo);
            $stmt->execute();
            $idSaidaProduto = $this->pdo->lastInsertId();
            $this->pdo->commit(); 

            $sqlUp = '
                UPDATE wmsprod_armazenagem_produtos
                SET estoque = "N"
                WHERE id_codigo = :idCodigo
            ';
            $stmt = $this->pdo->prepare($sqlUp);
            $stmt->bindParam(':idCodigo', $idCodigo);
            $stmt->execute();

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
                'msg' => $e->getMessage()
            ));
        }
    }
}