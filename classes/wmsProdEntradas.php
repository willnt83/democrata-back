<?php

class WMSProdEntradas{
    public function __construct($db){
        $this->pdo = $db;
    }

    /*
    public function getEntradaProdutos($filters){
        $where = 'WHERE cb.estorno = "N" AND cb.lancado = "Y"';
        if(count($filters) > 0){
            $i = 0;
            foreach($filters as $key => $value){
                $and = ' and ';
                $where .= $and.'cb.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                cb.id idEntrada,
                cb.id_produto idProduto,
                p.nome nomeProduto,
                c.nome corProduto
            FROM pcp_codigo_de_barras cb
            JOIN pcp_produtos p ON p.id = cb.id_produto
            JOIN pcp_cores c ON c.id = p.id_cor
            '.$where.'
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->idEntrada,
                'idProduto' => (int)$row->idProduto,
                'nomeProduto' => $row->nomeProduto,
                'corProduto' => $row->corProduto
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }
    */

    public function estornarEntradaProduto($request){
        try{
            // Valida a request
            if(!array_key_exists('idUsuario', $request) or $request['idUsuario'] === '' or $request['idUsuario'] === null)
                throw new \Exception('idUsuario não informado');
            if(!array_key_exists('codigo', $request) or $request['codigo'] === '' or $request['codigo'] === null)
                throw new \Exception('codigo não informado');

            // Valida se o produto já foi lançado e não foi estornado
            $sql = '
                select id, lancado, estorno
                from pcp_codigo_de_barras
                where
                    codigo = :codigo
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->execute();
            $row = $stmt->fetch();
            if($row->lancado == 'N'){
                if($row->estorno == 'N')
                    throw new \Exception('Produto ainda não foi lançado');
                else if($row->estorno == 'Y')
                    throw new \Exception('Produto já foi estornado');
            }

            $idCodigo = $row->id;

            // Valida se o produto já se encontra armazenado
            $sql = '
                SELECT
                    ap.id,
                    ap.id_armazenagem idArmazenagem,
                    a.nome nomeAlmoxarifado,
                    p.posicao nomePosicao,
                    ap.estorno
                FROM wmsprod_armazenagem_produtos ap
                JOIN wmsprod_almoxarifados a ON a.id = ap.id_almoxarifado
                JOIN wmsprod_posicoes p ON p.id = ap.id_posicao
                WHERE ap.id_codigo = :idCodigo;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idCodigo', $idCodigo);
            $stmt->execute();
            $row = $stmt->fetch();
            if(isset($row->id) and $row->estorno == 'N'){
                throw new \Exception('Não foi possível realizar estorno de entrada pois o produto já está armazenado no endereço: '.$row->nomeAlmoxarifado.' - '.$row->nomePosicao);
            }

            // Lançamento validado, seguindo com estorno
            $sql = '
                update pcp_codigo_de_barras
                set
                    lancado = "N",
                    estorno = "Y",
                    id_usuario_estorno = :idUsuarioEstorno,
                    dthr_estorno = now()
                where
                    codigo = :codigo
            ';
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindParam(':idUsuarioEstorno', $request['idUsuario']);
            $stmt->bindParam(':codigo', $request['codigo']);
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Estorno de entrada realizado com sucesso'
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function getEstornosEntradaProduto(){
        $sql = '
            SELECT
                cb.codigo codigoProduto,
                concat(p.nome," (", c.nome,")") descricaoProduto,
                u.nome nomeUsuario,
                cb.dthr_estorno dthrEstorno
            FROM pcp_codigo_de_barras cb
            JOIN pcp_produtos p ON p.id = cb.id_produto
            JOIN pcp_cores c ON c.id = p.id_cor
            JOIN pcp_usuarios u ON u.id = cb.id_usuario_estorno
            WHERE
                cb.id_setor = 8
                AND cb.lancado = "N"
                AND cb.estorno = "Y"
            ORDER BY cb.dthr_estorno DESC;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'codigoProduto' => $row->codigoProduto,
                'descricaoProduto' => $row->descricaoProduto,
                'nomeUsuario' => $row->nomeUsuario,
                'dthrEstorno' => $row->dthrEstorno
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }
}