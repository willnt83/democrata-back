<?php

class PedidosInsumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getPedidosInsumos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pin.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            SELECT
                pin.id id,
                p.id idPedido,
                p.chave_nf chaveNF,
                i.nome nomeInsumo,
                i.ins insInsumo,
                pin.quantidade quantidadeConferida,
                pin.dthr_recebimento dthrRecebimento,
                pin.`status`
            FROM pcp_pedidos p
            JOIN pcp_pedidos_insumos pin ON pin.id_pedido = p.id
            JOIN pcp_insumos i ON i.id = pin.id_insumo
            '.$where.'
            order by p.id;';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $row->id = (int)$row->id;
            $row->idPedido = (int)$row->idPedido;
            $row->insInsumo = (int)$row->insInsumo;
            $row->quantidadeConferida = (int)$row->quantidadeConferida;
            $responseData[] = $row;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function changeStatusInsumo($request){
        try{
            // Código do pedido insumo
            if(!array_key_exists('idPedidoInsumo', $request) or $request['idPedidoInsumo'] === '' or $request['idPedidoInsumo'] === null)
                throw new \Exception('Insumo do pedido de compra não informado.');
            else
                $idPedidoInsumo = $request['idPedidoInsumo'];

            // Status do pedido insumo
            if(!array_key_exists('status', $request) or $request['status'] === '' or $request['status'] === null)
                throw new \Exception('Insumo do pedido de compra não informado.');
            else
                $status = $request['status'];

            // Alterando o status
            $this->changeStatus($idPedidoInsumo, $status);

            return json_encode(array(
                'success' => true
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    protected function changeStatus($idPedidoInsumo, $status){
        $sql = 'update  pcp_pedidos_insumos
        set     status = :status
        where   id = :idPedidoInsumo';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idPedidoInsumo', $idPedidoInsumo);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    }
}