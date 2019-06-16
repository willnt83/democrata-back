<?php

class Subsetores{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getSubsetores($filters){
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
            select ss.id, ss.nome, ss.ativo, ss.id_setor, s.nome as nome_setor
            from pcp_subsetores ss
            join pcp_setores s on s.id = ss.id_setor
            '.$where.'
            order by s.id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'ativo' => $row->ativo,
                'setor' => array(
                    'id' => (int)$row->id_setor,
                    'nome' => $row->nome_setor
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateSubsetor($request){
        print_r($request);
        try{
            // Validações
            if(!array_key_exists('nome', $request)
                or $request['nome'] === ''
                or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('setor', $request) or !array_key_exists('id', $request['setor'])
                or $request['setor']['id'] === ''
                or $request['setor']['id'] === null)
                throw new \Exception('Campo Setor é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_subsetores
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
                $stmt->bindParam(':setor', $request['setor']['id']);
                $stmt->execute();

                $msg = 'Subsetor atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_subsetores
                    set
                        nome = :nome,
                        ativo = :ativo,
                        id_setor = :setor
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->bindParam(':setor', $request['setor']['id']);
                $stmt->execute();

                $msg = 'Subsetor cadastrado com sucesso.';
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

    public function deleteSubsetor($filters){
        try{
            $sql = '
                delete from pcp_subsetores
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Subsetor removido com sucesso.'
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