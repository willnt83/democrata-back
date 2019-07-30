<?php

class PedidosInsumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getPedidosInsumos($filters){
        $where          = '';
        $fullPedido     = false;

        if(count($filters) > 0){
            // Full pedido
            if(isset($filters['fullPedido']) and $filters['fullPedido'] === 'S') $fullPedido = true;
            unset($filters['fullPedido']);

            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                if($key === 'id') {
                    $nick = 'pci.';
                    $equalBinding = ' = :'.$key;
                } else if($key === 'idPedido') {
                    $nick = 'pc.';
                    $equalBinding = ' = :'.$key;
                    $key = 'id';
                } else if($key === 'idInsumo') {
                    $nick = 'ins.';                    
                    $equalBinding = ' = :'.$key;
                    $key = 'id';
                } else if($key === 'nomeInsumo') {
                    $nick = 'ins.';                    
                    $equalBinding = ' like :'.$key;
                    $filters[$key] = '%'.$value.'%';
                    $key = 'nome';
                } else if($key === 'status' or $key === 'statusPedido'){
                    $nick = ($key === 'statusPedido') ? 'pc.' : 'pci.';
                    $statusesArray = explode(',', $value);
                    if(count($statusesArray) > 1) {
                        $equalBinding = '';
                        unset($filters[$key]);
                        foreach($statusesArray as $key_status=>$value_status){
                            if($key_status > 0) $equalBinding .= ' or ';
                            $equalBinding .= $nick.'status = :'.$key.$key_status;
                            $filters[$key.$key_status] = $value_status;
                        }
                        $key = '';                        
                        $nick = '';
                        if(trim($equalBinding) != '') $equalBinding = '('.$equalBinding.')';
                    } else {
                        $equalBinding = ' = :'.$key;
                        $key = 'status';
                    }                    
                } else {
                    $nick = '';
                    $equalBinding = ' = :'.$key;
                }
                
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$nick.$key.$equalBinding;
                $i++;
            }
        }

        $sql = 'select 	pci.id, pc.id as idPedido, pc.status as statusPedido,
                        '.( ($fullPedido) ?
                            'pc.dthr_pedido, pc.dt_prevista, pc.chave_nf,
                             pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor,' :
                            '').'
                        pci.id_insumo as idInsumo, ins.nome as nomeInsumo, ins.ins as insInsumo, 
                        um.unidade as unidadeUnidadeMedida, pci.status as status, pci.quantidade, 
                        ifnull((select sum(quantidade) from pcp_entrada_insumos e where e.id_pedido_insumo = pci.id),0) as quantidadeConferida
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_insumos ins on pci.id_insumo = ins.id
                        '.( ($fullPedido) ?
                            'inner join pcp_fornecedores f on pc.id_fornecedor = f.id' :
                            '').'
                        left join pcp_unidades_medida um on um.id = ins.id_unidade_medida                        
                '.$where.'
                order by pci.id;';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $row->id = (int)$row->id;
            $row->idPedido = (int)$row->idPedido;
            $row->idInsumo = (int)$row->idInsumo;
            $row->quantidade = (float)$row->quantidade;
            $row->quantidadeConferida = (float)$row->quantidadeConferida;
            if(isset($row->id_fornecedor)) $row->id_fornecedor = (int) $row->id_fornecedor;
            $responseData[] = $row;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getInsumosAvailabesToEnter(){
        $responseData = array();
        $sql = 'select 	    ins.id, ins.nome, ins.ins, um.unidade as unidadeUnidadeMedida
                from	    pcp_insumos ins
                            inner join pcp_pedidos_insumos pci on pci.id_insumo = ins.id
                            left join pcp_unidades_medida um on um.id = ins.id_unidade_medida
                where	    pci.status in ("S","E")
                group by    ins.id
                order by    pci.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        while ($row = $stmt->fetch()) {
            $row->id = (int)$row->id;
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

    public function changeStatus($idPedidoInsumo, $status){
        $sql = 'update  pcp_pedidos_insumos
                set     status = :status
                where   id = :idPedidoInsumo';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idPedidoInsumo', $idPedidoInsumo);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    }
}