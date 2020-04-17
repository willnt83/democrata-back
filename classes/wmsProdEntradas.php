<?php

class WMSProdEntradas{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getEntradaProdutos($filters){
        $where = 'where cb.estorno = "N"';
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

    public function estornarEntradaProduto($request){
        try{
            // Valida a request
            if(!array_key_exists('idUsuario', $request) or $request['idUsuario'] === '' or $request['idUsuario'] === null)
                throw new \Exception('idUsuario não informado');
            if(!array_key_exists('idEntradaProduto', $request) or $request['idEntradaProduto'] === '' or $request['idEntradaProduto'] === null)
                throw new \Exception('idEntradaProduto não informado');

            // Valida se o produto está armazenado e em estoque
            $sql = '
                select id, lancado, estorno
                from pcp_codigo_de_barras
                where
                    id = :idEntradaProduto
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':idEntradaProduto', $request['idEntradaProduto']);
            $stmt->execute();
            $row = $stmt->fetch();
            if($row->lancado == 'N'){
                if($row->estorno == 'N')
                    throw new \Exception('Produto ainda não foi lançado');
                else if($row->estorno == 'Y')
                    throw new \Exception('Produto já foi estornado');
            }
            
            $sql = '
                update pcp_codigo_de_barras
                set
                    lancado = "N",
                    estorno = "Y",
                    id_usuario_estorno = :idUsuarioEstorno
                where
                    id = :idEntradaProduto
            ';
            $stmt = $this->pdo->prepare($sql);

            $stmt->bindParam(':idUsuarioEstorno', $request['idUsuario']);
            $stmt->bindParam(':idEntradaProduto', $request['idEntradaProduto']);
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
}