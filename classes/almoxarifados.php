<?php

class Almoxarifados{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getAlmoxarifados($filters){
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
            from pcp_almoxarifado
            '.$where.'
            order by id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int) $row->id,
                'nome' => $row->nome,
                'ativo' => $row->ativo,
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateAlmoxarifado($request){
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
                    update pcp_almoxarifado
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

                $msg = 'Almoxarifado atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_almoxarifado
                    set
                        nome = :nome,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Almoxarifado cadastrado com sucesso.';
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

    public function deleteAlmoxarifado($filters){
        try{
            $sql = '
                delete from pcp_almoxarifado
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Almoxarifado removido com sucesso.'
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