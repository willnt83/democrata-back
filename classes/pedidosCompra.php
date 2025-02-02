<?php
class PedidosCompra{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getPedidosCompra($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'pc.'.$key.' = :'.$key;
                $i++;
            }
        }

        $responseData = array();

        $sql = 'select 	pc.id, pc.dthr_pedido, pc.dt_prevista, pc.chave_nf,
                        pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor
                from	pcp_pedidos pc
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                '.$where.'
                order by pc.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);
        while ($row = $stmt->fetch()) {
            $dthr_pedido = explode(' ', $row->dthr_pedido);
            $responseData[] = array(
                'id'                => (int) $row->id,
                'data_pedido'       => (isset($dthr_pedido[0]) and $dthr_pedido[0]) ? $dthr_pedido[0] : null,
                'hora_pedido'       => (isset($dthr_pedido[1]) and $dthr_pedido[1]) ? $dthr_pedido[1] : null,
                'data_prevista'     => $row->dt_prevista,
                'idFornecedor'      => (int) $row->idFornecedor,
                'nomeFornecedor'    => $row->nomeFornecedor,
                'chave_nf'          => $row->chave_nf
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function getPedidosCompraInsumos($filters){
        $where = '';
        
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                // Table's nickname
                if($key === 'id') {
                    $nick = 'pc.';
                    $equalBinding = ' = :'.$key;
                } else if($key === 'idPedidoInsumo') {
                    $nick = 'pci.';                    
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

        $sql = 'select 	pc.id, pc.dthr_pedido, pc.dt_prevista, pc.chave_nf, 
                        pc.id_fornecedor as idFornecedor, f.nome as nomeFornecedor,
                        pci.id as item, pci.id_insumo as idInsumo, ins.nome as nomeInsumo, ins.ins, 
                        um.unidade as unidadeUnidadeMedida, pci.valor, pci.quantidade,
                        ifnull((select sum(quantidade) from pcp_entrada_insumos e where e.id_pedido_insumo = pci.id),0) as quantidade_conferida
                from	pcp_pedidos pc
                        inner join pcp_pedidos_insumos pci on pci.id_pedido = pc.id
                        inner join pcp_insumos ins on pci.id_insumo = ins.id
                        inner join pcp_fornecedores f on pc.id_fornecedor = f.id
                        left join pcp_unidades_medida um on um.id = ins.id_unidade_medida
                '.$where.'
                group by pci.id
                order by pc.id, pci.id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $i = 0;
        $pedidoId = 0;
        $responseData = array();
        while ($row = $stmt->fetch()) {
            // Pedido
            if ($pedidoId != (int) $row->id) {
                $dthr_pedido = explode(' ', $row->dthr_pedido);
                $responseData[] = array(
                    'id'                => (int) $row->id,
                    'data_pedido'       => (isset($dthr_pedido[0]) and $dthr_pedido[0])  ? $dthr_pedido[0] : null,
                    'hora_pedido'       => (isset($dthr_pedido[1]) and $dthr_pedido[1])  ? $dthr_pedido[1] : null,
                    'data_prevista'     => $row->dt_prevista,
                    'idFornecedor'      => (int) $row->idFornecedor,
                    'nomeFornecedor'    => $row->nomeFornecedor,
                    'chave_nf'          => $row->chave_nf,
                    'insumos'           => array()
                );
                $i++;
            }

            // Insumos
            $responseData[($i-1)]['insumos'][] = array(
                'item'                  => (int) $row->item,
                'id'                    => (int) $row->idInsumo,
                'nome'                  => $row->nomeInsumo,
                'ins'                   => $row->ins,
                'unidademedida'         => $row->unidadeUnidadeMedida,
                'valor'                 => (float) $row->valor,
                'quantidade'            => (float) $row->quantidade,
                'quantidade_conferida'  => (float) $row->quantidade_conferida
            );

            $pedidoId = $row->id;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdatePedidoCompra($request){
        try{
            // Validações
            if(!array_key_exists('data_pedido', $request) or $request['data_pedido'] === '' or $request['data_pedido'] === null)
                throw new \Exception('Data do pedido é obrigatório.');
            if(!array_key_exists('hora_pedido', $request) or $request['hora_pedido'] === '' or $request['hora_pedido'] === null)
                throw new \Exception('Hora do pedido é obrigatória.');
            if(!array_key_exists('chave_nf', $request) or $request['chave_nf'] === '' or $request['chave_nf'] === null)
                throw new \Exception('Chave da Nota Fiscal é obrigatória.');
            if(!array_key_exists('idFornecedor', $request) or $request['idFornecedor'] === '' or $request['idFornecedor'] === null)
                throw new \Exception('Fornecedor é obrigatório.');
            if(!array_key_exists('data_prevista', $request) or $request['data_prevista'] === '' or $request['data_prevista'] === null)
                throw new \Exception('Data de previsão é obrigatória.');                              

            // Valida os insumos
            foreach($request['insumos'] as $key => $insumo){
                if(!array_key_exists('idInsumo', $insumo) or $insumo['idInsumo'] === '' or $insumo['idInsumo'] === null)
                    throw new \Exception('Insumo é obrigatório.');
                if(!array_key_exists('quantidade', $insumo) or $insumo['quantidade'] === '' or $insumo['quantidade'] === null)
                    throw new \Exception('Quantidade é obrigatória.');  
                    
                // Verifica se possui entrada com quantidade maior que o insumo
                
            }

            // Pedido de Compra
            if($request['id']){
                // Edit
                $sql = 'update  pcp_pedidos
                        set     dthr_pedido = CONCAT(:data_pedido," ",:hora_pedido),
                                chave_nf = :chave_nf,
                                id_fornecedor = :id_fornecedor,
                                dt_prevista = :dt_prevista
                        where   id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':data_pedido', $request['data_pedido']);
                $stmt->bindParam(':hora_pedido', $request['hora_pedido']);
                $stmt->bindParam(':chave_nf', $request['chave_nf']);
                $stmt->bindParam(':id_fornecedor', $request['idFornecedor']);
                $stmt->bindParam(':dt_prevista', $request['data_prevista']);
                $stmt->execute();
                $pedidoId = $request['id'];
                $msg = 'Pedido de compra atualizado com sucesso.';
            }
            else{
                $sql = 'insert into pcp_pedidos
                        set dthr_pedido = CONCAT(:data_pedido," ",:hora_pedido),
                            chave_nf = :chave_nf,
                            id_fornecedor = :id_fornecedor,
                            dt_prevista = :dt_prevista';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':data_pedido', $request['data_pedido']);
                $stmt->bindParam(':hora_pedido', $request['hora_pedido']);
                $stmt->bindParam(':chave_nf', $request['chave_nf']);
                $stmt->bindParam(':id_fornecedor', $request['idFornecedor']);
                $stmt->bindParam(':dt_prevista', $request['data_prevista']);
                $stmt->execute();
                $pedidoId = $this->pdo->lastInsertId();
                $msg = 'Pedido de compra cadastrado com sucesso.';
            }

            // Inserindo os insumos
            $id_pedido_insumos_array = array();
            foreach($request['insumos'] as $key => $insumo){
                // Verifica se existe o insumo para inserir/atualizar
                if($insumo['item']){
                    $id_pedido_insumos_array[] = $insumo['item'];
                    $sql = 'update  pcp_pedidos_insumos
                            set     id_pedido = :id_pedido,
                                    id_insumo = :id_insumo,
                                    valor = :valor,
                                    quantidade = :quantidade
                            where   id = :item ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido', $pedidoId);
                    $stmt->bindParam(':id_insumo', $insumo['idInsumo']);
                    $stmt->bindParam(':valor', $insumo['valor']);
                    $stmt->bindParam(':quantidade', $insumo['quantidade']);
                    $stmt->bindParam(':item', $insumo['item']);
                    $stmt->execute();
                } else {
                    $sql = 'insert into pcp_pedidos_insumos
                            set id_pedido = :id_pedido,
                                id_insumo = :id_insumo,
                                valor = :valor, 
                                quantidade = :quantidade';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_pedido', $pedidoId);
                    $stmt->bindParam(':id_insumo', $insumo['idInsumo']);
                    $stmt->bindParam(':valor', $insumo['valor']);
                    $stmt->bindParam(':quantidade', $insumo['quantidade']);
                    $stmt->execute();
                    $idItem = $this->pdo->lastInsertId();
                    if($idItem) $id_pedido_insumos_array[] = $idItem;
                }
                
            }

            // Deletando os Insumos não existentes
            if(count($id_pedido_insumos_array) > 0) {
                $sql = 'delete from pcp_pedidos_insumos where id_pedido = :id and not id in ('.implode(',',$id_pedido_insumos_array).')';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $pedidoId);
                $stmt->execute();
            }  

            // Reponse
            return json_encode(array(
                'success' => true,
                'msg' => $msg
            ));
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function deletePedidoCompra($filters){
        try{
            if(isset($filters['id']) and $filters['id']){                
                // Verifica se já possui entrada, armazenamento e/ou saída para os insumosdo pedido de compra
                $sqlVerify = '  select 	count(*) as total
                                from	pcp_pedidos p
                                        inner join pcp_pedidos_insumos pi on pi.id_pedido = p.id
                                where	p.id = :id and
                                        (select e.id from pcp_entrada_insumos e where e.id_pedido_insumo = pi.id limit 1) > 0';
                $stmtVerify = $this->pdo->prepare($sqlVerify);
                $stmtVerify->bindParam(':id', $filters['id']);
                $stmtVerify->execute();
                $rowVerify = $stmtVerify->fetch();
                if($rowVerify and $rowVerify->total > 0){
                    throw new \Exception('Não é permitido excluir pedido de compra com insumo já entregue ou armazenado.');
                }
                
                // Deletando os Insumos
                $sql = 'delete from pcp_pedidos_insumos where id_pedido = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $filters['id']);
                $stmt->execute();
                
                // Deletando o Pedido de Compras
                $sql = 'delete from pcp_pedidos where id = :id';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $filters['id']); 
                $stmt->execute();
            }

            return json_encode(array(
                'success' => true,
                'msg' => 'Pedido de compra removido com sucesso.'
            ));
        }
        catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function printPedidoCompra($filters){        
        try{
            $response = json_decode($this->getPedidosCompraInsumos(['id'=>$filters['id']]));
            if($response->success and count($response->payload) == 1){
                require('../vendor/fpdf/fpdf.php');

                $pedidoCompra = $response->payload[0];

                $pdf = new FPDF('P','mm','A4');

                $pdf->SetAutoPagebreak(true);            
                $pdf->AliasNbPages();            
                $pdf->AddPage();

                $width = $pdf->GetPageWidth();            

                $pdf->SetFont('Times','B',16);
                $pdf->Cell(0, 25,utf8_decode('REQUISIÇÃO DE PEDIDO DE COMPRA'),0,1,'C');
                $pdf->Line(10,$pdf->GetY(),$width-10,$pdf->GetY());
                $pdf->Ln(1);

                $pdf->SetFont('Times','B',11);
                $pdf->Cell(16, 7,utf8_decode('#Pedido:'), 0, 0, 'L');
                $pdf->SetFont('Times','',12);
                $pdf->Cell(($width/2)-16, 7,$pedidoCompra->id, 0, 0, 'L');
                $pdf->SetX(($width/2)+1);
                $pdf->SetFont('Times','B',11);
                $pdf->Cell(50, 7,'Data do Pedido:', 0, 0, 'R');
                $pdf->SetFont('Times','',12);
                $pdf->Cell(45, 7,utf8_decode($pedidoCompra->data_pedido.' às '.$pedidoCompra->hora_pedido), 0, 1, 'R');

                $pdf->SetFont('Times','B',11);
                $pdf->Cell(40, 7,'Chave da Nota Fiscal:', 0, 0, 'L');
                $pdf->SetFont('Times','',12);
                $pdf->Cell(50, 7, $pedidoCompra->chave_nf, 0, 1, 'L');

                $pdf->SetFont('Times','B',11);
                $pdf->Cell(40, 7,'Fornecedor: ', 0, 0, 'L');
                $pdf->SetFont('Times','',12);
                $pdf->Cell(50, 7,$pedidoCompra->idFornecedor.' - '.utf8_decode($pedidoCompra->nomeFornecedor), 0, 1, 'L');

                $pdf->Ln(10);
                $pdf->SetFont('Times','B',11);
                $pdf->Cell(20, 7,'INSUMOS', 0, 0, 'L');
                $pdf->SetFont('Times','',12);
                $pdf->Cell(($width/2)-20, 7,'(Itens: '.count($pedidoCompra->insumos).')', 0, 0, 'L');
                $pdf->SetX(($width/2)+1);
                $pdf->SetFont('Times','B',11);
                $pdf->Cell(65, 7,utf8_decode('Previsão de Entrega:'), 0, 0, 'R');
                $pdf->SetFont('Times','',12);
                $pdf->Cell(28, 7,$pedidoCompra->data_prevista, 0, 1, 'R');         
                $pdf->Line(10,$pdf->GetY(),$width-10,$pdf->GetY());
                $pdf->Ln(2);

                // Itens do pedido de compras
                $item = 0;
                $page = 1;
                $total = count($pedidoCompra->insumos);
                foreach($pedidoCompra->insumos as $key=>$insumo){
                    $item++;

                    // Insumo + INS                    
                    $pdf->SetFont('Times','B',11);
                    $pdf->Cell(7, 7,($key+1).')', 0, 0, 'L');
                    $pdf->SetFont('Times','',12);
                    $pdf->Cell(($width-7), 7,$insumo->id.' '.$insumo->nome.' (INS: '.$insumo->ins.')', 0, 1, 'L');
                    
                    // Valor
                    $pdf->SetX(17);
                    $pdf->SetFont('Times','B',10);
                    $pdf->Cell(18, 5,'Valor: ', 0, 0, 'L');
                    $pdf->SetFont('Times','',12);
                    $pdf->Cell(20, 5,$insumo->valor, 0, 0, 'L');

                    // Quantidade 
                    $pdf->SetFont('Times','B',10);
                    $pdf->Cell(20, 5,'Quantidade: ', 0, 0, 'L');
                    $pdf->SetFont('Times','',12);
                    $pdf->Cell(20, 5,$insumo->quantidade, 0, 0, 'L');

                    // Unidade de Medida
                    $pdf->SetFont('Times','B',10);
                    $pdf->Cell(35, 5,'Unidade de Medida: ', 0, 0, 'L');
                    $pdf->SetFont('Times','',12);
                    $pdf->Cell(0, 5,$insumo->unidademedida, 0, 1, 'L');

                    $pdf->Ln(5);

                    // Pular página (se não for ainda o último item)
                    if((($page == 1 and $item == 12) or ($page > 1 and $item == 16)) and ($key+1) < $total){
                        $page++;
                        $item = 0 ;
                        $pdf->AddPage();
                        if($page > 1) $pdf->Ln(8);
                    }
                }

                $path = 'pedidoscompra/pedido-'.$pedidoCompra->id.'.pdf';
                $pdf->Output('F', $path, true);

                return json_encode(array(
                    'success' => true,
                    'payload' => array(
                        'url' => $path
                    )
                ));            
            } else {
                return json_encode(array(
                    'success' => false,
                    'msg' -> utf8_decode('Não há dados para imprimir')
                ));     
            }       
        }
        catch(Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }            
    }
}