<?php

class WMSProdArmazenagens{
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
                $where .= $and.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT ep.id, ep.dthr_entrada, u.nome nome_usuario
            from pcp_entrada_produtos ep
            LEFT JOIN pcp_usuarios u ON u.id = ep.id_usuario
            '.$where.'
            order by ep.id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'dataEntrada' => $row->dthr_entrada,
                'nomeUsuario' => $row->nome_usuario
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getArmazenagemProdutos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                epi.id,
                epi.id_entrada_produtos,
                epi.id_producao,
                pd.nome nome_producao,
                epi.id_produto,
                pro.nome nome_produto,
                epi.codigo,
                cor.nome cor_produto
            from pcp_entrada_produtos_itens epi
            JOIN pcp_producoes pd ON pd.id = epi.id_producao
            JOIN pcp_produtos pro ON pro.id = epi.id_produto
            JOIN pcp_cores cor ON cor.id = pro.id_cor
            '.$where.'
            order by epi.id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'idEntradaProdutos' => (int)$row->id_entrada_produtos,
                'codigo' => $row->codigo,
                'producao' => array(
                    'id' => (int)$row->id_producao,
                    'nome' => $row->nome_producao
                ),
                'produto' => array(
                    'id' => (int)$row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }



    public function lancamentoArmazenagemProdutos($request){
        try{
            // Valida a request
            if(!array_key_exists('idUsuario', $request) or $request['idUsuario'] === '' or $request['idUsuario'] === null)
                throw new \Exception('Id do usuário não informado');
            else if(!array_key_exists('barcode', $request) or $request['barcode'] === '' or $request['barcode'] === null)
                throw new \Exception('Código de barras não informado');

            // Valida se o produto foi lançado
            $sql = '
                select lancado from pcp_codigo_de_barras
                where
                    codigo = :codigo
                    and lancado="Y";
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['barcode']);
            $stmt->execute();
            if(count($stmt->fetchAll()) == 0){
                throw new \Exception('Produto ainda não finalizado.');
            }

            // Valida se o produto já teve sua entrada lançada
            $sql = '
                select id from pcp_entrada_produtos_itens
                where codigo = :codigo;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['barcode']);
            $stmt->execute();
            if(count($stmt->fetchAll()) > 0){
                throw new \Exception('Entrada do produto já realizada.');
            }

            
            if($request['idEntrada']){
                $idEntrada = $request['idEntrada'];
            }
            else{
                $sql = '
                    insert into pcp_entrada_produtos
                    set
                        dthr_entrada = now(),
                        id_usuario = :idUsuario
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->execute();
                $idEntrada = $this->pdo->lastInsertId();
            }

            $barcodeArr = explode('-', $request['barcode']);

            $sql = '
                insert into pcp_entrada_produtos_itens
                set
                    id_entrada_produtos = :idEntrada,
                    codigo = :codigo,
                    id_producao = :idProducao,
                    id_produto = :idProduto
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idEntrada', $idEntrada);
            $stmt->bindParam(':codigo', $request['barcode']);
            $stmt->bindParam(':idProducao', $barcodeArr[0]);
            $stmt->bindParam(':idProduto', $barcodeArr[1]);
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Entrada registrada com sucesso',
                'payload' => array(
                    'idEntrada' => $idEntrada
                )
            ));


        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    
}