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
        $where = 'where ap.estorno = "N"';
        if(count($filters) > 0){
            $i = 0;
            foreach($filters as $key => $value){
                $and = ' and ';
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
                ap.estoque,
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
                'estoque' => $row->estoque,
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

    public function getProdutoArmazenadoInfo($filters){
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
                pro.id id_produto,
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
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        while($row = $stmt->fetch()) {
            $responseData = array(
                'idArmazenagemProduto' => (int)$row->id,
                'produto' => array(
                    'id' => (int)$row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto,
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

            // Valida se o produto já foi armazenado
            $sql = '
                select id
                from wmsprod_armazenagem_produtos
                where
                    codigo = :codigo
                    and estorno = "N"
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->execute();
            $row = $stmt->fetch();
            if(isset($row->id)){
                throw new \Exception('Produto já armazenado');
            }

            // Valida se o produto está na entrada
            $sql = '
                select id
                from pcp_codigo_de_barras cb
                where
                    cb.id_setor = 8
                    and cb.lancado = "Y"
                    and cb.codigo = :codigo
                limit 1
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->execute();
            $row = $stmt->fetch();
            if(!isset($row->id))
                throw new \Exception('Produto não encontra-se na entrada');
            else
                $idCodigo = $row->id;
            

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
                    id_posicao = :idPosicao,
                    id_codigo = :idCodigo
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idArmazenagem', $idArmazenagem);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->bindParam(':idProduto', $request['idProduto']);
            $stmt->bindParam(':idAlmoxarifado', $request['idAlmoxarifado']);
            $stmt->bindParam(':idPosicao', $request['idPosicao']);
            $stmt->bindParam(':idCodigo', $idCodigo);
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

    public function alteracaoEndereco($request){
        try{
            // Valida a request
            if(!array_key_exists('idArmazenagemProduto', $request) or $request['idArmazenagemProduto'] === '' or $request['idArmazenagemProduto'] === null)
                throw new \Exception('idArmazenagemProduto não informado');
            if(!array_key_exists('idAlmoxarifado', $request) or $request['idAlmoxarifado'] === '' or $request['idAlmoxarifado'] === null)
                throw new \Exception('idAlmoxarifado não informado');
            if(!array_key_exists('idPosicao', $request) or $request['idPosicao'] === '' or $request['idPosicao'] === null)
                throw new \Exception('idPosicao não informado');

            // Valida se o produto está armazenado e em estoque
            $sql = '
                select id
                from wmsprod_armazenagem_produtos
                where
                    id = :idArmazenagemProduto
                    and estoque = "Y"
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idArmazenagemProduto', $request['idArmazenagemProduto']);
            $stmt->execute();
            $row = $stmt->fetch();
            if(!isset($row->id)){
                throw new \Exception('Produto não encontra-se armazenado ou já foi registrado sua saída');
            }

            
            $sql = '
                update wmsprod_armazenagem_produtos
                set
                    id_almoxarifado = :idAlmoxarifado,
                    id_posicao = :idPosicao
                where
                    id = :idArmazenagemProduto
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idAlmoxarifado', $request['idAlmoxarifado']);
            $stmt->bindParam(':idPosicao', $request['idPosicao']);
            $stmt->bindParam(':idArmazenagemProduto', $request['idArmazenagemProduto']);
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Alteração de endereço realizada com sucesso'
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function estornarArmazenagemProduto($request){
        try{
            // Valida a request
            if(!array_key_exists('idUsuario', $request) or $request['idUsuario'] === '' or $request['idUsuario'] === null)
                throw new \Exception('idUsuario não informado');
            if(!array_key_exists('idArmazenagemProduto', $request) or $request['idArmazenagemProduto'] === '' or $request['idArmazenagemProduto'] === null)
                throw new \Exception('idArmazenagemProduto não informado');

            // Valida se o produto está armazenado e em estoque
            $sql = '
                select id, estoque, estorno
                from wmsprod_armazenagem_produtos
                where
                    id = :idArmazenagemProduto
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idArmazenagemProduto', $request['idArmazenagemProduto']);
            $stmt->execute();
            $row = $stmt->fetch();
            if(!isset($row->id)){
                throw new \Exception('Produto não encontra-se armazenado');
            }
            else if($row->estoque != 'Y'){
                throw new \Exception('Produto já foi expedido');
            }
            else if($row->estorno == 'Y'){
                throw new \Exception('Produto já foi estornado');
            }
            
            $sql = '
                update wmsprod_armazenagem_produtos
                set
                    estorno = "Y",
                    id_usuario_estorno = :idUsuarioEstorno
                where
                    id = :idArmazenagemProduto
            ';
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindParam(':idUsuarioEstorno', $request['idUsuario']);
            $stmt->bindParam(':idArmazenagemProduto', $request['idArmazenagemProduto']);
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Estorno de armazenagem realizado com sucesso'
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
}