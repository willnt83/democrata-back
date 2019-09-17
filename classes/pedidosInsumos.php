<?php

class PedidosInsumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getPedidosCompraAvailabes($filters){
        $where = '';
        $responseData = array();
        if(count($filters) > 0){
            foreach($filters as $key => $value){
                if($key === 'id') {
                    $nick = 'pc.';
                    $equalBinding = ' = :'.$key;
                } else if($key === 'idInsumo') {
                    $nick = 'ins.';                    
                    $equalBinding = ' = :'.$key;
                    $key = 'id';
                } else if($key === 'nomeInsumo') {
                    $nick = 'ins.';                    
                    $equalBinding = ' like :'.$key;
                    $filters[$key] = '%'.$value.'%';
                    $key = 'nome';
                } else {
                    $nick = '';
                    $equalBinding = ' = :'.$key;
                }

                $where .= ' and '.$nick.$key.$equalBinding;
            }
        }
        $sql = 'select 	    pc.id as idPedido, pc.dthr_pedido, pc.dt_prevista, pc.chave_nf,
                            pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor
                from	    pcp_pedidos pc
                            inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                            inner join pcp_insumos ins on pci.id_insumo = ins.id
                            inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                where		pci.quantidade > (
                                select 	ifnull(sum(ent.quantidade), 0)
                                from 	pcp_entrada_insumos as ent 
                                where 	ent.id_pedido_insumo = pci.id
                            )'.$where.'
                group by	pc.id;';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        while ($row = $stmt->fetch()) {
            $row->idPedido = (int)$row->idPedido;
            $responseData[] = $row;
        }
        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getPedidosCompraInsumosAvailabes($filters){
        $where          = '';

        if(count($filters) > 0){
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
                } else {
                    $nick = '';
                    $equalBinding = ' = :'.$key;
                }
                
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$nick.$key.$equalBinding;
                $i++;
            }
        }

        $sql = 'select 	pci.id, pc.id as idPedido,
                        pc.dthr_pedido, pc.dt_prevista, pc.chave_nf,
                        pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor,
                        pci.id_insumo as idInsumo, ins.nome as nomeInsumo, ins.ins as insInsumo, 
                        um.unidade as unidadeUnidadeMedida, pci.quantidade, 
                        ifnull((select sum(quantidade) from pcp_entrada_insumos e where e.id_pedido_insumo = pci.id),0) as quantidadeConferida
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_insumos ins on pci.id_insumo = ins.id
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                        left join pcp_unidades_medida um on um.id = ins.id_unidade_medida
                '.$where.'                        
                having(quantidade > quantidadeConferida)
                order by pci.id';
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

    public function getPedidosInsumosAvailabes(){
        $responseData = array();
        $sql = 'select 	    ins.id, ins.nome, ins.ins, um.unidade as unidadeUnidadeMedida
                from	    pcp_insumos ins
                            inner join pcp_pedidos_insumos pci on pci.id_insumo = ins.id
                            left join pcp_unidades_medida um on um.id = ins.id_unidade_medida
                where	    pci.quantidade > (
                                select 	ifnull(sum(ent.quantidade),0)
                                from 	pcp_entrada_insumos as ent 
                                where 	ent.id_pedido_insumo = pci.id
                            )
                group by    ins.id
                order by    pci.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $row->id = (int)$row->id;
            $responseData[] = $row;
        }
        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }
}