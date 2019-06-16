<?php

class Subprodutos{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getSubprodutos($filters){
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
            select *
            from pcp_subprodutos
            '.$where.'
            order by id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'ativo' => $row->ativo,
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateSubproduto($request){
        print_r($request);
        try{
            // Validações
            if(!array_key_exists('nome', $request)
                or $request['nome'] === ''
                or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_subprodutos
                    set
                        nome = :nome,
                        ativo = :ativo
                    where id = :id;
                ';

                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Subproduto atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_subprodutos
                    set
                        nome = :nome,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Subproduto cadastrado com sucesso.';
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

    public function deleteSubproduto($filters){
        try{
            $sql = '
                delete from pcp_subprodutos
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Subproduto removido com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function getSubprodutosPorProducaoSetor($filters){
        $sql = '
            SELECT
                cb.id_subproduto id, ss.nome nome
            FROM pcp_codigo_de_barras cb
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            WHERE
                cb.id_producao = :idProducao
                AND cb.id_setor = :idSetor
            GROUP BY ss.nome
            ORDER BY cb.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idProducao', $filters['idProducao']);
        $stmt->bindParam(':idSetor', $filters['idSetor']);
        $stmt->execute();

        $responseData = array();

        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }
}