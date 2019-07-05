<?php

class InsumosArmazenagem{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getInsumosArmazenagem($filters){
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
            SELECT *
            FROM pcp_insumos_armazenagem ia
            '.$where.'
            order by ia.id_pedido_insumo;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $row->id = (int)$row->id;
            $row->id_pedido_insumo = (int)$row->id_pedido_insumo;
            $row->id_almoxarifado = (int)$row->id_almoxarifado;
            $row->id_posicao = (int)$row->id_posicao;
            $row->quantidade = (int)$row->quantidade;
            $responseData[] = $row;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateInsumosArmazenagem($request){
        try{
            // Validações
            $i = 0;
            while($i < count($request['lancamentos'])){
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

            // Removendo todos as armazenagens realizadas para o insumo do pedido
            $sqlDelete = '
                delete from pcp_insumos_armazenagem
                where id_pedido_insumo = :idPedidoInsumo;
            ';
            $stmt = $this->pdo->prepare($sqlDelete);
            $stmt->bindParam(':idPedidoInsumo', $request['idPedidoInsumo']);
            $stmt->execute();
            $i = 0;

            while($i < count($request['lancamentos'])){
                // Inserindo registros de armazenagem
                $sql = '
                    insert into pcp_insumos_armazenagem
                    set
                        id_pedido_insumo = :idPedidoInsumo,
                        id_almoxarifado = :idAlmoxarifado,
                        id_posicao = :idPosicao,
                        quantidade = :quantidade
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':idPedidoInsumo', $request['idPedidoInsumo']);
                $stmt->bindParam(':idAlmoxarifado', $request['lancamentos'][$i]['idAlmoxarifado']);
                $stmt->bindParam(':idPosicao', $request['lancamentos'][$i]['idPosicao']);
                $stmt->bindParam(':quantidade', $request['lancamentos'][$i]['quantidade']);
                $stmt->execute();
                $i++;
            }
            $msg = 'Armazenagem de insumo registrada com sucesso.';

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
}