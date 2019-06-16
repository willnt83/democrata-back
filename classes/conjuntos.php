<?php
class Conjuntos{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getConjuntos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'c.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select c.id, c.nome, c.ativo, s.id id_setor, s.nome nome_setor, sp.id id_subproduto, sp.nome nome_subproduto, cs.quantidade_subproduto, cs.pontos_subproduto
            from pcp_conjuntos c
            join pcp_setores s on s.id = c.id_setor
            left join pcp_conjuntos_subprodutos cs on cs.id_conjunto = c.id
            left join pcp_subprodutos sp on sp.id = cs.id_subproduto
            '.$where.'
            order by c.id, sp.nome;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        $subprodutos = array();
        $prevId = 0;
        $i = 0;
        while ($row = $stmt->fetch()) {
            // Primeira linha
            if($i === 0){
                $responseData[] = array(
                    'id' => (int)$row->id,
                    'nome' => $row->nome,
                    'ativo' => $row->ativo,
                    'idSetor' => (int)$row->id_setor,
                    'nomeSetor' => $row->nome_setor
                );
                $i++;
            }
            // Demais linhas
            else{
                if($prevId != (int)$row->id){
                    $responseData[($i - 1)]['subprodutos'] = $subprodutos;
                    $subprodutos = [];
                    $responseData[] = array(
                        'id' => (int)$row->id,
                        'nome' => $row->nome,
                        'ativo' => $row->ativo,
                        'idSetor' => (int)$row->id_setor,
                        'nomeSetor' => $row->nome_setor
                    );
                    $i++;
                }
            }
            if($row->id_subproduto !== null){
                $subprodutos[] = array(
                    'id' => (int)$row->id_subproduto,
                    'nome' => $row->nome_subproduto,
                    'quantidade' => (int)$row->quantidade_subproduto,
                    'pontos' => (float)$row->pontos_subproduto
                );
            }

            $prevId = $row->id;
        }
        $responseData[($i - 1)]['subprodutos'] = $subprodutos;

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateConjunto($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('setor', $request) or $request['setor'] === '' or $request['setor'] === null)
                throw new \Exception('Campo Setor é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_conjuntos
                    set
                        nome = :nome,
                        ativo = :ativo,
                        id_setor = :setor
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->bindParam(':setor', $request['setor']);
                $stmt->execute();
                $conjuntoId = $request['id'];
                $msg = 'Conjunto atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_conjuntos
                    set
                        nome = :nome,
                        ativo = :ativo,
                        id_setor = :setor
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->bindParam(':setor', $request['setor']);
                $stmt->execute();
                $conjuntoId = $this->pdo->lastInsertId();
                $msg = 'Conjunto cadastrado com sucesso.';
            }

            // Relação produto x subproduto
            if($request['subprodutos'] !== null and count($request['subprodutos']) > 0){
                $sql = '
                    delete from pcp_conjuntos_subprodutos
                    where id_conjunto = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $conjuntoId);
                $stmt->execute();

                foreach($request['subprodutos'] as $key => $subproduto){
                    $sql = '
                        insert into pcp_conjuntos_subprodutos
                        set
                            id_conjunto = :id_conjunto,
                            id_subproduto = :id_subproduto,
                            quantidade_subproduto = :quantidade_subproduto,
                            pontos_subproduto = :pontos_subproduto;
                    ';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_conjunto', $conjuntoId);
                    $stmt->bindParam(':id_subproduto', $subproduto['id']);
                    $stmt->bindParam(':quantidade_subproduto', $subproduto['quantidade']);
                    $stmt->bindParam(':pontos_subproduto', $subproduto['pontos']);
                    $stmt->execute();
                }
            }

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

    public function deleteConjunto($filters){
        try{
            $sql = '
                delete from pcp_conjuntos
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Conjunto removido com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
}