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
            SELECT ep.id, ep.dthr_armazenagem, u.nome nome_usuario
            from wmsprod_armazenagens ep
            LEFT JOIN pcp_usuarios u ON u.id = ep.id_usuario
            '.$where.'
            order by ep.id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $dataFormalizadaD = new DateTime($row->dthr_armazenagem);
            $dataFormalizada = $dataFormalizadaD->format('d/m/Y H:i:s');
            $responseData[] = array(
                'id' => (int)$row->id,
                'dataArmazenagem' => $dataFormalizada,
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
                $where .= $and.'ap.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                ap.id,
                ap.id_armazenagem,
                ap.codigo,
                ap.id_produto,
                pro.nome nome_produto,
                cor.nome cor_produto,
                ap.id_almoxarifado,
                a.nome nome_almoxarifado,
                ap.id_posicao,
                p.posicao nome_posicao
            FROM wmsprod_armazenagem_produtos ap
            JOIN pcp_produtos pro ON pro.id = ap.id_produto
            JOIN pcp_cores cor ON cor.id = pro.id_cor
            JOIN wmsprod_almoxarifados a ON a.id = ap.id_almoxarifado
            JOIN wmsprod_posicoes p ON p.id = ap.id_posicao
            '.$where.'
            order by ap.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'idArmazenagem' => (int)$row->id_armazenagem,
                'codigo' => $row->codigo,
                'produto' => array(
                    'id' => (int)$row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto
                ),
                'almoxarifado' => array(
                    'id' => (int)$row->id_almoxarifado,
                    'nome' => $row->nome_almoxarifado
                ),
                'posicao' => array(
                    'id' => (int)$row->id_posicao,
                    'nome' => $row->nome_posicao
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
                throw new \Exception('idUsuario não informado');
            if(!array_key_exists('codigo', $request) or $request['codigo'] === '' or $request['codigo'] === null)
                throw new \Exception('codigo não informado');
            if(!array_key_exists('idProduto', $request) or $request['idProduto'] === '' or $request['idProduto'] === null)
                throw new \Exception('idProduto não informado');
            if(!array_key_exists('idAlmoxarifado', $request) or $request['idAlmoxarifado'] === '' or $request['idAlmoxarifado'] === null)
                throw new \Exception('idAlmoxarifado não informado');
            if(!array_key_exists('idPosicao', $request) or $request['idPosicao'] === '' or $request['idPosicao'] === null)
                throw new \Exception('idPosicao não informado');

            // Valida se o produto está na entrada
            $sql = '
                select id from pcp_entrada_produtos_itens
                where
                    codigo = :codigo
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->execute();
            if(count($stmt->fetchAll()) == 0){
                throw new \Exception('Produto não encontra-se na entrada');
            }

            // Valida se o produto já foi armazenado
            $sql = '
                select id from wmsprod_armazenagem_produtos
                where
                    codigo = :codigo
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->execute();
            if(count($stmt->fetchAll()) > 0){
                throw new \Exception('Produto já armazenado');
            }
            
            if($request['idArmazenagem']){
                $idArmazenagem = $request['idArmazenagem'];
            }
            else{
                $sql = '
                    insert into wmsprod_armazenagens
                    set
                        dthr_armazenagem = now(),
                        id_usuario = :idUsuario
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idUsuario', $request['idUsuario']);
                $stmt->execute();
                $idArmazenagem = $this->pdo->lastInsertId();
            }

            $barcodeArr = explode('-', $request['codigo']);

            $sql = '
                insert into wmsprod_armazenagem_produtos
                set
                    id_armazenagem = :idArmazenagem,
                    codigo = :codigo,
                    id_produto = :idProduto,
                    id_almoxarifado = :idAlmoxarifado,
                    id_posicao = :idPosicao
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idArmazenagem', $idArmazenagem);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->bindParam(':idProduto', $request['idProduto']);
            $stmt->bindParam(':idAlmoxarifado', $request['idAlmoxarifado']);
            $stmt->bindParam(':idPosicao', $request['idPosicao']);
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Entrada registrada com sucesso',
                'payload' => array(
                    'idArmazenagem' => $idArmazenagem
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